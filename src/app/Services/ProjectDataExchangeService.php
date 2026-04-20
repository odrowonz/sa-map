<?php

namespace App\Services;

use App\Models\Attachment;
use App\Models\Element;
use App\Models\Project;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;
use ZipArchive;

class ProjectDataExchangeService
{
    public const JSON_NAME = 'sa-map-project-export.json';

    public const FORMAT = 'sa-map-project-export';

    public const VERSION = 1;

    public function exportZipResponse(Project $project): StreamedResponse
    {
        $tmp = tempnam(sys_get_temp_dir(), 'sampexp');
        if ($tmp === false) {
            throw new RuntimeException('Cannot create temp file');
        }
        @unlink($tmp);
        $tmpZip = $tmp.'.zip';

        [$manifest, $zipFileEntries] = $this->buildExportManifest($project);

        $zip = new ZipArchive;
        if ($zip->open($tmpZip, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('Cannot create ZIP');
        }

        $zip->addFromString(self::JSON_NAME, json_encode($manifest, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));

        foreach ($zipFileEntries as $entry) {
            if (! empty($entry['storage_key']) && Storage::disk('local')->exists($entry['storage_key'])) {
                $zip->addFile(Storage::disk('local')->path($entry['storage_key']), $entry['zip_path']);
            }
        }

        $zip->close();

        $safeSlug = $project->slug ? preg_replace('/[^a-zA-Z0-9._-]+/', '-', $project->slug) : 'project';
        $downloadName = 'sa-map-export-'.$safeSlug.'-'.now()->format('Y-m-d-His').'.zip';

        return response()->streamDownload(function () use ($tmpZip): void {
            readfile($tmpZip);
            @unlink($tmpZip);
        }, $downloadName, [
            'Content-Type' => 'application/zip',
        ]);
    }

    /**
     * @return array{0: array<string, mixed>, 1: list<array{zip_path: string, storage_key: string}>}
     */
    private function buildExportManifest(Project $project): array
    {
        $elements = Element::query()
            ->where('project_id', $project->id)
            ->where('include_in_export', true)
            ->with(['attachments'])
            ->orderBy('level')
            ->orderBy('sort_order')
            ->get();

        $out = [];
        $zipFileEntries = [];

        foreach ($elements as $el) {
            $attachments = [];
            foreach ($el->attachments as $att) {
                $safeBase = $this->safeFilename($att->original_name, (string) $att->id);
                $zipPath = 'files/'.$el->id.'/'.$att->id.'_'.$safeBase;
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
                'include_in_export' => (bool) $el->include_in_export,
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

        return [$manifest, $zipFileEntries];
    }

    public function importFromZip(Project $project, string $zipAbsolutePath): int
    {
        $zip = new ZipArchive;
        if ($zip->open($zipAbsolutePath) !== true) {
            throw new RuntimeException('Cannot open ZIP');
        }

        $json = $zip->getFromName(self::JSON_NAME);
        if ($json === false) {
            $zip->close();
            throw new RuntimeException('Missing '.self::JSON_NAME.' in archive');
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
            $count = $this->importElements($project, $elements, $extractDir);
        } finally {
            $this->rrmdir($extractDir);
        }

        return $count;
    }

    /**
     * @param  list<array<string, mixed>>  $elements
     */
    private function importElements(Project $project, array $elements, string $extractDir): int
    {
        usort($elements, function ($a, $b) {
            $la = (int) ($a['level'] ?? 0);
            $lb = (int) ($b['level'] ?? 0);
            if ($la !== $lb) {
                return $la <=> $lb;
            }

            return ((int) ($a['sort_order'] ?? 0)) <=> ((int) ($b['sort_order'] ?? 0));
        });

        return (int) DB::transaction(function () use ($project, $elements, $extractDir) {
            $idMap = [];
            $created = 0;

            foreach ($elements as $row) {
                $level = (int) ($row['level'] ?? 0);
                $artifactKey = (string) ($row['artifact_key'] ?? '');
                if ($level < 1 || $level > 10 || $artifactKey === '') {
                    continue;
                }

                $def = $this->artifactDef($project, $level, $artifactKey);
                if ($def === null) {
                    continue;
                }

                $content = is_array($row['content'] ?? null) ? $row['content'] : [];
                $titleNorm = $this->normTitle(is_string($content['title'] ?? null) ? $content['title'] : '');
                $incomingOldId = (string) ($row['id'] ?? '');

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
                    'include_in_export' => array_key_exists('include_in_export', $row)
                        ? (bool) $row['include_in_export']
                        : true,
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
