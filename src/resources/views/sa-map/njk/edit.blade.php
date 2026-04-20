@extends('layouts.sa_workspace')

@section('workspace_sidebar_heading', __('sa.dashboard.sidebar_heading'))

@section('title', __('sa.njk.edit_title').' — '.config('app.name'))

@section('workspace_sidebar')
    @include('sa-map.partials.cabinet-sidebar-njk')
@endsection

@section('workspace_toolbar')
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="min-w-0">
            <h1 class="truncate text-lg font-bold uppercase tracking-wide text-slate-800">{{ __('sa.njk.edit_title') }}</h1>
            <p class="mt-0.5 text-xs text-slate-500">{{ $template->title }}</p>
        </div>
        <div class="flex shrink-0 flex-wrap items-center gap-2">
            <a
                href="{{ route('njk-templates.index') }}"
                class="rounded-lg border border-slate-200 bg-white px-4 py-2 text-[10px] font-bold uppercase tracking-wider text-slate-600 shadow-sm hover:bg-slate-50"
            >
                {{ __('sa.njk.back_to_list') }}
            </a>
        </div>
    </div>
@endsection

@section('workspace_main')
    <div class="px-4 py-6 sm:px-8 lg:px-10">
        <form id="njk-template-form" method="post" action="{{ route('njk-templates.update', $template) }}" class="max-w-4xl">
            @csrf
            @method('PUT')
            <div class="rounded-2xl border border-slate-200/90 bg-white p-6 shadow-sm sm:p-8">
                @include('sa-map.njk._form', ['template' => $template])
                <div class="mt-8 flex flex-wrap gap-3 border-t border-slate-100 pt-6">
                    <button type="submit" class="rounded-xl bg-slate-800 px-6 py-2.5 text-sm font-bold text-white shadow-sm hover:bg-slate-700">
                        {{ __('sa.njk.save_update') }}
                    </button>
                    <a
                        href="{{ route('njk-templates.index') }}"
                        class="rounded-lg border border-slate-200 bg-white px-4 py-2.5 text-[10px] font-bold uppercase tracking-wider text-slate-600 shadow-sm hover:bg-slate-50"
                    >
                        {{ __('sa.njk.cancel') }}
                    </a>
                </div>
            </div>
        </form>
    </div>
@endsection

@push('scripts')
    @vite(['resources/js/njk-editor.js'])
@endpush
