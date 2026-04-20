@extends('layouts.sa_workspace')

@section('workspace_sidebar_heading', __('sa.home.sidebar_heading'))

@section('title', config('app.name'))

@section('workspace_sidebar')
    <div class="space-y-0.5">
        <span
            class="flex w-full items-center rounded-lg border-l-4 border-blue-500 bg-slate-700 px-3 py-2.5 text-left text-xs font-semibold text-white shadow-lg"
            aria-current="page"
        >
            {{ __('sa.home.nav_current') }}
        </span>
        @auth
            <a
                href="{{ route('dashboard') }}"
                class="cabinet-nav-link flex w-full items-center rounded-lg border-l-4 border-transparent px-3 py-2.5 text-left text-xs font-semibold text-slate-300 transition-colors hover:bg-slate-700/60"
            >
                {{ __('sa.nav.dashboard') }}
            </a>
        @else
            <a
                href="{{ route('login') }}"
                class="cabinet-nav-link flex w-full items-center rounded-lg border-l-4 border-transparent px-3 py-2.5 text-left text-xs font-semibold text-slate-300 transition-colors hover:bg-slate-700/60"
            >
                {{ __('sa.nav.login') }}
            </a>
            <a
                href="{{ route('register') }}"
                class="cabinet-nav-link flex w-full items-center rounded-lg border-l-4 border-transparent px-3 py-2.5 text-left text-xs font-semibold text-slate-300 transition-colors hover:bg-slate-700/60"
            >
                {{ __('sa.nav.register') }}
            </a>
        @endauth
    </div>
@endsection

@section('workspace_toolbar')
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="min-w-0">
            <h1 class="truncate text-lg font-bold uppercase tracking-wide text-slate-800">{{ __('sa.home.title') }}</h1>
            <p class="mt-0.5 text-xs text-slate-500">{{ __('sa.home.toolbar_sub') }}</p>
        </div>
        <div class="flex shrink-0 flex-wrap items-center gap-2">
            @include('partials.locale-switcher')
            @auth
                <a
                    href="{{ route('dashboard') }}"
                    class="rounded-lg border border-slate-200 bg-white px-4 py-2 text-[10px] font-bold uppercase tracking-wider text-slate-600 shadow-sm hover:bg-slate-50"
                >
                    {{ __('sa.nav.dashboard') }}
                </a>
            @else
                <a
                    href="{{ route('login') }}"
                    class="rounded-lg border border-slate-200 bg-white px-4 py-2 text-[10px] font-bold uppercase tracking-wider text-slate-600 shadow-sm hover:bg-slate-50"
                >
                    {{ __('sa.nav.login') }}
                </a>
                <a
                    href="{{ route('register') }}"
                    class="rounded-lg border border-slate-200 bg-white px-4 py-2 text-[10px] font-bold uppercase tracking-wider text-slate-600 shadow-sm hover:bg-slate-50"
                >
                    {{ __('sa.nav.register') }}
                </a>
            @endauth
        </div>
    </div>
@endsection

@section('workspace_main')
    <div class="px-4 py-6 sm:px-8 lg:px-10">
        <section class="scroll-mt-6">
            <header class="mb-6 max-w-4xl">
                <span class="inline-flex items-center rounded-full bg-blue-100 px-3 py-1 text-[10px] font-bold uppercase tracking-widest text-blue-800">
                    {{ __('sa.home.badge') }}
                </span>
                <h2 class="mt-3 text-2xl font-extrabold tracking-tight text-slate-900 sm:text-3xl">
                    {{ __('sa.home.title') }}
                </h2>
                <p class="mt-2 text-lg italic text-slate-500">
                    {{ __('sa.home.lead') }}
                </p>
            </header>

            <div class="max-w-4xl rounded-2xl border border-slate-200/90 bg-white p-6 shadow-sm sm:p-8">
                <span class="inline-flex items-center rounded-full bg-slate-100 px-3 py-1 text-[10px] font-bold uppercase tracking-widest text-slate-600">{{ __('sa.home.version') }}</span>

                @auth
                    <p class="mt-6 text-sm leading-relaxed text-slate-600">
                        {!! __('sa.home.logged_in_as', ['email' => '<strong>'.e(Auth::user()->email).'</strong>']) !!}
                    </p>
                    <div class="mt-6">
                        <a
                            href="{{ route('dashboard') }}"
                            class="inline-flex rounded-xl bg-slate-800 px-6 py-2.5 text-sm font-bold text-white shadow-sm hover:bg-slate-700"
                        >
                            {{ __('sa.home.go_dashboard') }}
                        </a>
                    </div>
                @else
                    <p class="mt-6 text-sm leading-relaxed text-slate-600">
                        {{ __('sa.home.login_or_register') }}
                    </p>
                    <div class="mt-6 flex flex-wrap gap-3">
                        <a
                            href="{{ route('login') }}"
                            class="inline-flex rounded-xl bg-slate-800 px-6 py-2.5 text-sm font-bold text-white shadow-sm hover:bg-slate-700"
                        >
                            {{ __('sa.auth.submit_login') }}
                        </a>
                        <a
                            href="{{ route('register') }}"
                            class="inline-flex rounded-lg border border-slate-200 bg-white px-4 py-2.5 text-[10px] font-bold uppercase tracking-wider text-slate-600 shadow-sm hover:bg-slate-50"
                        >
                            {{ __('sa.auth.register_title') }}
                        </a>
                    </div>
                @endauth
            </div>
        </section>
    </div>
@endsection
