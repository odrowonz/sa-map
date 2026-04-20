@extends('layouts.sa_workspace')

@php
    $artifactsForUi = collect($artifacts)->map(function (array $artifact) use ($elementsByArtifact) {
        $rows = $elementsByArtifact->get($artifact['key'], collect());
        $count = $artifact['multiple'] ? $rows->count() : ($rows->isNotEmpty() ? 1 : 0);

        return array_merge($artifact, ['elementCount' => $count]);
    })->all();

    $artifactsForDisplay = $artifactFilter !== null
        ? array_values(array_filter(
            $artifactsForUi,
            fn (array $a): bool => $a['key'] === $artifactFilter
        ))
        : $artifactsForUi;

    $totalElementsUi = (int) collect($artifactsForUi)->sum('elementCount');
    $totalElementsDisplay = (int) collect($artifactsForDisplay)->sum('elementCount');
    $isArtifactFiltered = $artifactFilter !== null;
@endphp

@section('title')
    {{ $project->name }} · L{{ $level }}{{ $artifactFilterLabel ? ' — '.$artifactFilterLabel : '' }} — {{ config('app.name') }}
@endsection

@section('workspace_sidebar')
    <div class="space-y-0.5">
        @foreach ($artifactsForUi as $a)
            @php
                $tocActive = $isArtifactFiltered && $artifactFilter === $a['key'];
                $tocHref = route('projects.level', [$project, $level, 'artifact' => $a['key']]);
            @endphp
            <a
                href="{{ $tocHref }}"
                data-toc-key="{{ $a['key'] }}"
                data-artifact-toc-link
                class="artifact-toc-link flex w-full items-center justify-between gap-2 rounded-lg border-l-4 px-3 py-2.5 text-left text-xs font-semibold transition-colors
                    @if ($tocActive)
                        border-blue-500 bg-slate-700 text-white shadow-lg
                    @else
                        border-transparent text-slate-300 hover:bg-slate-700/60
                    @endif
                "
            >
                <span class="min-w-0 truncate" title="{{ $a['label'] }}">{{ $a['label'] }}</span>
                <span class="shrink-0 tabular-nums text-[10px] opacity-80 {{ $tocActive ? 'text-slate-200' : 'text-slate-500' }}">{{ $a['elementCount'] }}</span>
            </a>
        @endforeach
    </div>
    <div class="mt-4 border-t border-slate-700 pt-3">
        <a href="{{ route('projects.level', [$project, $level]) }}" class="text-xs font-semibold text-blue-400 hover:text-blue-300">
            Все типы сразу
        </a>
    </div>
@endsection

@section('workspace_toolbar')
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="min-w-0">
            <h1 class="truncate text-lg font-bold uppercase tracking-wide text-slate-800">{{ $project->name }}</h1>
            <p class="mt-0.5 text-xs text-slate-500">
                Обновлён {{ $project->updated_at->translatedFormat('d.m.Y H:i') }}
                @if ($project->slug)
                    · <span class="font-mono text-slate-600">{{ $project->slug }}</span>
                @endif
            </p>
        </div>
        <div class="flex shrink-0 flex-wrap items-center gap-2">
            <button
                type="button"
                disabled
                class="cursor-not-allowed rounded-lg border border-slate-200 bg-slate-50 px-4 py-2 text-[10px] font-bold uppercase tracking-wider text-slate-400"
                title="Выгрузка JSON — в разработке"
            >
                JSON
            </button>
        </div>
    </div>
@endsection

@section('workspace_level_tabs')
    <div class="flex overflow-x-auto" role="tablist" aria-label="Уровни карты L1–L10">
        @foreach (range(1, 10) as $n)
            <a
                href="{{ route('projects.level', [$project, $n]) }}"
                role="tab"
                @if ($n === $level) aria-selected="true" @else aria-selected="false" @endif
                class="min-w-[3.25rem] shrink-0 border-b-4 px-3 py-3 text-center text-[10px] font-bold uppercase tracking-widest transition-colors sm:min-w-[3.5rem] sm:px-4
                    @if ($n === $level)
                        border-blue-500 bg-blue-50/95 text-blue-700
                    @else
                        border-transparent text-slate-500 hover:bg-slate-50 hover:text-slate-800
                    @endif
                "
            >
                L{{ $n }}
            </a>
        @endforeach
    </div>
