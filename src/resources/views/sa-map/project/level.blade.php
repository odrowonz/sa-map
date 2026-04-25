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

    $totalElementsDisplay = (int) collect($artifactsForDisplay)->sum('elementCount');
    $isArtifactFiltered = $artifactFilter !== null;
    $recordsWordDisplay = $totalElementsDisplay === 1 ? __('sa.level.record_one') : __('sa.level.records_many');
@endphp

@section('title')
    {{ $project->name }} · L{{ $level }}{{ $artifactFilterLabel ? ' — '.$artifactFilterLabel : '' }} — {{ config('app.name') }}
@endsection

@section('workspace_sidebar_heading_stat')
    {{ count($artifactsForUi) }}
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
            {{ __('sa.workspace.all_types') }}
        </a>
        @unless ($isArtifactFiltered)
            <div class="mt-3">
                <label for="artifact-filter" class="block text-[10px] font-bold uppercase tracking-wide text-slate-500">
                    {{ __('sa.level.filter_label') }}
                </label>
                <input
                    id="artifact-filter"
                    type="search"
                    name="artifact_filter"
                    autocomplete="off"
                    placeholder="{{ __('sa.level.filter_placeholder') }}"
                    class="mt-1.5 w-full rounded-lg border border-slate-600 bg-slate-900/60 px-3 py-2 text-xs text-slate-100 shadow-sm placeholder:text-slate-400 focus:border-blue-400 focus:ring-2 focus:ring-blue-400/30 focus:outline-none"
                />
            </div>
        @endunless
    </div>
@endsection

@section('workspace_toolbar')
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="min-w-0">
            <h1 class="truncate text-lg font-bold uppercase tracking-wide text-slate-800">{{ $project->name }}</h1>
            <p class="mt-0.5 text-xs text-slate-500">
                {{ __('sa.dashboard.updated') }} {{ $project->updated_at->translatedFormat('d.m.Y H:i') }}
                @if ($project->slug)
                    · <span class="font-mono text-slate-600">{{ $project->slug }}</span>
                @endif
            </p>
        </div>
        <div class="flex shrink-0 flex-wrap items-center gap-2">
            <form method="post" action="{{ route('projects.export-data', $project) }}" class="inline">
                @csrf
                <button
                    type="submit"
                    class="rounded-lg border border-slate-200 bg-white px-4 py-2 text-[10px] font-bold uppercase tracking-wider text-slate-600 shadow-sm hover:bg-slate-50"
                    title="{{ __('sa.project_data.export_title') }}"
                >
                    {{ __('sa.project_data.export') }}
                </button>
            </form>
            <form
                method="post"
                action="{{ route('projects.import-data', $project) }}"
                enctype="multipart/form-data"
                class="inline-flex flex-wrap items-center gap-2"
                data-confirm="{{ __('sa.project_data.import_confirm') }}"
                onsubmit="return confirm(this.dataset.confirm);"
            >
                @csrf
                <label class="cursor-pointer rounded-lg border border-slate-200 bg-white px-3 py-2 text-[10px] font-bold uppercase tracking-wider text-slate-600 shadow-sm hover:bg-slate-50">
                    <span>{{ __('sa.project_data.import_pick') }}</span>
                    <input type="file" name="archive" accept=".zip,application/zip" class="hidden" required onchange="var b=this.form.querySelector('[data-import-submit]'); if(b) b.removeAttribute('disabled');" />
                </label>
                <button
                    type="submit"
                    data-import-submit
                    disabled
                    class="rounded-lg border border-slate-800 bg-slate-800 px-4 py-2 text-[10px] font-bold uppercase tracking-wider text-white shadow-sm hover:bg-slate-700 disabled:cursor-not-allowed disabled:opacity-50"
                    title="{{ __('sa.project_data.import_title') }}"
                >
                    {{ __('sa.project_data.import') }}
                </button>
            </form>
        </div>
    </div>
@endsection

@section('workspace_level_tabs')
    <div class="flex overflow-x-auto" role="tablist" aria-label="{{ __('sa.level.tablist_aria') }}">
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
                    {!! __('sa.level.filtered_banner', ['name' => e($artifactFilterLabel)]) !!}
                    · <span class="tabular-nums">{{ $totalElementsDisplay }}</span> {{ $recordsWordDisplay }}
                </p>
                <a
                    href="{{ route('projects.level', [$project, $level]) }}"
                    class="shrink-0 rounded-lg bg-white px-3 py-1.5 text-sm font-semibold text-slate-800 shadow-sm ring-1 ring-slate-200 hover:bg-slate-50"
                >
                    {{ __('sa.level.all_types_level') }}
                </a>
            </div>
        @endif

        <article @if ($isArtifactFiltered) data-artifact-filter="{{ $artifactFilter }}" @endif class="border-t border-slate-200/80 pt-6">
            <header class="mb-8 max-w-4xl">
                <span class="inline-flex items-center rounded-full bg-blue-100 px-3 py-1 text-[10px] font-bold uppercase tracking-widest text-blue-800">
                    {{ __('sa.level.level_badge', ['n' => $level]) }}
                </span>
                <h2 class="mt-3 text-2xl font-extrabold tracking-tight text-slate-900 sm:text-3xl">
                    {{ $levelMeta['title'] }}
                </h2>
                <p class="mt-2 text-lg italic text-slate-500">
                    {{ $levelMeta['question'] }}
                </p>
            </header>

            <p class="mb-8 max-w-4xl text-sm leading-relaxed text-slate-600">
                {!! __('sa.level.artifacts_intro', ['table' => '<code class="rounded-md bg-slate-200/60 px-1.5 py-0.5 font-mono text-xs text-slate-800">sa_elements</code>']) !!}
                @if ($level === 1)
                    {{ __('sa.level.trace_l1') }}
                @else
                    {{ __('sa.level.trace_ln', ['n' => $level - 1]) }}
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
