@extends('layouts.sa_workspace')

@section('workspace_sidebar_heading', __('sa.dashboard.sidebar_heading'))

@section('title', $template->title.' — '.__('sa.njk.page_title').' — '.config('app.name'))

@section('workspace_sidebar')
    @include('sa-map.partials.cabinet-sidebar-njk')
@endsection

@section('workspace_toolbar')
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="min-w-0">
            <h1 class="truncate text-lg font-bold uppercase tracking-wide text-slate-800">{{ $template->title }}</h1>
            <p class="mt-0.5 font-mono text-xs text-slate-500">{{ $template->filename }}</p>
        </div>
        <div class="flex shrink-0 flex-wrap items-center gap-2">
            <a
                href="{{ route('njk-templates.index') }}"
                class="rounded-lg border border-slate-200 bg-white px-4 py-2 text-[10px] font-bold uppercase tracking-wider text-slate-600 shadow-sm hover:bg-slate-50"
            >
                {{ __('sa.njk.back_to_list') }}
            </a>
            <a
                href="{{ route('njk-templates.download', $template) }}"
                class="rounded-lg border border-slate-200 bg-white px-4 py-2 text-[10px] font-bold uppercase tracking-wider text-slate-600 shadow-sm hover:bg-slate-50"
            >
                {{ __('sa.njk.download') }}
            </a>
            @can('update', $template)
                <a
                    href="{{ route('njk-templates.edit', $template) }}"
                    class="rounded-xl bg-slate-800 px-4 py-2 text-[10px] font-bold uppercase tracking-wider text-white shadow-sm hover:bg-slate-700"
                >
                    {{ __('sa.njk.edit') }}
                </a>
            @endcan
        </div>
    </div>
@endsection

@section('workspace_main')
    <div class="px-4 py-6 sm:px-8 lg:px-10">
        <div class="mb-4 flex flex-wrap items-center gap-2">
            @if ($template->is_system)
                <span class="inline-flex items-center rounded-full bg-sky-200/80 px-2.5 py-0.5 text-[10px] font-bold uppercase tracking-wide text-sky-950">{{ __('sa.njk.badge_system') }}</span>
                <span class="inline-flex items-center rounded-full bg-slate-200 px-2.5 py-0.5 text-[10px] font-bold uppercase tracking-wide text-slate-700">{{ __('sa.njk.readonly_badge') }}</span>
            @else
                <span class="inline-flex items-center rounded-full bg-emerald-100 px-2.5 py-0.5 text-[10px] font-bold uppercase tracking-wide text-emerald-900">{{ __('sa.njk.badge_own') }}</span>
            @endif
        </div>
        <p class="mb-3 text-sm text-slate-600">{{ __('sa.njk.show_body_intro') }}</p>
        <div class="overflow-hidden rounded-xl border border-slate-200 bg-slate-50 shadow-inner">
            <pre class="max-h-[min(70vh,640px)] overflow-auto p-4 text-xs leading-relaxed text-slate-800 whitespace-pre-wrap break-words font-mono" tabindex="0">{{ $template->body }}</pre>
        </div>
    </div>
@endsection
