@php($dash = route('dashboard'))
<div class="space-y-0.5">
    <a
        href="{{ $dash }}#overview"
        class="cabinet-nav-link flex w-full items-center rounded-lg border-l-4 border-transparent px-3 py-2.5 text-left text-xs font-semibold text-slate-300 transition-colors hover:bg-slate-700/60"
    >
        {{ __('sa.dashboard.nav_overview') }}
    </a>
    <a
        href="{{ $dash }}#projects"
        class="cabinet-nav-link flex w-full items-center rounded-lg border-l-4 border-transparent px-3 py-2.5 text-left text-xs font-semibold text-slate-300 transition-colors hover:bg-slate-700/60"
    >
        {{ __('sa.dashboard.nav_projects') }}
    </a>
    <a
        href="{{ route('njk-templates.index') }}"
        class="flex w-full items-center rounded-lg border-l-4 px-3 py-2.5 text-left text-xs font-semibold transition-colors border-blue-500 bg-slate-700 text-white shadow-lg"
        aria-current="page"
    >
        {{ __('sa.dashboard.nav_njk') }}
    </a>
    <a
        href="{{ $dash }}#profile"
        class="cabinet-nav-link flex w-full items-center rounded-lg border-l-4 border-transparent px-3 py-2.5 text-left text-xs font-semibold text-slate-300 transition-colors hover:bg-slate-700/60"
    >
        {{ __('sa.dashboard.nav_profile') }}
    </a>
</div>
