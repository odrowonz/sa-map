@extends('layouts.app')

@section('main_width', 'max-w-6xl')

@section('title', 'Личный кабинет — ' . config('app.name'))

@section('content')
    @php($dash = route('dashboard'))
    <div class="flex flex-col gap-10 lg:flex-row lg:items-start">
        <nav class="relative z-20 shrink-0 lg:sticky lg:top-24 lg:w-52 lg:self-start" aria-label="Разделы кабинета">
            <ul class="flex flex-wrap gap-2 border-b border-slate-200 pb-4 lg:flex-col lg:border-b-0 lg:border-r lg:pb-0 lg:pr-6">
                <li>
                    <a href="{{ $dash }}#overview" data-cabinet-section="overview" class="cabinet-nav-link block cursor-pointer rounded-md border border-transparent px-3 py-2 text-sm text-slate-600 hover:bg-slate-100">Обзор</a>
                </li>
                <li>
                    <a href="{{ $dash }}#projects" data-cabinet-section="projects" class="cabinet-nav-link block cursor-pointer rounded-md border border-transparent px-3 py-2 text-sm text-slate-600 hover:bg-slate-100">Проекты</a>
                </li>
                <li>
                    <a href="{{ $dash }}#njk" data-cabinet-section="njk" class="cabinet-nav-link block cursor-pointer rounded-md border border-transparent px-3 py-2 text-sm text-slate-600 hover:bg-slate-100">Шаблоны NJK</a>
                </li>
                <li>
                    <a href="{{ $dash }}#profile" data-cabinet-section="profile" class="cabinet-nav-link block cursor-pointer rounded-md border border-transparent px-3 py-2 text-sm text-slate-600 hover:bg-slate-100">Профиль</a>
                </li>
            </ul>
        </nav>

        <div class="relative z-0 min-w-0 flex-1 space-y-12">
            <section id="overview" class="scroll-mt-24">
                <h1 class="text-2xl font-semibold text-slate-800">Личный кабинет</h1>
                <p class="mt-2 text-slate-600">
                    Здесь будут проекты карты аналитика (L1–L10), вложения и экспорт по
                    <a href="{{ route('home') }}" class="text-slate-800 underline">описанию в ТЗ</a>.
                </p>
            </section>

            <section id="projects" class="scroll-mt-24 rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="text-lg font-semibold text-slate-800">Проекты</h2>
                <p class="mt-2 text-sm text-slate-600">
                    Задачи и проекты системного анализа (данные в <code class="rounded bg-slate-100 px-1 text-xs">sa_projects</code>).
                </p>

                <form method="post" action="{{ route('projects.store') }}" class="mt-6 flex flex-col gap-3 sm:flex-row sm:items-end">
                    @csrf
                    <div class="min-w-0 flex-1">
                        <label for="project-name" class="block text-sm font-medium text-slate-700">Название проекта</label>
                        <input
                            id="project-name"
                            name="name"
                            type="text"
                            value="{{ old('name') }}"
                            required
                            maxlength="500"
                            class="mt-1 block w-full rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-slate-500 focus:outline-none focus:ring-1 focus:ring-slate-500"
                            placeholder="Например: Карта для сервиса переводов"
                        />
                        @error('name')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    <button type="submit" class="rounded-md bg-slate-800 px-4 py-2 text-sm font-medium text-white hover:bg-slate-700">
                        Создать проект
                    </button>
                </form>

                @if ($projects->isEmpty())
                    <div class="mt-6 rounded-lg border border-dashed border-slate-300 bg-slate-50 px-4 py-8 text-center text-sm text-slate-500">
                        Проектов пока нет — добавьте первый выше.
                    </div>
                @else
                    <ul class="mt-6 divide-y divide-slate-200 rounded-lg border border-slate-200">
                        @foreach ($projects as $project)
                            <li class="flex flex-wrap items-center justify-between gap-3 px-4 py-3">
                                <div class="min-w-0">
                                    <p class="font-medium text-slate-800">{{ $project->name }}</p>
                                    <p class="text-xs text-slate-500">
                                        Обновлён {{ $project->updated_at->translatedFormat('d.m.Y H:i') }}
                                        @if ($project->slug)
                                            · <span class="font-mono">{{ $project->slug }}</span>
                                        @endif
                                    </p>
                                </div>
                                <div class="flex shrink-0 flex-wrap items-center gap-4">
                                    <a href="{{ route('projects.show', $project) }}" class="text-sm font-medium text-slate-800 underline hover:text-slate-600">Открыть карту</a>
                                    <form method="post" action="{{ route('projects.destroy', $project) }}" class="inline" onsubmit="return confirm('Удалить этот проект? Связанные элементы карты будут удалены.');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-sm text-red-700 underline hover:text-red-900">Удалить</button>
                                    </form>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </section>

            <section id="njk" class="scroll-mt-24 rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="text-lg font-semibold text-slate-800">Библиотека шаблонов NJK</h2>
                <p class="mt-2 text-sm text-slate-600">
                    Шаблоны Nunjucks для экспорта в Markdown — по ТЗ, п. 5.5. Появится после реализации проектов.
                </p>
                <div class="mt-6 rounded-lg border border-dashed border-slate-300 bg-slate-50 px-4 py-8 text-center text-sm text-slate-500">
                    Шаблоны хранятся в <code class="rounded bg-slate-200 px-1 text-xs">sa_njk_templates</code>.
                </div>
            </section>

            <section id="profile" class="scroll-mt-24 rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="text-lg font-semibold text-slate-800">Профиль</h2>
                <dl class="mt-4 grid gap-3 text-sm sm:grid-cols-2">
                    <div>
                        <dt class="text-slate-500">Email</dt>
                        <dd class="font-medium text-slate-800">{{ $user->email }}</dd>
                    </div>
                    <div>
                        <dt class="text-slate-500">Имя</dt>
                        <dd class="font-medium text-slate-800">{{ $user->name ?: '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-slate-500">Язык интерфейса</dt>
                        <dd class="font-medium text-slate-800">{{ $user->locale }} <span class="text-slate-400">(переключатель — позже)</span></dd>
                    </div>
                </dl>
            </section>
        </div>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var nav = document.querySelector('[aria-label="Разделы кабинета"]');
            if (!nav) return;
            var links = nav.querySelectorAll('a[data-cabinet-section]');
            function hashKey() {
                var h = window.location.hash;
                if (!h || h === '#') return 'overview';
                return h.replace(/^#/, '');
            }
            function paint() {
                var key = hashKey();
                links.forEach(function (a) {
                    var on = a.getAttribute('data-cabinet-section') === key;
                    a.classList.toggle('bg-slate-200/80', on);
                    a.classList.toggle('font-medium', on);
                    a.classList.toggle('text-slate-800', on);
                    a.classList.toggle('text-slate-600', !on);
                    a.classList.toggle('border-slate-200/80', on);
                    if (on) a.setAttribute('aria-current', 'true');
                    else a.removeAttribute('aria-current');
                });
            }
            window.addEventListener('hashchange', paint);
            paint();
        });
    </script>
@endsection
