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

    $isArtifactFiltered = $artifactFilter !== null;
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
                <span class="ml-auto inline-flex shrink-0 items-center gap-1">
                    @if ($isArtifactFiltered && $tocActive)
                        <span class="inline-flex h-4 w-4 items-center justify-center text-blue-200" title="{{ __('sa.level.filter_label') }}" aria-label="{{ __('sa.level.filter_label') }}">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-3.5 w-3.5" aria-hidden="true">
                                <path d="M2.5 4.75A.75.75 0 0 1 3.25 4h13.5a.75.75 0 0 1 .53 1.28l-5.22 5.22a.75.75 0 0 0-.22.53V15.5a.75.75 0 0 1-1.06.69l-2-1A.75.75 0 0 1 8.5 14.5v-3.47a.75.75 0 0 0-.22-.53L3.06 5.28a.75.75 0 0 1-.56-.53Z" />
                            </svg>
                        </span>
                    @endif
                    <span class="tabular-nums text-[10px] opacity-80 {{ $tocActive ? 'text-slate-200' : 'text-slate-500' }}">{{ $a['elementCount'] }}</span>
                </span>
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
        <article @if ($isArtifactFiltered) data-artifact-filter="{{ $artifactFilter }}" @endif class="pt-2">
            <header class="mb-6 mx-auto max-w-4xl">
                <div class="flex flex-wrap items-center gap-2 sm:gap-3">
                    <span class="inline-flex shrink-0 items-center rounded-full bg-blue-100 px-2.5 py-0.5 text-[10px] font-bold uppercase tracking-widest text-blue-800">
                        {{ __('sa.level.level_badge', ['n' => $level]) }}
                    </span>
                    <div class="relative min-w-0" data-level-question-wrap>
                        <h2 class="min-w-0 text-xl font-extrabold leading-tight tracking-tight text-slate-900 sm:text-2xl">
                            {{ $levelMeta['title'] }}
                            <span
                                id="level-question-opener"
                                class="inline-flex cursor-pointer select-none align-super text-slate-400 transition hover:text-slate-700 focus:outline-none"
                                data-level-question-btn
                                aria-expanded="false"
                                aria-controls="level-question-hint"
                                role="button"
                                tabindex="0"
                                title="{{ __('sa.level.question_aria') }}"
                            >
                                <span class="sr-only">{{ __('sa.level.question_aria') }}</span>
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="h-3.5 w-3.5 -translate-y-0.5" aria-hidden="true">
                                    <path
                                        d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-6h2v6zm0-8h-2V7h2v2z"
                                    />
                                </svg>
                            </span>
                        </h2>
                        <div
                            id="level-question-hint"
                            class="absolute right-0 top-full z-30 mt-2 hidden w-[min(100vw-2rem,20rem)] rounded-xl border border-slate-200 bg-white p-4 text-sm leading-relaxed text-slate-700 shadow-lg"
                            data-level-question-panel
                            role="region"
                            aria-labelledby="level-question-opener"
                        >
                            <p class="m-0 text-slate-600 italic">{{ $levelMeta['question'] }}</p>
                        </div>
                    </div>
                </div>
            </header>

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

            var levelQWrap = document.querySelector('[data-level-question-wrap]');
            if (levelQWrap) {
                var levelQBtn = levelQWrap.querySelector('[data-level-question-btn]');
                var levelQPanel = document.getElementById('level-question-hint');
                function levelQSet(isOpen) {
                    if (!levelQPanel || !levelQBtn) {
                        return;
                    }
                    if (isOpen) {
                        levelQPanel.classList.remove('hidden');
                        levelQBtn.setAttribute('aria-expanded', 'true');
                    } else {
                        levelQPanel.classList.add('hidden');
                        levelQBtn.setAttribute('aria-expanded', 'false');
                    }
                }
                if (levelQBtn && levelQPanel) {
                    levelQBtn.addEventListener('click', function (e) {
                        e.stopPropagation();
                        var open = !levelQPanel.classList.contains('hidden');
                        levelQSet(!open);
                    });
                    levelQBtn.addEventListener('keydown', function (e) {
                        if (e.key !== 'Enter' && e.key !== ' ') {
                            return;
                        }
                        e.preventDefault();
                        var open = !levelQPanel.classList.contains('hidden');
                        levelQSet(!open);
                    });
                    document.addEventListener('click', function (e) {
                        if (levelQWrap.contains(e.target)) {
                            return;
                        }
                        levelQSet(false);
                    });
                    document.addEventListener('keydown', function (e) {
                        if (e.key === 'Escape') {
                            levelQSet(false);
                        }
                    });
                }
            }
        })();
    </script>
@endpush
