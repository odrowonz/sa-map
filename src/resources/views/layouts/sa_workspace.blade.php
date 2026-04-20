<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', config('app.name'))</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="flex min-h-screen flex-col bg-slate-100 antialiased lg:h-screen lg:overflow-hidden">
    @if (session('status'))
        <div class="shrink-0 border-b border-green-200 bg-green-50 px-4 py-2 text-center text-sm text-green-800">
            {{ session('status') }}
        </div>
    @endif

    {{-- Как в prototype/index.html: слева колонка на всю высоту, справа main с шапкой, табами уровней и редактором --}}
    <div class="flex flex-1 flex-col lg:min-h-0 lg:flex-1 lg:flex-row lg:overflow-hidden">
        {{-- Панель 1: фиксирована слева, высота рабочей области --}}
        <aside class="flex w-full shrink-0 flex-col border-b border-slate-700 bg-slate-800 text-slate-300 lg:h-full lg:w-72 lg:min-h-0 lg:border-r lg:border-b-0">
            <div class="shrink-0 border-b border-slate-700 p-4">
                <a href="{{ route('home') }}" class="text-lg font-bold tracking-tight text-white hover:text-blue-300">Карта СА</a>
                @auth
                    <p class="mt-2 truncate text-xs text-slate-400" title="{{ Auth::user()->email }}">{{ Auth::user()->email }}</p>
                @else
                    <p class="mt-2 text-xs">
                        <a href="{{ route('login') }}" class="text-blue-400 hover:text-blue-300 hover:underline">Вход</a>
                    </p>
                @endauth
            </div>
            <div class="flex min-h-0 flex-1 flex-col overflow-hidden">
                <p class="shrink-0 px-3 pb-2 pt-1 text-[10px] font-bold uppercase tracking-widest text-slate-500">Типы артефактов</p>
                <div class="min-h-0 flex-1 overflow-y-auto overscroll-contain px-2 pb-2">
                    @yield('workspace_sidebar')
                </div>
            </div>
            @auth
                <div class="shrink-0 space-y-2 border-t border-slate-700 p-3 text-xs">
                    <a href="{{ route('dashboard') }}" class="block font-medium text-slate-400 hover:text-white">Личный кабинет</a>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="text-slate-500 hover:text-red-300">Выйти</button>
                    </form>
                </div>
            @endauth
        </aside>

        {{-- Панели 2–4: стыкуются к левой колонке --}}
        <div class="flex min-h-0 min-w-0 flex-1 flex-col lg:min-h-0">
            {{-- Панель 2: название проекта слева, действия справа --}}
            <header class="shrink-0 border-b border-slate-200 bg-white px-4 py-3 shadow-sm sm:px-6">
                @yield('workspace_toolbar')
            </header>

            {{-- Панель 3: L1–L10 --}}
            <div class="shrink-0 border-b border-slate-200 bg-white shadow-sm">
                @yield('workspace_level_tabs')
            </div>

            {{-- Панель 4: прокрутка, основной контент --}}
            <main id="workspace-main-scroll" class="min-h-0 flex-1 overflow-y-auto overscroll-contain bg-slate-50" tabindex="-1">
                @yield('workspace_main')
            </main>
        </div>
    </div>

    @stack('scripts')
</body>
</html>
