@php
    /** @var \Illuminate\Support\Collection<string, \Illuminate\Support\Collection<int, \App\Models\Element>> $elementsByArtifact */
    /** @var list<array{key: string, label: string, multiple: bool, elementCount?: int}> $artifacts */
@endphp

<div class="artifact-sections mx-auto w-full max-w-4xl space-y-8" id="artifact-sections-root">
    @foreach ($artifacts as $artifact)
        @php
            $key = $artifact['key'];
            $rows = $elementsByArtifact->get($key, collect());
            $fragmentId = 'artifact-'.$key;
            $count = (int) ($artifact['elementCount'] ?? ($artifact['multiple'] ? $rows->count() : ($rows->isNotEmpty() ? 1 : 0)));
            $labelLower = mb_strtolower($artifact['label']);
        @endphp

        <section
            id="{{ $fragmentId }}"
            data-artifact-section
            data-artifact-key="{{ $key }}"
            data-artifact-label="{{ $labelLower }}"
            class="artifact-section scroll-mt-24 rounded-2xl border border-slate-200/90 bg-white shadow-sm"
        >
            <header class="border-b border-slate-100 px-5 py-4">
                <div class="flex flex-wrap items-start justify-between gap-2">
                    <div class="min-w-0">
                        <div class="mt-0.5 flex items-start gap-1.5">
                            <h2 class="min-w-0 text-base font-bold text-slate-900">{{ $artifact['label'] }}</h2>
                            <details class="relative mt-0.5 shrink-0">
                                <summary class="list-none cursor-pointer text-slate-400 transition hover:text-slate-700 [&::-webkit-details-marker]:hidden">
                                    <span class="sr-only">Info</span>
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="h-3.5 w-3.5" aria-hidden="true">
                                        <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-6h2v6zm0-8h-2V7h2v2z" />
                                    </svg>
                                </summary>
                                <div class="absolute left-0 top-full z-20 mt-2 w-44 rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs text-slate-600 shadow-lg">
                                    @if ($artifact['multiple'])
                                        {{ __('sa.artifact.multiple') }}
                                    @else
                                        {{ __('sa.artifact.single') }}
                                    @endif
                                </div>
                            </details>
                        </div>
                    </div>
                    <div class="flex shrink-0 flex-wrap items-center gap-2">
                        <span class="rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-semibold text-slate-700 tabular-nums" title="{{ __('sa.artifact.count') }}">
                            {{ $count }}
                        </span>
                        @if ($count > 0)
                            <span class="rounded-full bg-emerald-100 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-emerald-800">
                                {{ __('sa.artifact.has_data') }}
                            </span>
                        @endif
                    </div>
                </div>
            </header>

            <div class="px-5 py-5">
                @if ($artifact['multiple'])
                    @if ($rows->isEmpty())
                        <p class="text-sm text-slate-500">{{ __('sa.artifact.empty') }}</p>
                    @else
                        <ul class="space-y-4">
                            @foreach ($rows as $element)
                                <li class="rounded-2xl border border-slate-200 bg-slate-50/90 p-4 shadow-sm">
                                    @include('sa-map.project.partials.element-form', [
                                        'project' => $project,
                                        'level' => $level,
                                        'artifact' => $artifact,
                                        'element' => $element,
                                        'upstreamElements' => $upstreamElements,
                                        'isNew' => false,
                                    ])
                                </li>
                            @endforeach
                        </ul>
                    @endif

                    <div class="mt-4 rounded-2xl border border-dashed border-slate-300 bg-white p-4 shadow-sm">
                        <p class="mb-3 text-[10px] font-bold uppercase tracking-wide text-slate-500">{{ __('sa.artifact.new_entry') }}</p>
                        @include('sa-map.project.partials.element-form', [
                            'project' => $project,
                            'level' => $level,
                            'artifact' => $artifact,
                            'element' => null,
                            'upstreamElements' => $upstreamElements,
                            'isNew' => true,
                        ])
                    </div>
                @else
                    @php $single = $rows->first(); @endphp
                    <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                        @include('sa-map.project.partials.element-form', [
                            'project' => $project,
                            'level' => $level,
                            'artifact' => $artifact,
                            'element' => $single,
                            'upstreamElements' => $upstreamElements,
                            'isNew' => $single === null,
                        ])
                    </div>
                @endif
            </div>
        </section>
    @endforeach
</div>
