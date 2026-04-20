@extends('layouts.sa_workspace')

@section('workspace_sidebar_heading', __('sa.dashboard.sidebar_heading'))

@section('title', __('sa.njk.page_title').' — '.config('app.name'))

@section('workspace_sidebar')
    @include('sa-map.partials.cabinet-sidebar-njk')
@endsection

@section('workspace_toolbar')
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="min-w-0">
            <h1 class="truncate text-lg font-bold uppercase tracking-wide text-slate-800">{{ __('sa.njk.page_title') }}</h1>
            <p class="mt-0.5 text-xs text-slate-500">{{ __('sa.njk.toolbar_sub') }}</p>
        </div>
        <div class="flex shrink-0 flex-wrap items-center gap-2">
            @include('partials.locale-switcher')
            <a
                href="{{ route('dashboard') }}#njk"
                class="rounded-lg border border-slate-200 bg-white px-4 py-2 text-[10px] font-bold uppercase tracking-wider text-slate-600 shadow-sm hover:bg-slate-50"
            >
                {{ __('sa.dashboard.page_title') }}
            </a>
            <a
                href="{{ route('njk-templates.create') }}"
                class="rounded-xl bg-slate-800 px-4 py-2 text-[10px] font-bold uppercase tracking-wider text-white shadow-sm hover:bg-slate-700"
            >
                {{ __('sa.njk.add_template') }}
            </a>
        </div>
    </div>
@endsection

