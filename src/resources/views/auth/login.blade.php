@extends('layouts.sa_workspace')

@section('workspace_sidebar_heading', __('sa.home.sidebar_heading'))

@section('title', __('sa.auth.login_title').' — '.config('app.name'))

@section('workspace_sidebar')
    <div class="space-y-0.5">
        <a
            href="{{ route('home') }}"
            class="cabinet-nav-link flex w-full items-center rounded-lg border-l-4 border-transparent px-3 py-2.5 text-left text-xs font-semibold text-slate-300 transition-colors hover:bg-slate-700/60"
        >
            {{ __('sa.home.badge') }}
        </a>
        <span
            class="flex w-full items-center rounded-lg border-l-4 border-blue-500 bg-slate-700 px-3 py-2.5 text-left text-xs font-semibold text-white shadow-lg"
            aria-current="page"
        >
            {{ __('sa.auth.login_title') }}
        </span>
        <a
            href="{{ route('register') }}"
            class="cabinet-nav-link flex w-full items-center rounded-lg border-l-4 border-transparent px-3 py-2.5 text-left text-xs font-semibold text-slate-300 transition-colors hover:bg-slate-700/60"
        >
            {{ __('sa.auth.register_title') }}
        </a>
    </div>
@endsection

@section('workspace_toolbar')
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="min-w-0">
            <h1 class="truncate text-lg font-bold uppercase tracking-wide text-slate-800">{{ __('sa.auth.login_title') }}</h1>
            <p class="mt-0.5 text-xs text-slate-500">{{ __('sa.auth.toolbar_sub') }}</p>
        </div>
        <div class="flex shrink-0 flex-wrap items-center gap-2">
            <a
                href="{{ route('home') }}"
                class="rounded-lg border border-slate-200 bg-white px-4 py-2 text-[10px] font-bold uppercase tracking-wider text-slate-600 shadow-sm hover:bg-slate-50"
            >
                {{ __('sa.home.badge') }}
            </a>
        </div>
    </div>
@endsection

@section('workspace_main')
    <div class="px-4 py-6 sm:px-8 lg:px-10">
        <div class="mx-auto max-w-lg">
            <div class="rounded-2xl border border-slate-200/90 bg-white p-6 shadow-sm sm:p-8">
                <span class="inline-flex items-center rounded-full bg-slate-100 px-3 py-1 text-[10px] font-bold uppercase tracking-widest text-slate-600">{{ __('sa.auth.login_title') }}</span>
                <h2 class="mt-4 text-xl font-bold text-slate-900">{{ __('sa.auth.login_title') }}</h2>
                <form method="POST" action="{{ route('login') }}" class="mt-6 space-y-5">
                    @csrf
                    <div>
                        <label for="email" class="block text-xs font-bold uppercase tracking-wide text-slate-500">{{ __('sa.auth.email') }}</label>
                        <input
                            id="email"
                            name="email"
                            type="email"
                            value="{{ old('email') }}"
                            required
                            autofocus
                            autocomplete="username"
                            class="mt-1.5 block w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-900 shadow-sm placeholder:text-slate-400 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20"
                        >
                        @error('email')
                            <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label for="password" class="block text-xs font-bold uppercase tracking-wide text-slate-500">{{ __('sa.auth.password') }}</label>
                        <input
                            id="password"
                            name="password"
                            type="password"
                            required
                            autocomplete="current-password"
                            class="mt-1.5 block w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-900 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20"
                        >
                    </div>
                    <div class="flex items-center gap-2">
                        <input id="remember" name="remember" type="checkbox" value="1" class="rounded border-slate-300 text-slate-800 focus:ring-blue-500/30">
                        <label for="remember" class="text-sm text-slate-600">{{ __('sa.auth.remember') }}</label>
                    </div>
                    <button
                        type="submit"
                        class="inline-flex w-full justify-center rounded-xl bg-slate-800 px-6 py-2.5 text-sm font-bold text-white shadow-sm transition hover:bg-slate-700 sm:w-auto"
                    >
                        {{ __('sa.auth.submit_login') }}
                    </button>
                </form>
                <p class="mt-8 border-t border-slate-100 pt-6 text-sm text-slate-600">
                    {{ __('sa.auth.no_account') }}
                    <a href="{{ route('register') }}" class="font-semibold text-slate-800 underline decoration-slate-300 underline-offset-2 hover:text-slate-950">{{ __('sa.auth.register_title') }}</a>
                </p>
            </div>
        </div>
    </div>
@endsection
