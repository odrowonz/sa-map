<?php

namespace App\Services;

use App\Models\Attachment;
use App\Models\Element;
use App\Models\Project;
use App\Support\AttachmentFileIcon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;
use ZipArchive;

class ProjectDataExchangeService
{
    /** Импорт старых архивов; в новых — `{slug}.json`. */
    public const LEGACY_JSON_NAME = 'sa-map-project-export.json';

    /** @deprecated Используйте {@see exportManifestJsonName()}. */
    public const JSON_NAME = self::LEGACY_JSON_NAME;

    /** @deprecated Используйте {@see exportManifestMdName()}. */
    public const MD_NAME = 'sa-map-project-export.md';

    public const FORMAT = 'sa-map-project-export';

    public const VERSION = 1;

    /**
     * Конфиг для шаблонов в духе {@see templates/template.njk}: metadata.levels_config + ключи data «L{n}_…».
     *
     * @return array{levels_config: list<array{n: int, title: string, q: string, fields: list<array{name: string, label: string, isArray: bool}>}>}
     */
    public static function mdTemplateLegacyBridge(): array
    {
        $artifacts = config('sa_map.artifacts', []);
        $levelsConfig = [];

        foreach (range(1, 10) as $n) {
            $fields = [];

            foreach ((array) ($artifacts[$n] ?? []) as $art) {
                if (! is_array($art)) {
                    continue;
                }
                $key = (string) ($art['key'] ?? '');
                $prefix = 'L'.$n.'_';
                $name = str_starts_with($key, $prefix) ? substr($key, strlen($prefix)) : $key;
                $label = trim((string) ($art['label'] ?? ''));
                if ($label === '') {
                    $label = $name;
                }
                $fields[] = [
                    'name' => $name,
                    'label' => $label,
                    'isArray' => (bool) ($art['multiple'] ?? false),
                ];
            }

            $levelsConfig[] = [
                'n' => $n,
                'title' => (string) __('sa.map_levels.'.$n.'.title'),
                'q' => (string) __('sa.map_levels.'.$n.'.question'),
                'fields' => $fields,
            ];
        }

        return ['levels_config' => $levelsConfig];
    }

    public static function exportSlug(Project $project): string
    {
        $raw = (string) ($project->slug ?? '');
        $safe = $raw !== '' ? preg_replace('/[^a-zA-Z0-9._-]+/', '-', $raw) : '';

        return $safe !== '' ? $safe : 'project';
    }

    public static function exportManifestJsonName(Project $project): string
    {
        return self::exportSlug($project).'.json';
    }

    public static function exportManifestMdName(Project $project): string
    {
        return self::exportSlug($project).'.md';
    }

    /**
     * Подкаталог в pics/ для уровня: L{n}_{English_title_underscores} (заголовок из локали en).
     */
    public static function picsLevelDirSegment(int $level): string
    {
        if ($level < 1 || $level > 10) {
            return 'L'.$level.'_level';
        }
        $title = trim((string) Lang::get('sa.map_levels.'.$level.'.title', [], 'en'));
        $underscore = preg_replace('/\s+/u', '_', $title) ?? '';
        $underscore = preg_replace('/[^a-zA-Z0-9_\-]+/u', '', $underscore) ?? '';
        $underscore = preg_replace('/_+/u', '_', $underscore) ?? '';
        $underscore = trim($underscore, '_');
        if ($underscore === '') {
            $underscore = 'level_'.$level;
        }

        return 'L'.$level.'_'.$underscore;
    }

