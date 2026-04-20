<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Services\ProjectDataExchangeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class ProjectDataExchangeController extends Controller
{
    public function export(Request $request, Project $project, ProjectDataExchangeService $service): StreamedResponse
    {
        $this->authorize('view', $project);

        return $service->exportZipResponse($project);
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

        try {
            $count = $service->importFromZip($project, $tmp);
        } catch (Throwable $e) {
            report($e);

            return redirect()->back()->with('status', __('sa.project_data.import_error').' '.$e->getMessage());
        }

        $project->touch();

        return redirect()->back()->with('status', __('sa.project_data.imported', ['count' => $count]));
    }
}
