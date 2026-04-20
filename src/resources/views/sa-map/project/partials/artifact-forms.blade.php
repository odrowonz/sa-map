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
                        <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400">{{ $key }}</p>
                        <h2 class="mt-1 text-base font-bold text-slate-900">{{ $artifact['label'] }}</h2>
                        <p class="mt-0.5 text-xs text-slate-500">
                            @if ($artifact['multiple'])
                                Несколько записей
                            @else
                                Одна запись
                            @endif
                        </p>
                    </div>
                    <div class="flex shrink-0 flex-wrap items-center gap-2">
                        <span class="rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-semibold text-slate-700 tabular-nums" title="Количество записей по этому типу">
                            {{ $count }}
                        </span>
                        @if ($count > 0)
                            <span class="rounded-full bg-emerald-100 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-emerald-800">
                                есть данные
                            </span>
                        @endif
                    </div>
                </div>
            </header>

            <div class="px-5 py-5">
                @if ($artifact['multiple'])
                    @if ($rows->isEmpty())
                        <p class="text-sm text-slate-500">Записей пока нет — добавьте первую ниже.</p>
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
                        <p class="mb-3 text-[10px] font-bold uppercase tracking-wide text-slate-500">Новая запись</p>
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
