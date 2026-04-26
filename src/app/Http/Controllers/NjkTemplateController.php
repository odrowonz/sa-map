<?php

namespace App\Http\Controllers;

use App\Models\NjkTemplate;
use App\Models\Project;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class NjkTemplateController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        $systemTemplates = NjkTemplate::query()
            ->where('is_system', true)
            ->orderBy('title')
            ->get();

        $userTemplatesQuery = NjkTemplate::query()
            ->where('is_system', false)
            ->with(['user:id,email'])
            ->orderBy('user_id')
            ->orderBy('title');

        if (! $user->isAdmin()) {
            $userTemplatesQuery->where('user_id', $user->id);
        }

        return view('sa-map.njk.index', [
            'systemTemplates' => $systemTemplates,
            'userTemplates' => $userTemplatesQuery->get(),
        ]);
    }

    public function show(Request $request, NjkTemplate $njkTemplate): View
    {
        $this->authorize('view', $njkTemplate);

        return view('sa-map.njk.show', [
            'template' => $njkTemplate,
        ]);
    }

    public function create(): View
    {
        return view('sa-map.njk.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $rules = [
            'title' => ['required', 'string', 'max:255'],
            'filename' => ['required', 'string', 'max:255', 'regex:/\.njk$/i'],
            'body' => ['required', 'string'],
        ];
        if ($request->user()->isAdmin()) {
            $rules['is_system'] = ['sometimes', 'boolean'];
        }
        $validated = $request->validate($rules);

        $title = $validated['title'];
        $filename = basename($validated['filename']);
        $body = $validated['body'];
        $asSystem = $request->user()->isAdmin() && $request->boolean('is_system');

        if ($asSystem) {
            NjkTemplate::query()->create([
                'user_id' => null,
                'title' => $title,
                'filename' => $filename,
                'body' => $body,
                'is_system' => true,
            ]);
        } else {
            $request->user()->njkTemplates()->create([
                'title' => $title,
                'filename' => $filename,
                'body' => $body,
                'is_system' => false,
            ]);
        }

        return redirect()->route('njk-templates.index')
            ->with('status', __('sa.njk.flash_created'));
    }

    public function edit(Request $request, NjkTemplate $njkTemplate): View
    {
        $this->authorize('update', $njkTemplate);

        return view('sa-map.njk.edit', [
            'template' => $njkTemplate,
        ]);
    }

    public function update(Request $request, NjkTemplate $njkTemplate): RedirectResponse
    {
        $this->authorize('update', $njkTemplate);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'filename' => ['required', 'string', 'max:255', 'regex:/\.njk$/i'],
            'body' => ['required', 'string'],
        ]);

        $njkTemplate->update([
            'title' => $validated['title'],
            'filename' => basename($validated['filename']),
            'body' => $validated['body'],
        ]);

        return redirect()->route('njk-templates.index')
            ->with('status', __('sa.njk.flash_updated'));
    }

    public function destroy(Request $request, NjkTemplate $njkTemplate): RedirectResponse
    {
        $this->authorize('delete', $njkTemplate);

        $njkTemplate->delete();

        return redirect()->route('njk-templates.index')
            ->with('status', __('sa.njk.flash_deleted'));
    }

    public function exportBody(Request $request, Project $project, NjkTemplate $njk_template): JsonResponse
    {
        $this->authorize('view', $project);
        $this->authorize('view', $njk_template);

        return response()->json([
            'body' => $njk_template->body,
            'filename' => $njk_template->filename,
        ]);
    }

    public function download(Request $request, NjkTemplate $njkTemplate): Response
    {
        $this->authorize('view', $njkTemplate);

        $filename = $njkTemplate->filename;
        if (! str_ends_with(strtolower($filename), '.njk')) {
            $filename .= '.njk';
        }

        return response($njkTemplate->body, 200, [
            'Content-Type' => 'text/plain; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }
}
