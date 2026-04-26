@php
    $c = $element?->content ?? [];
    $title = old('title', $c['title'] ?? '');
    $body = old('body', $c['body'] ?? '');
    $selectedUpstream = old('upstream_element_ids', $c['upstreamElementIds'] ?? []);
    if (! is_array($selectedUpstream)) {
        $selectedUpstream = [];
    }
@endphp

@if ($isNew)
    <form method="post" action="{{ route('elements.store', $project) }}" class="space-y-3">
        @csrf
        <input type="hidden" name="level" value="{{ $level }}" />
        <input type="hidden" name="artifact_key" value="{{ $artifact['key'] }}" />
@else
    <form method="post" action="{{ route('elements.update', [$project, $element]) }}" class="space-y-3">
        @csrf
        @method('PATCH')
@endif

    <div>
        <label class="block text-sm font-medium text-slate-700">{{ __('sa.element.title_label') }}</label>
        <input
            type="text"
            name="title"
            value="{{ is_string($title) ? $title : '' }}"
            maxlength="500"
            class="mt-1 block w-full rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-slate-500 focus:outline-none focus:ring-1 focus:ring-slate-500"
        />
    </div>

    <div>
        <label class="block text-sm font-medium text-slate-700">{{ __('sa.element.body_label') }}</label>
        <textarea
            name="body"
            rows="6"
            class="mt-1 block w-full rounded-md border border-slate-300 px-3 py-2 font-mono text-sm shadow-sm focus:border-slate-500 focus:outline-none focus:ring-1 focus:ring-slate-500"
            placeholder="{{ __('sa.element.body_placeholder') }}"
        >{{ is_string($body) ? $body : '' }}</textarea>
    </div>

    @if ($level > 1)
        <div>
            <label class="block text-sm font-medium text-slate-700">{{ __('sa.element.upstream_label', ['prev' => $level - 1]) }}</label>
            @if ($upstreamElements->isEmpty())
                <p class="mt-1 text-sm text-amber-800">{{ __('sa.element.upstream_empty', ['prev' => $level - 1]) }}</p>
            @else
                <select
                    name="upstream_element_ids[]"
                    multiple
                    size="{{ min(8, max(3, $upstreamElements->count())) }}"
                    class="mt-1 block w-full rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-slate-500 focus:outline-none focus:ring-1 focus:ring-slate-500"
                >
                    @foreach ($upstreamElements as $up)
                        <option value="{{ $up->id }}" @selected(in_array($up->id, $selectedUpstream, true))>
                            [{{ $up->artifact_key }}] {{ $up->label() }}
                        </option>
                    @endforeach
                </select>
                <p class="mt-1 text-xs text-slate-500">{{ __('sa.element.upstream_hint') }}</p>
            @endif
        </div>
    @endif

    @if ($isNew)
        <p class="text-sm text-slate-500">{{ __('sa.element.save_then_attachments') }}</p>
    @endif

    <div class="flex flex-wrap items-center gap-3 pt-1">
        <button type="submit" class="rounded-md bg-slate-800 px-4 py-2 text-sm font-medium text-white hover:bg-slate-700">
            @if ($isNew)
                @if ($artifact['multiple'])
                    {{ __('sa.element.add_record') }}
                @else
                    {{ __('sa.element.save') }}
                @endif
            @else
                {{ __('sa.element.save_changes') }}
            @endif
        </button>
    </div>

    </form>

{{-- Вложения: отдельные формы, нельзя вкладывать в форму текста (HTML) --}}
@if (! $isNew && $element)
    @include('sa-map.project.partials.element-attachments', ['project' => $project, 'element' => $element])
@endif

@if (! $isNew && $element)
    <form method="post" action="{{ route('elements.destroy', [$project, $element]) }}" class="mt-4 inline" data-confirm="{{ __('sa.element.delete_record_confirm') }}" onsubmit="return confirm(this.dataset.confirm);">
        @csrf
        @method('DELETE')
        <button type="submit" class="text-sm text-red-700 underline hover:text-red-900">{{ __('sa.element.delete_record') }}</button>
    </form>
@endif
