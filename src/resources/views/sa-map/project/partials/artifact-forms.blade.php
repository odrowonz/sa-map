@php
    /** @var \Illuminate\Support\Collection<string, \Illuminate\Support\Collection<int, \App\Models\Element>> $elementsByArtifact */
@endphp

<div class="mt-8 space-y-3">
    @foreach ($artifacts as $artifact)
        @php
            $key = $artifact['key'];
            $rows = $elementsByArtifact->get($key, collect());
            $fragmentId = 'artifact-'.$key;
            $hasContent = $rows->isNotEmpty();
        @endphp

        <details id="{{ $fragmentId }}" class="group scroll-mt-28 rounded-lg border border-slate-200 bg-white shadow-sm open:ring-1 open:ring-slate-200/80">
            <summary
                class="flex cursor-pointer list-none items-center gap-3 px-4 py-3 text-left [&::-webkit-details-marker]:hidden"
            >
                <span
                    class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-md border border-slate-200 bg-slate-50 text-slate-500 transition group-open:border-slate-300 group-open:bg-slate-100"
                    aria-hidden="true"
                >
                    <svg
                        class="h-4 w-4 shrink-0 transition-transform group-open:rotate-180"
                        xmlns="http://www.w3.org/2000/svg"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke-width="1.5"
                        stroke="currentColor"
                    >
                        <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
                    </svg>
                </span>
                <span class="min-w-0 flex-1">
                    <span class="block text-base font-semibold text-slate-800">{{ $artifact['label'] }}</span>
                    <span class="mt-0.5 block text-xs text-slate-400">
                        {{ $key }}
                        @if ($artifact['multiple'])
                            · несколько записей
                        @else
                            · одна запись
                        @endif
                    </span>
                </span>
                @if ($hasContent)
                    <span class="shrink-0 rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-medium text-emerald-800" title="Есть сохранённые записи">
                        есть данные
                    </span>
                @endif
            </summary>

            <div class="border-t border-slate-100 px-4 py-4">
                @if ($artifact['multiple'])
                    @if ($rows->isEmpty())
                        <p class="text-sm text-slate-500">Записей пока нет — добавьте первую ниже.</p>
                    @else
                        <ul class="space-y-4">
                            @foreach ($rows as $element)
                                <li class="rounded-lg border border-slate-200 bg-slate-50/80 p-4">
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

                    <div class="mt-4 rounded-lg border border-dashed border-slate-300 bg-white p-4">
                        <p class="mb-3 text-xs font-medium text-slate-600">Новая запись</p>
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
                    <div class="rounded-lg border border-slate-200 bg-white p-4">
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
        </details>
    @endforeach
</div>