@section('workspace_main')
    <div class="px-4 py-6 sm:px-8 lg:px-10 space-y-12">
        {{-- Предустановленные (ТЗ: только чтение) --}}
        <section aria-labelledby="njk-heading-system">
            <div class="mb-4 max-w-4xl">
                <h2 id="njk-heading-system" class="text-xl font-bold text-slate-900">{{ __('sa.njk.section_system') }}</h2>
                <p class="mt-1 text-sm leading-relaxed text-slate-600">{{ __('sa.njk.section_system_lead') }}</p>
            </div>
            @if ($systemTemplates->isEmpty())
                <div class="rounded-2xl border border-dashed border-slate-300 bg-slate-50 px-4 py-10 text-center text-sm text-slate-600">
                    {{ __('sa.njk.section_system_empty') }}
                </div>
            @else
                <ul class="space-y-4">
                    @foreach ($systemTemplates as $tpl)
                        <li class="rounded-2xl border border-sky-200/80 bg-sky-50/40 p-5 shadow-sm sm:p-6">
                            <div class="flex flex-wrap items-start justify-between gap-4">
                                <div class="min-w-0 flex-1">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span class="inline-flex items-center rounded-full bg-sky-200/80 px-2.5 py-0.5 text-[10px] font-bold uppercase tracking-wide text-sky-950">{{ __('sa.njk.badge_system') }}</span>
                                        <span class="inline-flex items-center rounded-full bg-slate-200 px-2.5 py-0.5 text-[10px] font-bold uppercase tracking-wide text-slate-700">{{ __('sa.njk.readonly_badge') }}</span>
                                        <h3 class="text-lg font-bold text-slate-900">{{ $tpl->title }}</h3>
                                    </div>
                                    <p class="mt-1 font-mono text-xs text-slate-600">{{ $tpl->filename }}</p>
                                    <p class="mt-2 text-xs text-slate-500">{{ __('sa.njk.readonly_hint') }}</p>
                                    <p class="mt-1 text-xs text-slate-500">
                                        {{ __('sa.dashboard.updated') }} {{ $tpl->updated_at->translatedFormat('d.m.Y H:i') }}
                                    </p>
                                </div>
                                <div class="flex shrink-0 flex-wrap items-center gap-2">
                                    <a
                                        href="{{ route('njk-templates.show', $tpl) }}"
                                        class="rounded-lg border border-slate-200 bg-white px-3 py-2 text-[10px] font-bold uppercase tracking-wider text-slate-600 shadow-sm hover:bg-slate-50"
                                    >
                                        {{ __('sa.njk.view') }}
                                    </a>
                                    <a
                                        href="{{ route('njk-templates.download', $tpl) }}"
                                        class="rounded-lg border border-slate-200 bg-white px-3 py-2 text-[10px] font-bold uppercase tracking-wider text-slate-600 shadow-sm hover:bg-slate-50"
                                    >
                                        {{ __('sa.njk.download') }}
                                    </a>
                                </div>
                            </div>
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>

        {{-- Пользовательские (ТЗ: полный CRUD) --}}
        <section aria-labelledby="njk-heading-user">
            <div class="mb-4 max-w-4xl">
                <h2 id="njk-heading-user" class="text-xl font-bold text-slate-900">{{ __('sa.njk.section_user') }}</h2>
                <p class="mt-1 text-sm leading-relaxed text-slate-600">{{ __('sa.njk.section_user_lead') }}</p>
            </div>
            @if ($userTemplates->isEmpty())
                <div class="rounded-2xl border border-dashed border-emerald-300/80 bg-emerald-50/30 px-4 py-10 text-center text-sm text-slate-600">
                    {{ __('sa.njk.user_empty') }}
                    <div class="mt-4">
                        <a
                            href="{{ route('njk-templates.create') }}"
                            class="inline-flex rounded-xl bg-slate-800 px-6 py-2.5 text-sm font-bold text-white shadow-sm hover:bg-slate-700"
                        >
                            {{ __('sa.njk.add_template') }}
                        </a>
                    </div>
                </div>
            @else
                <ul class="space-y-4">
                    @foreach ($userTemplates as $tpl)
                        <li class="rounded-2xl border border-slate-200/90 bg-white p-5 shadow-sm sm:p-6">
                            <div class="flex flex-wrap items-start justify-between gap-4">
                                <div class="min-w-0 flex-1">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span class="inline-flex items-center rounded-full bg-emerald-100 px-2.5 py-0.5 text-[10px] font-bold uppercase tracking-wide text-emerald-900">{{ __('sa.njk.badge_own') }}</span>
                                        <h3 class="text-lg font-bold text-slate-900">{{ $tpl->title }}</h3>
                                    </div>
                                    <p class="mt-1 font-mono text-xs text-slate-600">{{ $tpl->filename }}</p>
                                    <p class="mt-2 text-xs text-slate-500">
                                        {{ __('sa.dashboard.updated') }} {{ $tpl->updated_at->translatedFormat('d.m.Y H:i') }}
                                    </p>
                                </div>
                                <div class="flex shrink-0 flex-wrap items-center gap-2">
                                    <a
                                        href="{{ route('njk-templates.show', $tpl) }}"
                                        class="rounded-lg border border-slate-200 bg-white px-3 py-2 text-[10px] font-bold uppercase tracking-wider text-slate-600 shadow-sm hover:bg-slate-50"
                                    >
                                        {{ __('sa.njk.view') }}
                                    </a>
                                    <a
                                        href="{{ route('njk-templates.download', $tpl) }}"
                                        class="rounded-lg border border-slate-200 bg-white px-3 py-2 text-[10px] font-bold uppercase tracking-wider text-slate-600 shadow-sm hover:bg-slate-50"
                                    >
                                        {{ __('sa.njk.download') }}
                                    </a>
                                    @can('update', $tpl)
                                        <a
                                            href="{{ route('njk-templates.edit', $tpl) }}"
                                            class="rounded-lg border border-slate-200 bg-white px-3 py-2 text-[10px] font-bold uppercase tracking-wider text-slate-600 shadow-sm hover:bg-slate-50"
                                        >
                                            {{ __('sa.njk.edit') }}
                                        </a>
                                    @endcan
                                    @can('delete', $tpl)
                                        <form
                                            method="post"
                                            action="{{ route('njk-templates.destroy', $tpl) }}"
                                            class="inline"
                                            data-confirm="{{ __('sa.njk.delete_confirm') }}"
                                            onsubmit="return confirm(this.dataset.confirm);"
                                        >
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-xs font-semibold text-red-600 underline hover:text-red-800">{{ __('sa.njk.delete') }}</button>
                                        </form>
                                    @endcan
                                </div>
                            </div>
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>
    </div>
@endsection
