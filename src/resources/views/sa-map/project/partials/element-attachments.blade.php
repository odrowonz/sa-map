<div class="mt-6 border-t border-slate-200 pt-4">
    <h4 class="text-sm font-medium text-slate-800">Вложения (файлы, PNG, схемы)</h4>
    <div class="mt-2 rounded-md border border-sky-200 bg-sky-50 px-3 py-2 text-sm text-sky-900">
        <strong>Как пользоваться:</strong> выберите файл → нажмите <strong>«Загрузить»</strong> — файл сразу сохраняется и появится в списке ниже. Кнопку «Сохранить изменения» в форме выше для файлов нажимать <strong>не нужно</strong> (она только для текста, галочки и трассировки).
    </div>
    <p class="mt-2 text-xs text-slate-500">
        К <strong>одной записи</strong> артефакта можно прикрепить <strong>несколько файлов</strong> — каждый «Загрузить» добавляет ещё одну строку в списке. Хранение в <code class="rounded bg-slate-100 px-1">sa_attachments</code>.
    </p>

    @if ($element->attachments->isNotEmpty())
        <ul class="mt-3 list-none space-y-4 p-0">
            @foreach ($element->attachments as $att)
                <li class="rounded-lg border border-slate-200 bg-slate-50/90 p-3">
                    <div class="flex gap-3">
                        <span class="mt-0.5 shrink-0 text-slate-500" title="Вложение">
                            {{-- иконка «прищепка» --}}
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-5 w-5" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m18.375 12.845-8.25 8.25a4.5 4.5 0 0 1-6.364-6.364l8.25-8.25a3 3 0 1 1 4.243 4.243l-8.25 8.25a1.5 1.5 0 0 1-2.122-2.122l8.25-8.25" />
                            </svg>
                        </span>
                        <div class="min-w-0 flex-1">
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

                            @php
                                $icon = \App\Support\AttachmentFileIcon::forAttachment($att);
                            @endphp
                            @if ($icon['mode'] === 'image')
                                <p class="mt-3 text-xs font-medium text-slate-600">Предпросмотр</p>
                                <div class="mt-1 overflow-hidden rounded-md border border-slate-200 bg-white">
                                    <img
                                        src="{{ route('attachments.file', [$project, $att]) }}"
                                        alt="Предпросмотр: {{ $att->original_name }}"
                                        class="max-h-64 w-full max-w-lg object-contain"
                                        loading="lazy"
                                    />
                                </div>
                            @elseif ($icon['mode'] === 'brand')
                                <div class="mt-3 rounded-md border border-slate-200 bg-white p-3">
                                    <p class="text-xs font-medium text-slate-600">Тип / инструмент</p>
                                    <div class="mt-2 flex items-start gap-3">
                                        <div class="relative flex h-14 w-14 shrink-0 items-center justify-center rounded-lg border border-slate-100 bg-slate-50">
                                            <img
                                                src="{{ $icon['url'] }}"
                                                alt=""
                                                width="40"
                                                height="40"
                                                class="h-10 w-10 object-contain"
                                                loading="lazy"
                                                decoding="async"
                                                referrerpolicy="no-referrer"
                                                onerror="this.classList.add('hidden');this.nextElementSibling.classList.remove('hidden')"
                                            />
                                            <span class="hidden h-10 w-10 text-slate-400" aria-hidden="true">
                                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-full w-full">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                                                </svg>
                                            </span>
                                        </div>
                                        <div class="min-w-0 text-sm text-slate-700">
                                            <p class="font-medium text-slate-800">{{ $icon['label'] }}</p>
                                            <p class="mt-1 text-xs text-slate-500">
                                                Просмотр содержимого — кнопка «Открыть». Иконки: <a href="https://github.com/vscode-icons/vscode-icons" class="underline" target="_blank" rel="noopener noreferrer">vscode-icons</a> (jsDelivr, MIT).
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            @else
                                <div class="mt-3 flex items-start gap-3 rounded-md border border-slate-200 bg-white p-3">
                                    <span class="shrink-0 text-slate-400" aria-hidden="true">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-10 w-10">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                                        </svg>
                                    </span>
                                    <div>
                                        <p class="text-xs font-medium text-slate-600">Без фирменной иконки</p>
                                        <p class="mt-1 text-sm text-slate-700">{{ $icon['label'] }}</p>
                                        <p class="mt-1 text-xs text-slate-500">Откройте файл кнопкой «Открыть». При необходимости расширение можно добавить в конфиг <code class="rounded bg-slate-100 px-1 text-xs">config/sa_map/attachment_file_icons.php</code>.</p>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
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
            <label class="block text-sm font-medium text-slate-700" for="attachment-file-{{ $element->id }}">
                <span class="inline-flex items-center gap-1.5">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-4 w-4 text-slate-500" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m18.375 12.845-8.25 8.25a4.5 4.5 0 0 1-6.364-6.364l8.25-8.25a3 3 0 1 1 4.243 4.243l-8.25 8.25a1.5 1.5 0 0 1-2.122-2.122l8.25-8.25" />
                    </svg>
                    Добавить ещё файл
                </span>
            </label>
            <input
                id="attachment-file-{{ $element->id }}"
                name="file"
                type="file"
                required
                class="mt-1 block w-full text-sm text-slate-600 file:mr-3 file:rounded-md file:border-0 file:bg-slate-800 file:px-3 file:py-1.5 file:text-sm file:font-medium file:text-white hover:file:bg-slate-700"
            />
            <p class="mt-1 text-xs text-slate-500">До 20 МБ за один раз. Можно загрузить несколько раз подряд.</p>
        </div>
        <button type="submit" class="rounded-md bg-slate-800 px-4 py-2 text-sm font-medium text-white hover:bg-slate-700">
            Загрузить
        </button>
    </form>
</div>