    public function exportZipResponse(Project $project, bool $forMdExport = false, ?array $elementIds = null): StreamedResponse
    {
        $tmp = tempnam(sys_get_temp_dir(), 'sampexp');
        if ($tmp === false) {
            throw new RuntimeException('Cannot create temp file');
        }
        @unlink($tmp);
        $tmpZip = $tmp.'.zip';

        if ($elementIds !== null) {
            $this->rememberWizardSelection($project, $forMdExport ? 'export_md' : 'export_data', $elementIds);
        }

        [$manifest, $zipFileEntries, $iconSlugs] = $this->buildExportManifest($project, $forMdExport, $elementIds);
        $jsonName = self::exportManifestJsonName($project);

        $zip = new ZipArchive;
        if ($zip->open($tmpZip, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('Cannot create ZIP');
        }

        $iconBodies = [];
        foreach ($iconSlugs as $slug) {
            $svg = $this->fetchVscodeIconSvg($slug);
            if ($svg !== null) {
                $iconBodies[$slug] = $svg;
            }
        }
        $this->injectPackagedIconPaths($manifest, $iconBodies);

        $zip->addFromString($jsonName, json_encode($manifest, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));

        foreach ($zipFileEntries as $entry) {
            if (! empty($entry['storage_key']) && Storage::disk('local')->exists($entry['storage_key'])) {
                $zip->addFile(Storage::disk('local')->path($entry['storage_key']), $entry['zip_path']);
            }
        }

        foreach ($iconBodies as $slug => $svg) {
            $zip->addFromString('icons/file_type_'.$slug.'.svg', $svg);
        }

        $zip->close();

        $downloadName = self::exportSlug($project).'_data.zip';

        return response()->streamDownload(function () use ($tmpZip): void {
            readfile($tmpZip);
            @unlink($tmpZip);
        }, $downloadName, [
            'Content-Type' => 'application/zip',
        ]);
    }

    /**
     * @return array{0: array<string, mixed>, 1: list<array{zip_path: string, storage_key: string}>, 2: list<string>}
     */
    /**
     * @param  list<string>|null  $elementIds  null — по флагам include_in_export_* в БД; иначе только перечисленные id.
     */
    private function buildExportManifest(Project $project, bool $forMdExport = false, ?array $elementIds = null): array
    {
        $q = Element::query()
            ->where('project_id', $project->id)
            ->with(['attachments'])
            ->orderBy('level')
            ->orderBy('sort_order');
        if ($elementIds !== null) {
            $ids = array_values(array_unique(array_filter($elementIds, fn ($id) => is_string($id) && Str::isUuid($id))));
            if ($ids === []) {
                $elements = collect();
            } else {
                $elements = (clone $q)->whereIn('id', $ids)->get();
            }
        } else {
            $flagCol = $forMdExport ? 'include_in_export_md' : 'include_in_export_data';
            $elements = (clone $q)->where($flagCol, true)->get();
        }

        $out = [];
        $zipFileEntries = [];
        $iconSlugs = [];

        foreach ($elements as $el) {
            $attachments = [];
            foreach ($el->attachments as $att) {
                $safeBase = $this->safeFilename($att->original_name, (string) $att->id);
                $graphic = $this->attachmentIsGraphicForExport($att);
                $picsSeg = self::picsLevelDirSegment((int) $el->level);
                $zipPath = $graphic
                    ? 'pics/'.$picsSeg.'/'.$att->id.'_'.$safeBase
                    : 'files/'.$att->id.'_'.$safeBase;
                $iconSlug = AttachmentFileIcon::vscodeIconSlug($att);
                $iconSlugs[$iconSlug] = true;
                $attachments[] = [
                    'id' => $att->id,
                    'original_name' => $att->original_name,
                    'mime_type' => $att->mime_type,
                    'kind' => $att->kind,
                    'size_bytes' => $att->size_bytes,
                    'zip_path' => $zipPath,
                ];
                $zipFileEntries[] = [
                    'zip_path' => $zipPath,
                    'storage_key' => $att->storage_key,
                ];
            }
            $out[] = [
                'id' => $el->id,
                'level' => $el->level,
                'artifact_key' => $el->artifact_key,
                'sort_order' => $el->sort_order,
                'content' => $el->content ?? [],
                'attachments' => $attachments,
            ];
        }

        $manifest = [
            'format' => self::FORMAT,
            'version' => self::VERSION,
            'exported_at' => now()->toIso8601String(),
            'project' => [
                'name' => $project->name,
                'slug' => $project->slug,
            ],
            'elements' => $out,
        ];
        if ($forMdExport) {
            $manifest['levels_config'] = $this->filterLevelsConfigForMdExport($out);
        }

        return [$manifest, $zipFileEntries, array_keys($iconSlugs)];
    }

    /**
     * Только уровни и поля, для которых есть строки в экспорте (учёт галочек мастера).
     *
     * @param  list<array<string, mixed>>  $exportedRows
     * @return list<array{n: int, title: string, q: string, fields: list<array{name: string, label: string, isArray: bool}>}>
     */
    private function filterLevelsConfigForMdExport(array $exportedRows): array
    {
        $keysByLevel = [];
        foreach ($exportedRows as $row) {
            if (! is_array($row)) {
                continue;
            }
            $lv = (int) ($row['level'] ?? 0);
            $ak = (string) ($row['artifact_key'] ?? '');
            if ($lv < 1 || $lv > 10 || $ak === '') {
                continue;
            }
            $keysByLevel[$lv][] = $ak;
        }
        foreach ($keysByLevel as $lv => $keys) {
            $keysByLevel[$lv] = array_values(array_unique($keys));
        }

        $full = self::mdTemplateLegacyBridge()['levels_config'];
        $out = [];
        foreach ($full as $lvl) {
            if (! is_array($lvl)) {
                continue;
            }
            $n = (int) ($lvl['n'] ?? 0);
            if ($n < 1 || $n > 10) {
                continue;
            }
            $atKeys = $keysByLevel[$n] ?? [];
            if ($atKeys === []) {
                continue;
            }
            $fields = [];
            foreach ($lvl['fields'] ?? [] as $f) {
                if (! is_array($f)) {
                    continue;
                }
                $fname = (string) ($f['name'] ?? '');
                if ($fname === '') {
                    continue;
                }
                $fieldKey = 'L'.$n.'_'.$fname;
                if (in_array($fieldKey, $atKeys, true)) {
                    $fields[] = $f;
                }
            }
            if ($fields === []) {
                continue;
            }
            $out[] = [
                'n' => $n,
                'title' => (string) ($lvl['title'] ?? ''),
                'q' => (string) ($lvl['q'] ?? ''),
                'fields' => $fields,
            ];
        }

        return $out;
    }

    /**
     * Дерево L1–L10 → типы артефактов → элементы + все id для выбора по умолчанию.
     *
     * @return array{levels: list<array<string, mixed>>, all_element_ids: list<string>}
     */
    public static function exportWizardTree(Project $project): array
    {
        $elements = Element::query()
            ->where('project_id', $project->id)
            ->orderBy('level')
            ->orderBy('artifact_key')
            ->orderBy('sort_order')
            ->get(['id', 'level', 'artifact_key', 'sort_order', 'content', 'include_in_export_data', 'include_in_export_md', 'include_in_import']);
        $byLevel = $elements->groupBy('level');
        $levels = [];
        foreach (range(1, 10) as $n) {
            $artifacts = config('sa_map.artifacts.'.$n);
            if (! is_array($artifacts)) {
                continue;
            }
            $atLevel = $byLevel->get($n, collect());
            $artifactNodes = [];
            foreach ($artifacts as $art) {
                if (! is_array($art)) {
                    continue;
                }
                $key = (string) ($art['key'] ?? '');
                $label = trim((string) ($art['label'] ?? ''));
                if ($label === '') {
                    $label = $key;
                }
                $rows = $atLevel->where('artifact_key', $key)->values();
                $elNodes = [];
                foreach ($rows as $el) {
                    $elNodes[] = [
                        'id' => $el->id,
                        'label' => $el->label(),
                        'include_export_data' => (bool) $el->include_in_export_data,
                        'include_export_md' => (bool) $el->include_in_export_md,
                        'include_in_import' => (bool) $el->include_in_import,
                    ];
                }
                if ($elNodes === []) {
                    continue;
                }
                $artifactNodes[] = [
                    'key' => $key,
                    'label' => $label,
                    'elements' => $elNodes,
                ];
            }
            if ($artifactNodes === []) {
                continue;
            }
            $levels[] = [
                'n' => $n,
                'title' => (string) __('sa.map_levels.'.$n.'.title'),
                'artifacts' => $artifactNodes,
            ];
        }

        return [
            'levels' => $levels,
            'all_element_ids' => $elements->pluck('id')->all(),
        ];
    }

    /**
     * @param  list<string>  $elementIds
     * @param  list<string>|null  $allManifestElementIds  для import_data: все id из манифеста, чтобы снять галочки у неотмеченных строк, существующих в БД.
     */
    public function rememberWizardSelection(Project $project, string $context, array $elementIds, ?array $allManifestElementIds = null): void
    {
        if (! in_array($context, ['export_data', 'export_md', 'import_data'], true)) {
            return;
        }
        $ids = array_values(array_unique(array_filter($elementIds, fn ($id) => is_string($id) && Str::isUuid($id))));
        $idSet = array_fill_keys($ids, true);

        if ($context === 'export_data') {
            DB::transaction(function () use ($project, $ids): void {
                Element::query()->where('project_id', $project->id)->update(['include_in_export_data' => false]);
                if ($ids !== []) {
                    Element::query()->where('project_id', $project->id)->whereIn('id', $ids)->update(['include_in_export_data' => true]);
                }
            });

            return;
        }

        if ($context === 'export_md') {
            DB::transaction(function () use ($project, $ids): void {
                Element::query()->where('project_id', $project->id)->update(['include_in_export_md' => false]);
                if ($ids !== []) {
                    Element::query()->where('project_id', $project->id)->whereIn('id', $ids)->update(['include_in_export_md' => true]);
                }
            });

            return;
        }

        $allM = $allManifestElementIds !== null
            ? array_values(array_unique(array_filter($allManifestElementIds, fn ($id) => is_string($id) && Str::isUuid($id))))
            : null;

        if ($allM !== null && $allM !== []) {
            $inDb = Element::query()
                ->where('project_id', $project->id)
                ->whereIn('id', $allM)
                ->pluck('id')
                ->all();
            foreach ($inDb as $eid) {
                Element::query()->where('id', $eid)->update([
                    'include_in_import' => isset($idSet[$eid]),
                ]);
            }

            return;
        }

        if ($ids !== []) {
            Element::query()->where('project_id', $project->id)->whereIn('id', $ids)->update(['include_in_import' => true]);
        }
    }

    /**
     * @param  array<string, string>  $iconBodies  slug => svg body
     * @param  array<string, mixed>  $manifest
     */
    private function injectPackagedIconPaths(array &$manifest, array $iconBodies): void
    {
        if ($iconBodies === [] || ! isset($manifest['elements']) || ! is_array($manifest['elements'])) {
            return;
        }
        foreach ($manifest['elements'] as &$el) {
            if (! is_array($el) || ! isset($el['attachments']) || ! is_array($el['attachments'])) {
                continue;
            }
            foreach ($el['attachments'] as &$attRow) {
                if (! is_array($attRow)) {
                    continue;
                }
                $stub = new Attachment([
                    'original_name' => (string) ($attRow['original_name'] ?? ''),
                    'mime_type' => (string) ($attRow['mime_type'] ?? ''),
                    'kind' => (string) ($attRow['kind'] ?? 'other'),
                ]);
                $slug = AttachmentFileIcon::vscodeIconSlug($stub);
                if (isset($iconBodies[$slug])) {
                    $attRow['icon_zip_path'] = 'icons/file_type_'.$slug.'.svg';
                }
            }
            unset($attRow);
        }
        unset($el);
    }

    /** Как в карте: в pics только то, что открывается как растровое/SVG превью (image/*), не по kind «схема». */
    private function attachmentIsGraphicForExport(Attachment $att): bool
    {
        return $att->canPreviewInline();
    }

    private function fetchVscodeIconSvg(string $slug): ?string
    {
        $slug = preg_replace('/[^a-z0-9_]/i', '', $slug) ?? '';
        if ($slug === '') {
            $slug = 'file';
        }
        $url = sprintf(
            'https://cdn.jsdelivr.net/gh/vscode-icons/vscode-icons@%s/icons/file_type_%s.svg',
            AttachmentFileIcon::VSCODE_ICONS_TAG,
            $slug
        );
        try {
            $res = Http::timeout(20)->get($url);
            if (! $res->successful()) {
                return null;
            }
            $body = $res->body();
            if ($body === '' || ! str_contains($body, '<')) {
                return null;
            }

            return $body;
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * @return array{count: int, bundle: 'data'|'md'}
     */
    /**
     * @param  list<string>|null  $onlySourceElementIds  id элементов в JSON архива; null — все.
     */
    public function importFromZip(Project $project, string $zipAbsolutePath, ?string $clientOriginalName = null, ?array $onlySourceElementIds = null): array
    {
        $bundle = $this->classifyImportArchiveBundle($clientOriginalName);

        $zip = new ZipArchive;
        if ($zip->open($zipAbsolutePath) !== true) {
            throw new RuntimeException('Cannot open ZIP');
        }

        $json = $this->readExportJsonFromZip($zip, $project);
        if ($json === false) {
            $zip->close();
            throw new RuntimeException('Missing export JSON manifest in archive');
        }

        $data = json_decode($json, true);
        if (! is_array($data)) {
            $zip->close();
            throw new RuntimeException('Invalid JSON');
        }

        if (($data['format'] ?? null) !== self::FORMAT || (int) ($data['version'] ?? 0) !== self::VERSION) {
            $zip->close();
            throw new RuntimeException('Unsupported export format or version');
        }

        $elements = $data['elements'] ?? [];
        if (! is_array($elements)) {
            $zip->close();
            throw new RuntimeException('Invalid elements');
        }

        $extractDir = sys_get_temp_dir().'/samimp_'.Str::random(16);
        if (! @mkdir($extractDir, 0700, true) && ! is_dir($extractDir)) {
            $zip->close();
            throw new RuntimeException('Cannot create temp dir');
        }

        $zip->extractTo($extractDir);
        $zip->close();

        try {
            $count = $this->importElements($project, $elements, $extractDir, $onlySourceElementIds);
        } finally {
            $this->rrmdir($extractDir);
        }

        return ['count' => $count, 'bundle' => $bundle];
    }

    private function classifyImportArchiveBundle(?string $clientOriginalName): string
    {
        $base = strtolower(basename((string) ($clientOriginalName ?? '')));

        return str_ends_with($base, '_data.zip') ? 'data' : 'md';
    }

    private function readExportJsonFromZip(ZipArchive $zip, Project $project): string|false
    {
        foreach ([self::exportManifestJsonName($project), self::LEGACY_JSON_NAME] as $name) {
            $j = $zip->getFromName($name);
            if ($j !== false) {
                return $j;
            }
        }
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $stat = $zip->statIndex($i);
            if ($stat === false || ! str_ends_with(strtolower((string) $stat['name']), '.json')) {
                continue;
            }
            if (str_contains((string) $stat['name'], '/')) {
                continue;
            }
            $j = $zip->getFromIndex($i);
            if ($j === false) {
                continue;
            }
            $decoded = json_decode($j, true);
            if (is_array($decoded) && ($decoded['format'] ?? null) === self::FORMAT) {
                return $j;
            }
        }

        return false;
    }

    /**
     * @param  list<array<string, mixed>>  $elements
     * @param  list<string>|null  $onlySourceElementIds
     */
    private function importElements(Project $project, array $elements, string $extractDir, ?array $onlySourceElementIds = null): int
    {
        usort($elements, function ($a, $b) {
            $la = (int) ($a['level'] ?? 0);
            $lb = (int) ($b['level'] ?? 0);
            if ($la !== $lb) {
                return $la <=> $lb;
            }

            return ((int) ($a['sort_order'] ?? 0)) <=> ((int) ($b['sort_order'] ?? 0));
        });

        return (int) DB::transaction(function () use ($project, $elements, $extractDir, $onlySourceElementIds) {
            $idMap = [];
            $created = 0;

            foreach ($elements as $row) {
                $level = (int) ($row['level'] ?? 0);
                $artifactKey = (string) ($row['artifact_key'] ?? '');
                if ($level < 1 || $level > 10 || $artifactKey === '') {
                    continue;
                }

                $incomingOldId = (string) ($row['id'] ?? '');
                if ($onlySourceElementIds !== null && $incomingOldId !== '' && ! in_array($incomingOldId, $onlySourceElementIds, true)) {
                    continue;
                }

                $def = $this->artifactDef($project, $level, $artifactKey);
                if ($def === null) {
                    continue;
                }

                $content = is_array($row['content'] ?? null) ? $row['content'] : [];
                $titleNorm = $this->normTitle(is_string($content['title'] ?? null) ? $content['title'] : '');

                $existing = $this->findExistingForReplace($project, $level, $artifactKey, $titleNorm);

                if ($existing !== null) {
                    $existing->delete();
                } elseif (! $def['multiple']) {
                    $slot = Element::query()
                        ->where('project_id', $project->id)
                        ->where('level', $level)
                        ->where('artifact_key', $artifactKey)
                        ->first();
                    if ($slot !== null && $titleNorm === '') {
                        continue;
                    }
                    if ($slot !== null && $titleNorm !== '' && $this->normTitle(is_string($slot->content['title'] ?? null) ? $slot->content['title'] : '') !== $titleNorm) {
                        continue;
                    }
                }

                $newId = (string) Str::uuid();
                $sortOrder = (int) ($row['sort_order'] ?? 0);
                if ($def['multiple']) {
                    $max = (int) Element::query()
                        ->where('project_id', $project->id)
                        ->where('level', $level)
                        ->where('artifact_key', $artifactKey)
                        ->max('sort_order');
                    $sortOrder = $max + 1;
                }

                $upstream = [];
                if ($level > 1 && isset($content['upstreamElementIds']) && is_array($content['upstreamElementIds'])) {
                    foreach ($content['upstreamElementIds'] as $oid) {
                        if (is_string($oid) && isset($idMap[$oid])) {
                            $upstream[] = $idMap[$oid];
                        }
                    }
                }

                $content['upstreamElementIds'] = $upstream;

                $element = new Element([
                    'project_id' => $project->id,
                    'level' => $level,
                    'artifact_key' => $artifactKey,
                    'sort_order' => $sortOrder,
                    'content' => $content,
                    'include_in_export_data' => array_key_exists('include_in_export_data', $row)
                        ? (bool) $row['include_in_export_data']
                        : (array_key_exists('include_in_export', $row) ? (bool) $row['include_in_export'] : true),
                    'include_in_export_md' => array_key_exists('include_in_export_md', $row)
                        ? (bool) $row['include_in_export_md']
                        : (array_key_exists('include_in_export', $row) ? (bool) $row['include_in_export'] : true),
                    'include_in_import' => array_key_exists('include_in_import', $row)
                        ? (bool) $row['include_in_import']
                        : (array_key_exists('include_in_export', $row) ? (bool) $row['include_in_export'] : true),
                ]);
                $element->id = $newId;
                $element->save();

                if ($incomingOldId !== '') {
                    $idMap[$incomingOldId] = $newId;
                }

                foreach ($row['attachments'] ?? [] as $att) {
                    if (! is_array($att)) {
                        continue;
                    }
                    $rel = (string) ($att['zip_path'] ?? '');
                    if ($rel === '' || str_contains($rel, '..')) {
                        continue;
                    }
                    $rel = ltrim(str_replace('\\', '/', $rel), '/');
                    $full = $extractDir.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $rel);
                    if (! is_readable($full)) {
                        continue;
                    }

                    $orig = (string) ($att['original_name'] ?? basename($rel));
                    $mime = (string) ($att['mime_type'] ?? 'application/octet-stream');
                    $kind = (string) ($att['kind'] ?? 'other');
                    $size = (int) ($att['size_bytes'] ?? filesize($full));

                    $ext = strtolower(pathinfo($orig, PATHINFO_EXTENSION) ?: 'bin');
                    $storageKey = 'sa-map/projects/'.$project->id.'/'.Str::uuid()->toString().'.'.$ext;
                    Storage::disk('local')->put($storageKey, file_get_contents($full));

                    $attachment = Attachment::create([
                        'project_id' => $project->id,
                        'storage_key' => $storageKey,
                        'original_name' => strlen($orig) > 500 ? substr($orig, 0, 497).'…' : $orig,
                        'mime_type' => strlen($mime) > 127 ? substr($mime, 0, 127) : $mime,
                        'kind' => in_array($kind, ['scheme', 'png', 'document', 'other'], true) ? $kind : 'other',
                        'size_bytes' => $size,
                    ]);
                    $attachment->elements()->attach($element->id);
                }

                $created++;
            }

            return $created;
        });
    }

    private function findExistingForReplace(Project $project, int $level, string $artifactKey, string $titleNorm): ?Element
    {
        if ($titleNorm === '') {
            return null;
        }

        $q = Element::query()
            ->where('project_id', $project->id)
            ->where('level', $level)
            ->where('artifact_key', $artifactKey);

        foreach ($q->get() as $el) {
            $t = is_string($el->content['title'] ?? null) ? $el->content['title'] : '';
            if ($this->normTitle($t) === $titleNorm) {
                return $el;
            }
        }

        return null;
    }

    public function normTitle(string $title): string
    {
        $t = trim(preg_replace('/\s+/u', ' ', $title) ?? '');

        return $t;
    }

    /**
     * @return array{key: string, label: string, multiple: bool}|null
     */
    private function artifactDef(Project $project, int $level, string $artifactKey): ?array
    {
        $artifacts = config('sa_map.artifacts.'.$level);
        if (! is_array($artifacts)) {
            return null;
        }
        foreach ($artifacts as $row) {
            if (($row['key'] ?? null) === $artifactKey) {
                return $row;
            }
        }

        return null;
    }

    private function safeFilename(string $name, string $fallback): string
    {
        $base = basename($name);
        $base = preg_replace('/[^a-zA-Z0-9._\-\x{0400}-\x{04FF}]+/u', '_', $base) ?? $base;
        if ($base === '' || $base === '.' || $base === '..') {
            return 'file_'.$fallback;
        }

        return $base;
    }

    private function rrmdir(string $dir): void
    {
        if (! is_dir($dir)) {
            return;
        }
        $items = scandir($dir);
        if ($items === false) {
            return;
        }
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            $path = $dir.DIRECTORY_SEPARATOR.$item;
            if (is_dir($path)) {
                $this->rrmdir($path);
            } else {
                @unlink($path);
            }
        }
        @rmdir($dir);
    }
}
