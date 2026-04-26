<?php

namespace App\Http\Controllers;

use App\Models\Element;
use App\Models\NjkTemplate;
use App\Models\Project;
use App\Services\ProjectDataExchangeService;
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

        $artifacts = config('sa_map.artifacts.'.$level);
        if (! is_array($artifacts)) {
            $artifacts = [];
        }

        $elements = Element::query()
            ->where('project_id', $project->id)
            ->where('level', $level)
            ->with('attachments')
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

        $validArtifactKeys = collect($artifacts)->pluck('key')->all();
        $artifactFilter = $request->query('artifact');
        if (! is_string($artifactFilter) || ! in_array($artifactFilter, $validArtifactKeys, true)) {
            $artifactFilter = null;
        }

        $artifactFilterLabel = null;
        if ($artifactFilter !== null) {
            $match = collect($artifacts)->firstWhere('key', $artifactFilter);
            $artifactFilterLabel = is_array($match) ? ($match['label'] ?? $artifactFilter) : $artifactFilter;
        }

        $njkForExport = NjkTemplate::query()->orderByDesc('is_system')->orderBy('title');
        if (! $request->user()->isAdmin()) {
            $njkForExport->where(function ($q) use ($request): void {
                $q->where('is_system', true)
                    ->orWhere('user_id', $request->user()->id);
            });
        }
        $njkTemplatesForMdExport = $njkForExport->get(['id', 'title']);

        $elementWizardFlags = [];
        foreach (Element::query()
            ->where('project_id', $project->id)
            ->get(['id', 'include_in_export_data', 'include_in_export_md', 'include_in_import']) as $el) {
            $elementWizardFlags[(string) $el->id] = [
                'export_data' => (bool) $el->include_in_export_data,
                'export_md' => (bool) $el->include_in_export_md,
                'import' => (bool) $el->include_in_import,
            ];
        }

        return view('sa-map.project.level', [
            'project' => $project,
            'level' => $level,
            'levelMeta' => [
                'title' => __('sa.map_levels.'.$level.'.title'),
                'question' => __('sa.map_levels.'.$level.'.question'),
            ],
            'artifacts' => $artifacts,
            'artifactFilter' => $artifactFilter,
            'artifactFilterLabel' => $artifactFilterLabel,
            'elementsByArtifact' => $elementsByArtifact,
            'upstreamElements' => $upstreamElements,
            'njkTemplatesForMdExport' => $njkTemplatesForMdExport,
            'mdTemplateLegacyBridge' => ProjectDataExchangeService::mdTemplateLegacyBridge(),
            'elementWizardFlags' => $elementWizardFlags,
        ]);
    }
}
