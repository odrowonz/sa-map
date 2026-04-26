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
            <button
                type="button"
                id="sa-export-data-btn"
                class="rounded-lg border border-slate-200 bg-white px-4 py-2 text-[10px] font-bold uppercase tracking-wider text-slate-600 shadow-sm hover:bg-slate-50"
                title="{{ __('sa.project_data.export_title') }}"
            >
                {{ __('sa.project_data.export') }}
            </button>
            @if ($njkTemplatesForMdExport->isNotEmpty())
                <button
                    type="button"
                    id="sa-md-export-open"
                    class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-2 text-[10px] font-bold uppercase tracking-wider text-emerald-800 shadow-sm hover:bg-emerald-100"
                    title="{{ __('sa.project_data.export_md_title') }}"
                >
                    {{ __('sa.project_data.export_md') }}
                </button>
            @endif
            <form
                id="sa-import-data-form"
                method="post"
                action="{{ route('projects.import-data', $project) }}"
                enctype="multipart/form-data"
                class="inline-flex flex-wrap items-center gap-2"
                data-confirm="{{ __('sa.project_data.import_confirm') }}"
            >
                @csrf
                <label class="cursor-pointer rounded-lg border border-slate-200 bg-white px-3 py-2 text-[10px] font-bold uppercase tracking-wider text-slate-600 shadow-sm hover:bg-slate-50">
                    <span>{{ __('sa.project_data.import_pick') }}</span>
                    <input type="file" name="archive" accept=".zip,application/zip" class="hidden" required onchange="(function(f){ var b=f.querySelector('[data-import-submit]'); if(b) b.removeAttribute('disabled'); ['import_element_ids','import_manifest_element_ids'].forEach(function(n){ var h=f.querySelector('input[name='+n+']'); if(h) h.remove(); }); })(this.form);" />
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

    @php
        $wizardArtifacts = config('sa_map.artifacts', []);
        $wizardLevelTitles = [];
        foreach (range(1, 10) as $_wn) {
            $wizardLevelTitles[$_wn] = (string) __('sa.map_levels.'.$_wn.'.title');
        }
        $wizardI18n = [
            'wizard_title_export' => __('sa.project_data.wizard_title_export'),
            'wizard_hint_export' => __('sa.project_data.wizard_hint_export'),
            'wizard_title_export_md' => __('sa.project_data.wizard_title_export_md'),
            'wizard_hint_export_md' => __('sa.project_data.wizard_hint_export_md'),
            'wizard_title_import' => __('sa.project_data.wizard_title_import'),
            'wizard_hint_import' => __('sa.project_data.wizard_hint_import'),
            'wizard_continue' => __('sa.project_data.wizard_continue'),
            'wizard_cancel' => __('sa.project_data.wizard_cancel'),
            'wizard_close' => __('sa.project_data.wizard_close'),
            'wizard_err_manifest' => __('sa.project_data.wizard_err_manifest'),
            'wizard_err_empty' => __('sa.project_data.wizard_err_empty'),
            'wizard_err_tree' => __('sa.project_data.wizard_err_tree'),
        ];
    @endphp
    <div
        id="sa-export-wizard-config"
        class="hidden"
        data-tree-url="{{ route('projects.export-wizard-tree', $project) }}"
        data-export-url="{{ route('projects.export-data', $project) }}"
        data-json-name="{{ e(\App\Services\ProjectDataExchangeService::exportManifestJsonName($project)) }}"
        data-legacy-json="{{ e(\App\Services\ProjectDataExchangeService::LEGACY_JSON_NAME) }}"
    ></div>
    <script type="application/json" id="sa-element-wizard-flags">
        {!! json_encode($elementWizardFlags ?? [], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS) !!}
    </script>
    <script type="application/json" id="sa-export-wizard-artifacts">
        {!! json_encode($wizardArtifacts, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS) !!}
    </script>
    <script type="application/json" id="sa-export-wizard-level-titles">
        {!! json_encode($wizardLevelTitles, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS) !!}
    </script>
    <script type="application/json" id="sa-export-wizard-i18n">
        {!! json_encode($wizardI18n, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS) !!}
    </script>
    <div
        id="sa-export-wizard-modal"
        class="fixed inset-0 z-[60] hidden items-center justify-center bg-slate-900/50 p-4"
        role="dialog"
        aria-modal="true"
        aria-labelledby="sa-export-wizard-title"
        aria-hidden="true"
    >
        <div class="flex max-h-[85vh] w-full max-w-lg flex-col overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-xl">
            <div class="flex shrink-0 items-start justify-between gap-3 border-b border-slate-100 px-5 py-4">
                <div class="min-w-0">
                    <h2 id="sa-export-wizard-title" class="text-base font-bold text-slate-900"></h2>
                    <p id="sa-export-wizard-hint" class="mt-1 text-xs text-slate-600"></p>
                </div>
                <button
                    type="button"
                    id="sa-export-wizard-close"
                    class="rounded-lg p-1 text-slate-500 hover:bg-slate-100 hover:text-slate-800"
                    aria-label="{{ __('sa.project_data.wizard_close') }}"
                >
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div id="sa-export-wizard-tree" class="min-h-0 flex-1 overflow-y-auto px-5 py-3"></div>
            <div class="flex shrink-0 flex-wrap justify-end gap-2 border-t border-slate-100 px-5 py-4">
                <button
                    type="button"
                    id="sa-export-wizard-cancel"
                    class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-xs font-bold uppercase tracking-wide text-slate-600 hover:bg-slate-50"
                >
                    {{ __('sa.project_data.wizard_cancel') }}
                </button>
                <button
                    type="button"
                    id="sa-export-wizard-continue"
                    class="rounded-xl bg-blue-600 px-4 py-2 text-xs font-bold uppercase tracking-wide text-white shadow-sm hover:bg-blue-700"
                >
                    {{ __('sa.project_data.wizard_continue') }}
                </button>
            </div>
        </div>
    </div>

    @if ($njkTemplatesForMdExport->isNotEmpty())
        @php
            $dummyNjkId = ((int) (\App\Models\NjkTemplate::query()->max('id') ?? 0)) + 1_000_000;
            $mdExportTemplateUrlTpl = preg_replace(
                '#(/njk-templates/)\d+(/export-body)$#',
                '$1__TEMPLATE_ID__$2',
                route('projects.njk-templates.export-body', [$project, $dummyNjkId])
            ) ?? route('projects.njk-templates.export-body', [$project, $dummyNjkId]);
            $mdExportI18n = [
                'pick_template' => __('sa.project_data.export_md_pick'),
                'config' => __('sa.project_data.export_md_config'),
                'busy' => __('sa.project_data.export_md_busy'),
                'error_zip' => __('sa.project_data.export_md_error_zip'),
                'error_json' => __('sa.project_data.export_md_error_json'),
                'error_json_parse' => __('sa.project_data.export_md_error_json_parse'),
                'error_template' => __('sa.project_data.export_md_error_template'),
                'error_render' => __('sa.project_data.export_md_error_render'),
                'error_network' => __('sa.project_data.export_md_error_network'),
            ];
        @endphp
        <div
            id="sa-md-export-config"
            class="hidden"
            data-export-post="{{ route('projects.export-data', $project) }}"
            data-json-name="{{ e(\App\Services\ProjectDataExchangeService::exportManifestJsonName($project)) }}"
            data-md-name="{{ e(\App\Services\ProjectDataExchangeService::exportManifestMdName($project)) }}"
            data-template-url-tpl="{{ $mdExportTemplateUrlTpl }}"
        ></div>
        <script type="application/json" id="sa-md-export-bridge">
            {!! json_encode($mdTemplateLegacyBridge, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS) !!}
        </script>
        <script type="application/json" id="sa-md-export-i18n">
            {!! json_encode($mdExportI18n, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS) !!}
        </script>
        <div
            id="sa-md-export-modal"
            class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-900/50 p-4"
            role="dialog"
            aria-modal="true"
            aria-labelledby="sa-md-export-title"
            aria-hidden="true"
        >
            <div class="w-full max-w-md rounded-2xl border border-slate-200 bg-white p-6 shadow-xl">
                <div class="flex items-start justify-between gap-3">
                    <h2 id="sa-md-export-title" class="text-base font-bold text-slate-900">{{ __('sa.project_data.export_md_modal_title') }}</h2>
                    <button
                        type="button"
                        id="sa-md-export-close"
                        class="rounded-lg p-1 text-slate-500 hover:bg-slate-100 hover:text-slate-800"
                        aria-label="{{ __('sa.project_data.export_md_close') }}"
                    >
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <p class="mt-2 text-xs text-slate-600">{{ __('sa.project_data.export_md_modal_hint') }}</p>
                <label for="sa-md-export-template" class="mt-4 block text-[10px] font-bold uppercase tracking-wide text-slate-500">
                    {{ __('sa.project_data.export_md_template_label') }}
                </label>
                <select
                    id="sa-md-export-template"
                    class="mt-1.5 block w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm shadow-sm focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/30 focus:outline-none"
                >
                    <option value="">{{ __('sa.project_data.export_md_template_placeholder') }}</option>
                    @foreach ($njkTemplatesForMdExport as $tpl)
                        <option value="{{ $tpl->id }}">{{ $tpl->title }}</option>
                    @endforeach
                </select>
                <p id="sa-md-export-error" class="mt-3 hidden text-sm text-red-600" role="alert"></p>
                <div class="mt-6 flex flex-wrap justify-end gap-2">
                    <button
                        type="button"
                        id="sa-md-export-cancel"
                        class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-xs font-bold uppercase tracking-wide text-slate-600 hover:bg-slate-50"
                    >
                        {{ __('sa.project_data.export_md_cancel') }}
                    </button>
                    <button
                        type="button"
                        id="sa-md-export-run"
                        class="rounded-xl bg-emerald-600 px-4 py-2 text-xs font-bold uppercase tracking-wide text-white shadow-sm hover:bg-emerald-700"
                    >
                        {{ __('sa.project_data.export_md_download') }}
                    </button>
                </div>
            </div>
        </div>
    @endif
@endsection

@push('scripts')
    @vite(['resources/js/project-export-wizard.js'])
    @if ($njkTemplatesForMdExport->isNotEmpty())
        @vite(['resources/js/project-md-export.js'])
    @endif
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
