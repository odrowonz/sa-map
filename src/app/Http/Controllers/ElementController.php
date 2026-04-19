<?php

namespace App\Http\Controllers;

use App\Models\Element;
use App\Models\Project;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ElementController extends Controller
{
    public function store(Request $request, Project $project): RedirectResponse
    {
        $this->authorize('view', $project);

        $validated = $request->validate([
            'level' => ['required', 'integer', 'min:1', 'max:10'],
            'artifact_key' => ['required', 'string', 'max:120'],
            'title' => ['nullable', 'string', 'max:500'],
            'body' => ['nullable', 'string', 'max:65535'],
            'include_in_export' => ['sometimes', 'boolean'],
            'upstream_element_ids' => ['nullable', 'array'],
            'upstream_element_ids.*' => ['uuid'],
        ]);

        $level = (int) $validated['level'];
        $artifactKey = $validated['artifact_key'];
        $def = $this->artifactDef($level, $artifactKey);
        if ($def === null) {
            abort(404);
        }

        $upstream = $this->normalizeUpstream($project, $level, $request->input('upstream_element_ids', []));

        $content = [
            'title' => $validated['title'] ?? '',
            'body' => $validated['body'] ?? '',
            'upstreamElementIds' => $upstream,
        ];

        if (! $def['multiple']) {
            $element = Element::firstOrNew([
                'project_id' => $project->id,
                'level' => $level,
                'artifact_key' => $artifactKey,
            ]);
            if (! $element->exists) {
                $element->id = (string) Str::uuid();
            }
            $element->fill([
                'content' => $content,
                'include_in_export' => $request->boolean('include_in_export', true),
                'sort_order' => 0,
            ]);
            $element->save();
        } else {
            $max = (int) Element::query()
                ->where('project_id', $project->id)
                ->where('level', $level)
                ->where('artifact_key', $artifactKey)
                ->max('sort_order');
            $row = new Element([
                'project_id' => $project->id,
                'level' => $level,
                'artifact_key' => $artifactKey,
                'content' => $content,
                'include_in_export' => $request->boolean('include_in_export', true),
                'sort_order' => $max + 1,
            ]);
            $row->id = (string) Str::uuid();
            $row->save();
        }

        return redirect()->to(route('projects.level', [$project, $level]).'#'.$this->fragmentId($artifactKey));
    }

    public function update(Request $request, Project $project, Element $element): RedirectResponse
    {
        $this->authorize('update', $element);
        abort_unless($element->project_id === $project->id, 404);

        $validated = $request->validate([
            'title' => ['nullable', 'string', 'max:500'],
            'body' => ['nullable', 'string', 'max:65535'],
            'include_in_export' => ['sometimes', 'boolean'],
            'upstream_element_ids' => ['nullable', 'array'],
            'upstream_element_ids.*' => ['uuid'],
        ]);

        $level = $element->level;
        $upstream = $this->normalizeUpstream($project, $level, $request->input('upstream_element_ids', []));

        $content = array_merge($element->content ?? [], [
            'title' => $validated['title'] ?? '',
            'body' => $validated['body'] ?? '',
            'upstreamElementIds' => $upstream,
        ]);

        $element->update([
            'content' => $content,
            'include_in_export' => $request->boolean('include_in_export', (bool) $element->include_in_export),
        ]);

        return redirect()->to(route('projects.level', [$project, $level]).'#'.$this->fragmentId($element->artifact_key));
    }

    public function destroy(Request $request, Project $project, Element $element): RedirectResponse
    {
        $this->authorize('delete', $element);
        abort_unless($element->project_id === $project->id, 404);

        $level = $element->level;
        $artifactKey = $element->artifact_key;
        $element->delete();

        return redirect()->to(route('projects.level', [$project, $level]).'#'.$this->fragmentId($artifactKey));
    }

    /**
     * @return array{key: string, label: string, multiple: bool}|null
     */
    private function artifactDef(int $level, string $artifactKey): ?array
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

    /**
     * @param  list<string>|null  $ids
     * @return list<string>
     */
    private function normalizeUpstream(Project $project, int $level, ?array $ids): array
    {
        if ($level <= 1 || $ids === null || $ids === []) {
            return [];
        }
        $ids = array_values(array_unique(array_filter($ids)));

        return Element::query()
            ->where('project_id', $project->id)
            ->where('level', $level - 1)
            ->whereIn('id', $ids)
            ->pluck('id')
            ->all();
    }

    private function fragmentId(string $artifactKey): string
    {
        return 'artifact-'.str_replace(['/', '\\', ' '], '-', $artifactKey);
    }
}
