<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Services\ProjectDataExchangeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class ProjectDataExchangeController extends Controller
{
    public function exportWizardTree(Project $project): JsonResponse
    {
        $this->authorize('view', $project);

        return response()->json(ProjectDataExchangeService::exportWizardTree($project));
    }

    public function export(Request $request, Project $project, ProjectDataExchangeService $service): StreamedResponse
    {
        $this->authorize('view', $project);

        $elementIds = null;
        if ($request->exists('element_ids')) {
            $elementIds = self::parseUuidListInput($request->input('element_ids'));
        }

        return $service->exportZipResponse($project, $request->boolean('md'), $elementIds);
    }

    public function import(Request $request, Project $project, ProjectDataExchangeService $service): RedirectResponse
    {
        $this->authorize('update', $project);

        $request->validate([
            'archive' => ['required', 'file', 'max:51200', 'mimes:zip'],
        ]);

        $file = $request->file('archive');
        if ($file === null) {
            return redirect()->back()->with('status', __('sa.project_data.import_failed'));
        }

        $tmp = $file->getRealPath();
        if ($tmp === false || ! is_readable($tmp)) {
            return redirect()->back()->with('status', __('sa.project_data.import_failed'));
        }

        $onlySourceElementIds = null;
        if ($request->exists('import_element_ids')) {
            $onlySourceElementIds = self::parseUuidListInput($request->input('import_element_ids'));
        }

        $importManifestAllIds = null;
        if ($request->exists('import_manifest_element_ids')) {
            $importManifestAllIds = self::parseUuidListInput($request->input('import_manifest_element_ids'));
        }

        try {
            $result = $service->importFromZip($project, $tmp, $file->getClientOriginalName(), $onlySourceElementIds);
        } catch (Throwable $e) {
            report($e);

            return redirect()->back()->with('status', __('sa.project_data.import_error').' '.$e->getMessage());
        }

        if ($onlySourceElementIds !== null) {
            $service->rememberWizardSelection($project, 'import_data', $onlySourceElementIds, $importManifestAllIds);
        }

        $project->touch();

        $msgKey = ($result['bundle'] ?? 'data') === 'data'
            ? 'sa.project_data.imported_data'
            : 'sa.project_data.imported_md';

        return redirect()->back()->with('status', __($msgKey, ['count' => (int) ($result['count'] ?? 0)]));
    }

    /**
     * @param  mixed  $raw
     * @return list<string>
     */
    private static function parseUuidListInput(mixed $raw): array
    {
        if (is_string($raw)) {
            $decoded = json_decode($raw, true);
            $raw = is_array($decoded) ? $decoded : [];
        }
        if (! is_array($raw)) {
            return [];
        }

        return array_values(array_filter($raw, fn ($id) => is_string($id) && Str::isUuid($id)));
    }
}
