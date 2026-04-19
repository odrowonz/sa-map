<?php

namespace App\Http\Controllers;

use App\Models\Element;
use App\Models\Project;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class ProjectWorkspaceController extends Controller
{
    public function show(Request $request, Project $project): RedirectResponse
    {
        $this->authorize('view', $project);

        return redirect()->route('projects.level', [$project, 1]);
    }

    public function level(Request $request, Project $project, int $level): View
    {
        $this->authorize('view', $project);
        abort_unless($level >= 1 && $level <= 10, 404);

        $levels = config('sa_map.levels');

        $artifacts = config('sa_map.artifacts.'.$level);
        if (! is_array($artifacts)) {
            $artifacts = [];
        }

        $elements = Element::query()
            ->where('project_id', $project->id)
            ->where('level', $level)
            ->orderBy('artifact_key')
            ->orderBy('sort_order')
            ->get();

        /** @var Collection<string, Collection<int, Element>> $elementsByArtifact */
        $elementsByArtifact = $elements->groupBy('artifact_key');

        $upstreamElements = $level > 1
            ? Element::query()
                ->where('project_id', $project->id)
                ->where('level', $level - 1)
                ->orderBy('artifact_key')
                ->orderBy('sort_order')
                ->get()
            : collect();

        return view('sa-map.project.level', [
            'project' => $project,
            'level' => $level,
            'levelMeta' => $levels[$level],
            'artifacts' => $artifacts,
            'elementsByArtifact' => $elementsByArtifact,
            'upstreamElements' => $upstreamElements,
        ]);
    }
}
