@php
    $c = $element?->content ?? [];
    $title = old('title', $c['title'] ?? '');
    $body = old('body', $c['body'] ?? '');
    $include = old('include_in_export', $element?->include_in_export ?? true);
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
        <label class="block text-sm font-medium text-slate-700">Краткое название / заголовок</label>
        <input
            type="text"
            name="title"
            value="{{ is_string($title) ? $title : '' }}"
            maxlength="500"
            class="mt-1 block w-full rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-slate-500 focus:outline-none focus:ring-1 focus:ring-slate-500"
        />
    </div>

    <div>
        <label class="block text-sm font-medium text-slate-700">Содержимое</label>
        <textarea
            name="body"
            rows="6"
            class="mt-1 block w-full rounded-md border border-slate-300 px-3 py-2 font-mono text-sm shadow-sm focus:border-slate-500 focus:outline-none focus:ring-1 focus:ring-slate-500"
            placeholder="Текст и списки. Файлы — в отдельном блоке «Вложения» под этой формой (только у уже сохранённой записи)."
        >{{ is_string($body) ? $body : '' }}</textarea>
    </div>

    <div class="flex flex-wrap items-center gap-4">
        <label class="inline-flex items-center gap-2 text-sm text-slate-700">
            <input type="hidden" name="include_in_export" value="0" />
            <input
                type="checkbox"
                name="include_in_export"
                value="1"
                class="rounded border-slate-300 text-slate-800 focus:ring-slate-500"
                @checked(filter_var($include, FILTER_VALIDATE_BOOLEAN))
            />
            Включать в выгрузки (Сохранить данные / MD)
        </label>
    </div>

    @if ($level > 1)
        <div>
            <label class="block text-sm font-medium text-slate-700">Основание на L{{ $level - 1 }} (трассировка)</label>
            @if ($upstreamElements->isEmpty())
                <p class="mt-1 text-sm text-amber-800">На уровне L{{ $level - 1 }} пока нет элементов — сначала заполните предыдущий уровень.</p>
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
                <p class="mt-1 text-xs text-slate-500">Ctrl+клик для нескольких значений.</p>
            @endif
        </div>
    @endif

    @if ($isNew)
        <p class="text-sm text-slate-500">Сохраните запись — затем под формой появится блок загрузки файлов к этому артефакту.</p>
    @endif

    <div class="flex flex-wrap items-center gap-3 pt-1">
        <button type="submit" class="rounded-md bg-slate-800 px-4 py-2 text-sm font-medium text-white hover:bg-slate-700">
            @if ($isNew)
                @if ($artifact['multiple'])
                    Добавить запись
                @else
                    Сохранить
                @endif
            @else
                Сохранить изменения
            @endif
        </button>
    </div>

    </form>

{{-- Вложения: отдельные формы, нельзя вкладывать в форму текста (HTML) --}}
@if (! $isNew && $element)
    @include('sa-map.project.partials.element-attachments', ['project' => $project, 'element' => $element])
@endif

@if (! $isNew && $element)
    <form method="post" action="{{ route('elements.destroy', [$project, $element]) }}" class="mt-4 inline" onsubmit="return confirm('Удалить эту запись артефакта?');">
        @csrf
        @method('DELETE')
        <button type="submit" class="text-sm text-red-700 underline hover:text-red-900">Удалить запись</button>
    </form>
@endif
