@php
    /** @var \Illuminate\Support\Collection<string, \Illuminate\Support\Collection<int, \App\Models\Element>> $elementsByArtifact */
@endphp

@foreach ($artifacts as $artifact)
    @php
        $key = $artifact['key'];
        $rows = $elementsByArtifact->get($key, collect());
        $fragmentId = 'artifact-'.$key;
    @endphp

    <section id="{{ $fragmentId }}" class="mt-10 scroll-mt-28 border-t border-slate-200 pt-8 first:mt-0 first:border-t-0 first:pt-0">
        <div class="flex flex-wrap items-baseline justify-between gap-2">
            <h3 class="text-base font-semibold text-slate-800">{{ $artifact['label'] }}</h3>
            <span class="text-xs text-slate-400">{{ $key }}@if ($artifact['multiple']) · несколько записей @else · одна запись @endif</span>
        </div>

        @if ($artifact['multiple'])
            @if ($rows->isEmpty())
                <p class="mt-2 text-sm text-slate-500">Записей пока нет — добавьте первую ниже.</p>
            @else
                <ul class="mt-4 space-y-4">
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
            <div class="mt-4 rounded-lg border border-slate-200 bg-white p-4">
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
    </section>
@endforeach
