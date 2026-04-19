<?php

namespace App\Http\Controllers;

use App\Models\Attachment;
use App\Models\Element;
use App\Models\Project;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class AttachmentController extends Controller
{
    public function store(Request $request, Project $project, Element $element): RedirectResponse
    {
        $this->authorize('view', $project);
        abort_unless($element->project_id === $project->id, 404);

        $validated = $request->validate([
            'file' => ['required', 'file', 'max:20480'],
        ]);

        $file = $validated['file'];
        $mime = $file->getMimeType() ?: 'application/octet-stream';
        $ext = strtolower($file->getClientOriginalExtension() ?: 'bin');
        $kind = $this->detectKind($mime, $ext);

        $originalName = $file->getClientOriginalName();
        if (strlen($originalName) > 500) {
            $originalName = substr($originalName, 0, 497).'…';
        }

        $storageKey = 'sa-map/projects/'.$project->id.'/'.Str::uuid()->toString().'.'.$ext;

        Storage::disk('local')->put($storageKey, file_get_contents($file->getRealPath()));

        $attachment = Attachment::create([
            'project_id' => $project->id,
            'storage_key' => $storageKey,
            'original_name' => $originalName,
            'mime_type' => $mime,
            'kind' => $kind,
            'size_bytes' => $file->getSize(),
        ]);

        $attachment->elements()->attach($element->id);

        $frag = '#artifact-'.$element->artifact_key;

        return redirect()->to(route('projects.level', [$project, $element->level]).$frag)
            ->with('status', 'Файл загружен.');
    }

    public function file(Request $request, Project $project, Attachment $attachment): BinaryFileResponse
    {
        abort_unless($attachment->project_id === $project->id, 404);
        $this->authorize('view', $attachment);

        if (! Storage::disk('local')->exists($attachment->storage_key)) {
            abort(404);
        }

        return response()->file(Storage::disk('local')->path($attachment->storage_key), [
            'Content-Type' => $attachment->mime_type ?: 'application/octet-stream',
        ]);
    }

    public function destroy(Request $request, Project $project, Attachment $attachment): RedirectResponse
    {
        abort_unless($attachment->project_id === $project->id, 404);
        $this->authorize('delete', $attachment);

        $validated = $request->validate([
            'element' => ['required', 'uuid'],
        ]);

        $element = Element::query()
            ->where('project_id', $project->id)
            ->whereKey($validated['element'])
            ->firstOrFail();

        abort_unless($attachment->elements()->whereKey($element->id)->exists(), 404);

        Storage::disk('local')->delete($attachment->storage_key);
        $attachment->delete();

        $frag = '#artifact-'.$element->artifact_key;

        return redirect()->to(route('projects.level', [$project, $element->level]).$frag)
            ->with('status', 'Вложение удалено.');
    }

    private function detectKind(string $mime, string $ext): string
    {
        if (str_starts_with($mime, 'image/')) {
            return 'png';
        }

        $scheme = ['bpmn', 'drawio', 'uml', 'xmi', 'xml', 'svg'];
        if (in_array($ext, $scheme, true)) {
            return 'scheme';
        }

        $doc = ['pdf', 'doc', 'docx', 'odt', 'md', 'txt', 'rtf'];
        if (in_array($ext, $doc, true)) {
            return 'document';
        }

        return 'other';
    }
}
