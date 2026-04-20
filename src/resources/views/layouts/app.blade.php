<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', config('app.name'))</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="flex min-h-screen flex-col bg-slate-100 antialiased">
    <header class="sticky top-0 z-50 shrink-0 border-b border-slate-200 bg-white shadow-sm">
        <div class="mx-auto flex max-w-6xl flex-wrap items-center justify-between gap-4 px-6 py-4">
            <a href="{{ route('home') }}" class="font-semibold text-slate-800 hover:text-slate-600">{{ __('sa.nav.map_sa') }}</a>
            <nav class="flex flex-wrap items-center gap-3 text-sm">
                @auth
                    <a href="{{ route('dashboard') }}" class="font-medium text-slate-800 hover:text-slate-600">{{ __('sa.nav.dashboard') }}</a>
                    <span class="hidden text-slate-400 sm:inline">|</span>
                    <span class="max-w-[12rem] truncate text-slate-600 sm:max-w-xs" title="{{ Auth::user()->email }}">{{ Auth::user()->email }}</span>
                    <form method="POST" action="{{ route('logout') }}" class="inline">
                        @csrf
                        <button type="submit" class="text-slate-700 underline hover:text-slate-900">{{ __('sa.nav.logout') }}</button>
                    </form>
                @else
                    <a href="{{ route('login') }}" class="text-slate-700 hover:text-slate-900">{{ __('sa.nav.login') }}</a>
                    <a href="{{ route('register') }}" class="text-slate-700 hover:text-slate-900">{{ __('sa.nav.register') }}</a>
                @endauth
            </nav>
        </div>
    </header>
    <main class="mx-auto flex w-full min-h-0 flex-1 flex-col px-6 @yield('main_width', 'max-w-3xl') @yield('main_class', 'overflow-y-auto py-10')">
        @if (session('status'))
            <p class="mb-6 rounded-md border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">{{ session('status') }}</p>
        @endif
        @yield('content')
    </main>
</body>
</html>