@endsection

@section('workspace_main')
    <div class="px-4 py-6 sm:px-8 lg:px-10">
        @if ($isArtifactFiltered)
            <div class="mb-6 flex flex-wrap items-center justify-between gap-3 rounded-xl border border-sky-200 bg-sky-50 px-4 py-3 text-sm text-sky-950">
                <p class="min-w-0">
                    Показан только тип <strong>{{ $artifactFilterLabel }}</strong>
                    · <span class="tabular-nums">{{ $totalElementsDisplay }}</span> {{ $totalElementsDisplay === 1 ? 'запись' : 'записей' }}
                </p>
                <a
                    href="{{ route('projects.level', [$project, $level]) }}"
                    class="shrink-0 rounded-lg bg-white px-3 py-1.5 text-sm font-semibold text-slate-800 shadow-sm ring-1 ring-slate-200 hover:bg-slate-50"
                >
                    Все типы уровня
                </a>
            </div>
        @endif

        <div class="mb-8 flex flex-col gap-4 border-b border-slate-200/80 pb-6 sm:flex-row sm:items-end sm:justify-between">
            @unless ($isArtifactFiltered)
                <div class="min-w-0 flex-1 max-w-md">
                    <label for="artifact-filter" class="block text-[10px] font-bold uppercase tracking-wide text-slate-500">Фильтр по типу</label>
                    <input
                        id="artifact-filter"
                        type="search"
                        name="artifact_filter"
                        autocomplete="off"
                        placeholder="Ключ или название…"
                        class="mt-1.5 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-800 shadow-sm placeholder:text-slate-400 focus:border-blue-400 focus:ring-2 focus:ring-blue-400/30 focus:outline-none"
                    />
                </div>
            @endunless
            <p class="text-xs leading-relaxed text-slate-600 sm:text-right {{ $isArtifactFiltered ? 'sm:text-left' : '' }}" id="artifact-level-summary">
                @if ($isArtifactFiltered)
                    <span class="font-bold uppercase tracking-wide text-slate-400">На уровне всего</span>
                    <span class="mt-1 block sm:mt-0 sm:inline">
                        <strong class="text-slate-900">{{ count($artifactsForUi) }}</strong> типов,
                        <strong class="text-slate-900">{{ $totalElementsUi }}</strong> записей
                    </span>
                @else
                    <span class="font-bold uppercase tracking-wide text-slate-400">На уровне</span>
                    <span class="mt-1 block sm:mt-0 sm:inline">
                        <strong class="text-slate-900">{{ count($artifactsForUi) }}</strong> типов,
                        <strong class="text-slate-900">{{ $totalElementsUi }}</strong> записей
                    </span>
                @endif
            </p>
        </div>

        <article @if ($isArtifactFiltered) data-artifact-filter="{{ $artifactFilter }}" @endif>
            <header class="mb-8 max-w-4xl">
                <span class="inline-flex items-center rounded-full bg-blue-100 px-3 py-1 text-[10px] font-bold uppercase tracking-widest text-blue-800">
                    Уровень {{ $level }}
                </span>
                <h2 class="mt-3 text-2xl font-extrabold tracking-tight text-slate-900 sm:text-3xl">
                    {{ $levelMeta['title'] }}
                </h2>
                <p class="mt-2 text-lg italic text-slate-500">
                    {{ $levelMeta['question'] }}
                </p>
            </header>

            <p class="mb-8 max-w-4xl text-sm leading-relaxed text-slate-600">
                Артефакты уровня — по ТЗ п. 12; данные в <code class="rounded-md bg-slate-200/60 px-1.5 py-0.5 font-mono text-xs text-slate-800">sa_elements</code>.
                @if ($level === 1)
                    Трассировка на предыдущий уровень для L1 не задаётся (п. 5.3.2).
                @else
                    Связь с элементами <strong>L{{ $level - 1 }}</strong> — поле «Основание на L{{ $level - 1 }}».
                @endif
            </p>

            @include('sa-map.project.partials.artifact-forms', [
                'project' => $project,
                'level' => $level,
                'artifacts' => $artifactsForDisplay,
                'elementsByArtifact' => $elementsByArtifact,
                'upstreamElements' => $upstreamElements,
            ])
        </article>
    </div>
