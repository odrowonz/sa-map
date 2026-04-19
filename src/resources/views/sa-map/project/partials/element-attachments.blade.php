<div class="mt-6 border-t border-slate-200 pt-4">
    <h4 class="text-sm font-medium text-slate-800">Вложения (файлы, PNG, схемы)</h4>
    <p class="mt-1 text-xs text-slate-500">Хранение в <code class="rounded bg-slate-100 px-1">sa_attachments</code>, привязка к этой записи элемента.</p>

    @if ($element->attachments->isNotEmpty())
        <ul class="mt-3 space-y-4">
            @foreach ($element->attachments as $att)
                <li class="rounded-lg border border-slate-200 bg-slate-50/90 p-3">
                    <div class="flex flex-wrap items-start justify-between gap-2">
                        <div class="min-w-0">
                            <p class="truncate text-sm font-medium text-slate-800" title="{{ $att->original_name }}">
                                {{ $att->original_name }}
                            </p>
                            <p class="mt-0.5 text-xs text-slate-500">
                                {{ $att->kind }}
                                @if ($att->size_bytes !== null)
                                    · {{ \Illuminate\Support\Number::fileSize($att->size_bytes ?? 0) }}
                                @endif
                                @if ($att->mime_type)
                                    · {{ $att->mime_type }}
                                @endif
                            </p>
                        </div>
                        <div class="flex shrink-0 flex-wrap items-center gap-2">
                            <a
                                href="{{ route('attachments.file', [$project, $att]) }}"
                                class="text-sm font-medium text-slate-800 underline hover:text-slate-600"
                                target="_blank"
                                rel="noopener noreferrer"
                            >
                                Открыть
                            </a>
                            <form
                                method="post"
                                action="{{ route('attachments.destroy', [$project, $att]) }}"
                                class="inline"
                                onsubmit="return confirm('Удалить этот файл?');"
                            >
                                @csrf
                                @method('DELETE')
                                <input type="hidden" name="element" value="{{ $element->id }}" />
                                <button type="submit" class="text-sm text-red-700 underline hover:text-red-900">Удалить</button>
                            </form>
                        </div>
                    </div>
                    @if ($att->canPreviewInline())
                        <div class="mt-3 overflow-hidden rounded-md border border-slate-200 bg-white">
                            <img
                                src="{{ route('attachments.file', [$project, $att]) }}"
                                alt=""
                                class="max-h-64 w-full max-w-lg object-contain"
                                loading="lazy"
                            />
                        </div>
                    @endif
                </li>
            @endforeach
        </ul>
    @else
        <p class="mt-2 text-sm text-slate-500">Файлов пока нет.</p>
    @endif

    <form
        method="post"
        action="{{ route('attachments.store', [$project, $element]) }}"
        enctype="multipart/form-data"
        class="mt-4 flex flex-col gap-2 sm:flex-row sm:flex-wrap sm:items-end"
    >
        @csrf
        <div class="min-w-0 flex-1">
            <label class="block text-sm font-medium text-slate-700" for="attachment-file-{{ $element->id }}">Добавить файл</label>
            <input
                id="attachment-file-{{ $element->id }}"
                name="file"
                type="file"
                required
                class="mt-1 block w-full text-sm text-slate-600 file:mr-3 file:rounded-md file:border-0 file:bg-slate-800 file:px-3 file:py-1.5 file:text-sm file:font-medium file:text-white hover:file:bg-slate-700"
            />
            <p class="mt-1 text-xs text-slate-500">До 20 МБ. Схемы, документы, изображения.</p>
        </div>
        <button type="submit" class="rounded-md bg-slate-800 px-4 py-2 text-sm font-medium text-white hover:bg-slate-700">
            Загрузить
        </button>
    </form>
</div>
