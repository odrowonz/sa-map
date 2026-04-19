<?php

namespace App\Http\Controllers;

use App\Models\Project;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ProjectController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:500'],
        ]);

        $user = $request->user();
        $base = Str::slug($validated['name']);
        if ($base === '') {
            $base = 'project';
        }
        $slug = $base;
        $suffix = 0;
        while ($user->projects()->where('slug', $slug)->exists()) {
            $suffix++;
            $slug = $base.'-'.$suffix;
        }

        $user->projects()->create([
            'name' => $validated['name'],
            'slug' => $slug,
        ]);

        return redirect()->to(route('dashboard').'#projects')->with('status', 'Проект создан.');
    }

    public function destroy(Request $request, Project $project): RedirectResponse
    {
        $this->authorize('delete', $project);

        $project->delete();

        return redirect()->to(route('dashboard').'#projects')->with('status', 'Проект удалён.');
    }
}
