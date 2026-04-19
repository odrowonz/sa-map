@extends('layouts.app')

@section('main_width', 'max-w-6xl')

@section('title', $project->name . ' · L' . $level . ' — ' . config('app.name'))

@section('content')
    <nav class="mb-6 text-sm text-slate-600" aria-label="Навигация">
        <a href="{{ route('dashboard') }}#projects" class="text-slate-800 underline hover:text-slate-600">Личный кабинет</a>
        <span class="mx-1 text-slate-400">/</span>
        <span class="font-medium text-slate-800">{{ $project->name }}</span>
    </nav>

    <header class="border-b border-slate-200 pb-4">
        <h1 class="text-xl font-semibold text-slate-800">{{ $project->name }}</h1>
        <p class="mt-1 text-xs text-slate-500">
            Обновлён {{ $project->updated_at->translatedFormat('d.m.Y H:i') }}
            @if ($project->slug)
                · <span class="font-mono">{{ $project->slug }}</span>
            @endif
        </p>
    </header>

    <div class="mt-4" role="tablist" aria-label="Уровни карты L1–L10">
        <p class="mb-2 text-xs font-medium uppercase tracking-wide text-slate-500">Уровни</p>
        <div class="-mx-1 flex gap-1 overflow-x-auto pb-2">
            @foreach (range(1, 10) as $n)
                <a
                    href="{{ route('projects.level', [$project, $n]) }}"
                    role="tab"
                    @if ($n === $level) aria-selected="true" @else aria-selected="false" @endif
                    class="shrink-0 rounded-md px-2.5 py-1.5 text-sm font-medium whitespace-nowrap
                        @if ($n === $level)
                            bg-slate-800 text-white
                        @else
                            bg-white text-slate-700 ring-1 ring-slate-200 hover:bg-slate-50
                        @endif
                    "
                >
                    L{{ $n }}
                </a>
            @endforeach
        </div>
    </div>

    <article class="mt-8 rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
        <p class="text-xs font-semibold tracking-wide text-slate-500 uppercase">Уровень {{ $level }}</p>
        <h2 class="mt-1 text-lg font-semibold text-slate-800">{{ $levelMeta['title'] }}</h2>
        <blockquote class="mt-3 border-l-4 border-amber-400 bg-amber-50/80 py-2 pr-4 pl-4 text-sm text-slate-700 italic">
            {{ $levelMeta['question'] }}
        </blockquote>

        <p class="mt-6 text-sm text-slate-600">
            Артефакты уровня — по ТЗ п. 12; данные в <code class="rounded bg-slate-100 px-1 text-xs">sa_elements</code>.
            @if ($level === 1)
                Трассировка на предыдущий уровень для L1 не задаётся (п. 5.3.2).
            @else
                Связь с элементами <strong>L{{ $level - 1 }}</strong> — поле «Основание на L{{ $level - 1 }}».
            @endif
        </p>

        @include('sa-map.project.partials.artifact-forms', [
            'project' => $project,
            'level' => $level,
            'artifacts' => $artifacts,
            'elementsByArtifact' => $elementsByArtifact,
            'upstreamElements' => $upstreamElements,
        ])
    </article>
@endsection