@endsection

@push('scripts')
    <script>
        (function () {
            var article = document.querySelector('#workspace-main-scroll article[data-artifact-filter]');
            var filteredMode = article && article.getAttribute('data-artifact-filter');
            var input = document.getElementById('artifact-filter');
            var sections = document.querySelectorAll('[data-artifact-section]');
            var tocLinks = document.querySelectorAll('[data-artifact-toc-link]');
            var scrollRoot = document.getElementById('workspace-main-scroll');

            function applyFilter() {
                if (filteredMode) return;
                var q = (input && input.value ? input.value : '').trim().toLowerCase();
                sections.forEach(function (sec) {
                    var key = (sec.getAttribute('data-artifact-key') || '').toLowerCase();
                    var label = sec.getAttribute('data-artifact-label') || '';
                    var show = !q || key.indexOf(q) !== -1 || label.indexOf(q) !== -1;
                    sec.classList.toggle('hidden', !show);
                });
                tocLinks.forEach(function (link) {
                    var k = link.getAttribute('data-toc-key');
                    if (!k) return;
                    var sec = document.getElementById('artifact-' + k);
                    var show = sec && !sec.classList.contains('hidden');
                    link.classList.toggle('hidden', !show);
                    link.setAttribute('aria-hidden', show ? 'false' : 'true');
                });
            }

            if (input) {
                input.addEventListener('input', applyFilter);
            }

            function clearSpy() {
                tocLinks.forEach(function (a) {
                    a.classList.remove('spy-active-toc');
                });
            }

            function setActiveToc(id) {
                if (filteredMode) return;
                var key =
                    id.indexOf('artifact-') === 0 ? id.slice('artifact-'.length) : '';
                if (!key) return;
                clearSpy();
                tocLinks.forEach(function (a) {
                    var k = a.getAttribute('data-toc-key') || '';
                    var on = k === key;
                    if (on) {
                        a.classList.add('spy-active-toc');
                    }
                    a.setAttribute('aria-current', on ? 'location' : 'false');
                });
            }

            if (!filteredMode && scrollRoot) {
                var observer = new IntersectionObserver(
                    function (entries) {
                        var visible = entries
                            .filter(function (e) {
                                return e.isIntersecting && !e.target.classList.contains('hidden');
                            })
                            .sort(function (a, b) {
                                return b.intersectionRatio - a.intersectionRatio;
                            });
                        if (visible.length && visible[0].target.id) {
                            setActiveToc(visible[0].target.id);
                        }
                    },
                    {
                        root: scrollRoot,
                        rootMargin: '-28% 0px -28% 0px',
                        threshold: [0, 0.1, 0.25, 0.5, 1],
                    }
                );
                sections.forEach(function (s) {
                    observer.observe(s);
                });
            }

            document.addEventListener('DOMContentLoaded', function () {
                var hash = window.location.hash;
                if (!hash || hash.length < 2) return;
                if (filteredMode) return;
                var id = hash.slice(1);
                if (!id || id.indexOf('artifact-') !== 0) return;
                var node = document.getElementById(id);
                if (node && scrollRoot) {
                    requestAnimationFrame(function () {
                        node.scrollIntoView({ behavior: 'smooth', block: 'start' });
                    });
                }
            });
        })();
    </script>
@endpush
