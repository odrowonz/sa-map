@extends('layouts.sa_workspace')

@section('workspace_sidebar_heading', __('sa.dashboard.sidebar_heading'))

@section('title', __('sa.dashboard.page_title').' — '.config('app.name'))

@section('workspace_sidebar')
    @php($dash = route('dashboard'))
    <div class="space-y-0.5">
        <a
            href="{{ $dash }}#overview"
            data-cabinet-section="overview"
            class="cabinet-nav-link flex w-full items-center rounded-lg border-l-4 border-transparent px-3 py-2.5 text-left text-xs font-semibold text-slate-300 transition-colors hover:bg-slate-700/60"
        >
            {{ __('sa.dashboard.nav_overview') }}
        </a>
        <a
            href="{{ $dash }}#projects"
            data-cabinet-section="projects"
            class="cabinet-nav-link flex w-full items-center rounded-lg border-l-4 border-transparent px-3 py-2.5 text-left text-xs font-semibold text-slate-300 transition-colors hover:bg-slate-700/60"
        >
            {{ __('sa.dashboard.nav_projects') }}
        </a>
        <a
            href="{{ route('njk-templates.index') }}"
            class="cabinet-nav-link flex w-full items-center rounded-lg border-l-4 border-transparent px-3 py-2.5 text-left text-xs font-semibold text-slate-300 transition-colors hover:bg-slate-700/60"
        >
            {{ __('sa.dashboard.nav_njk') }}
        </a>
        <a
            href="{{ $dash }}#profile"
            data-cabinet-section="profile"
            class="cabinet-nav-link flex w-full items-center rounded-lg border-l-4 border-transparent px-3 py-2.5 text-left text-xs font-semibold text-slate-300 transition-colors hover:bg-slate-700/60"
        >
            {{ __('sa.dashboard.nav_profile') }}
        </a>
    </div>
@endsection

@section('workspace_toolbar')
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="min-w-0">
            <h1 class="truncate text-lg font-bold uppercase tracking-wide text-slate-800">{{ __('sa.dashboard.page_title') }}</h1>
            <p class="mt-0.5 text-xs text-slate-500">{{ __('sa.dashboard.toolbar_sub') }}</p>
        </div>
        <div class="flex shrink-0 flex-wrap items-center gap-2">
            @include('partials.locale-switcher')
            <a
                href="{{ route('home') }}"
                class="rounded-lg border border-slate-200 bg-white px-4 py-2 text-[10px] font-bold uppercase tracking-wider text-slate-600 shadow-sm hover:bg-slate-50"
            >
                {{ __('sa.dashboard.home') }}
            </a>
        </div>
    </div>
@endsection

@section('workspace_main')
    <div class="px-4 py-6 sm:px-8 lg:px-10">
        <section id="overview" class="cabinet-section scroll-mt-6">
            <header class="mb-6 max-w-4xl">
                <span class="inline-flex items-center rounded-full bg-blue-100 px-3 py-1 text-[10px] font-bold uppercase tracking-widest text-blue-800">
                    {{ __('sa.dashboard.overview_badge') }}
                </span>
                <h2 class="mt-3 text-2xl font-extrabold tracking-tight text-slate-900 sm:text-3xl">
                    {{ __('sa.dashboard.overview_title') }}
                </h2>
                <p class="mt-2 text-lg italic text-slate-500">
                    {{ __('sa.dashboard.overview_lead') }}
                </p>
            </header>
            <p class="max-w-4xl text-sm leading-relaxed text-slate-600">
                {{ __('sa.dashboard.overview_intro') }}
                <a href="{{ route('home') }}" class="font-medium text-blue-600 hover:text-blue-800">{{ __('sa.dashboard.overview_home_link') }}</a>{{ __('sa.dashboard.overview_outro') }}
            </p>
        </section>

        <section id="projects" class="cabinet-section mt-14 scroll-mt-6">
            <div class="rounded-2xl border border-slate-200/90 bg-white p-6 shadow-sm sm:p-8">
                <span class="inline-flex items-center rounded-full bg-slate-100 px-3 py-1 text-[10px] font-bold uppercase tracking-widest text-slate-600">{{ __('sa.dashboard.projects_badge') }}</span>
                <h2 class="mt-4 text-xl font-bold text-slate-900">{{ __('sa.dashboard.projects_title') }}</h2>
                <p class="mt-2 text-sm text-slate-600">
                    {!! __('sa.dashboard.projects_lead', ['table' => '<code class="rounded-md bg-slate-100 px-1.5 py-0.5 font-mono text-xs">sa_projects</code>']) !!}
                </p>

                <form method="post" action="{{ route('projects.store') }}" class="mt-8 flex flex-col gap-4 border-t border-slate-100 pt-6 sm:flex-row sm:items-end">
                    @csrf
                    <div class="min-w-0 flex-1">
                        <label for="project-name" class="block text-[10px] font-bold uppercase tracking-wide text-slate-500">{{ __('sa.dashboard.project_name_label') }}</label>
                        <input
                            id="project-name"
                            name="name"
                            type="text"
                            value="{{ old('name') }}"
                            required
                            maxlength="500"
                            class="mt-1.5 block w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm shadow-sm placeholder:text-slate-400 focus:border-blue-400 focus:ring-2 focus:ring-blue-400/30 focus:outline-none"
                            placeholder="{{ __('sa.dashboard.project_placeholder') }}"
                        />
                        @error('name')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    <button type="submit" class="shrink-0 rounded-xl bg-slate-800 px-6 py-2.5 text-sm font-bold text-white shadow-sm hover:bg-slate-700">
                        {{ __('sa.dashboard.create_project') }}
                    </button>
                </form>

                @if ($projects->isEmpty())
                    <div class="mt-8 rounded-2xl border border-dashed border-slate-300 bg-slate-50 px-4 py-10 text-center text-sm text-slate-500">
                        {{ __('sa.dashboard.projects_empty') }}
                    </div>
                @else
                    <ul class="mt-8 divide-y divide-slate-200 rounded-2xl border border-slate-200 bg-slate-50/50">
                        @foreach ($projects as $project)
                            <li class="flex flex-wrap items-center justify-between gap-3 px-4 py-4 sm:px-5">
                                <div class="min-w-0">
                                    <p class="font-semibold text-slate-900">{{ $project->name }}</p>
                                    <p class="mt-0.5 text-xs text-slate-500">
                                        {{ __('sa.dashboard.updated') }} {{ $project->updated_at->translatedFormat('d.m.Y H:i') }}
                                        @if ($project->slug)
                                            · <span class="font-mono text-slate-600">{{ $project->slug }}</span>
                                        @endif
                                    </p>
                                </div>
                                <div class="flex shrink-0 flex-wrap items-center gap-3">
                                    <a
                                        href="{{ route('projects.show', $project) }}"
                                        class="rounded-lg bg-blue-600 px-4 py-2 text-xs font-bold uppercase tracking-wide text-white shadow-sm hover:bg-blue-700"
                                    >
                                        {{ __('sa.dashboard.open_map') }}
                                    </a>
                                    <form
                                        method="post"
                                        action="{{ route('projects.destroy', $project) }}"
                                        class="inline"
                                        data-confirm="{{ __('sa.dashboard.delete_project_confirm') }}"
                                        onsubmit="return confirm(this.dataset.confirm);"
                                    >
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-xs font-semibold text-red-600 underline hover:text-red-800">
                                            {{ __('sa.dashboard.delete_project') }}
                                        </button>
                                    </form>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </section>

        <section id="njk" class="cabinet-section mt-14 scroll-mt-6">
            <div class="rounded-2xl border border-slate-200/90 bg-white p-6 shadow-sm sm:p-8">
                <span class="inline-flex items-center rounded-full bg-slate-100 px-3 py-1 text-[10px] font-bold uppercase tracking-widest text-slate-600">{{ __('sa.dashboard.njk_badge') }}</span>
                <h2 class="mt-4 text-xl font-bold text-slate-900">{{ __('sa.dashboard.njk_title') }}</h2>
                <p class="mt-2 text-sm leading-relaxed text-slate-600">
                    {!! __('sa.dashboard.njk_lead', ['table' => '<code class="rounded-md bg-slate-100 px-1.5 py-0.5 font-mono text-xs">sa_njk_templates</code>']) !!}
                </p>
                <div class="mt-8">
                    <a
                        href="{{ route('njk-templates.index') }}"
                        class="inline-flex rounded-xl bg-slate-800 px-6 py-2.5 text-sm font-bold text-white shadow-sm hover:bg-slate-700"
                    >
                        {{ __('sa.dashboard.njk_open') }}
                    </a>
                </div>
            </div>
        </section>

        <section id="profile" class="cabinet-section mt-14 scroll-mt-6 pb-8">
            <div class="rounded-2xl border border-slate-200/90 bg-white p-6 shadow-sm sm:p-8">
                <span class="inline-flex items-center rounded-full bg-slate-100 px-3 py-1 text-[10px] font-bold uppercase tracking-widest text-slate-600">{{ __('sa.dashboard.profile_badge') }}</span>
                <h2 class="mt-4 text-xl font-bold text-slate-900">{{ __('sa.dashboard.profile_title') }}</h2>
                <dl class="mt-6 grid gap-6 text-sm sm:grid-cols-2">
                    <div class="rounded-xl border border-slate-100 bg-slate-50/80 px-4 py-3">
                        <dt class="text-[10px] font-bold uppercase tracking-wide text-slate-500">{{ __('sa.dashboard.profile_email') }}</dt>
                        <dd class="mt-1 font-semibold text-slate-900">{{ $user->email }}</dd>
                    </div>
                    <div class="rounded-xl border border-slate-100 bg-slate-50/80 px-4 py-3">
                        <dt class="text-[10px] font-bold uppercase tracking-wide text-slate-500">{{ __('sa.dashboard.profile_name') }}</dt>
                        <dd class="mt-1 font-semibold text-slate-900">{{ $user->name ?: __('sa.generic.dash') }}</dd>
                    </div>
                    <div class="rounded-xl border border-slate-100 bg-slate-50/80 px-4 py-3 sm:col-span-2">
                        <dt class="text-[10px] font-bold uppercase tracking-wide text-slate-500">{{ __('sa.dashboard.profile_locale') }}</dt>
                        <dd class="mt-1 font-semibold text-slate-900">{{ $user->locale }} <span class="text-slate-400">{{ __('sa.dashboard.profile_locale_note') }}</span></dd>
                    </div>
                </dl>
            </div>
        </section>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var links = document.querySelectorAll('a[data-cabinet-section]');
            var scrollRoot = document.getElementById('workspace-main-scroll');

            function hashKey() {
                var h = window.location.hash;
                if (!h || h === '#') return 'overview';
                return h.replace(/^#/, '');
            }

            function paint() {
                var key = hashKey();
                links.forEach(function (a) {
                    var on = a.getAttribute('data-cabinet-section') === key;
                    a.classList.toggle('border-blue-500', on);
                    a.classList.toggle('bg-slate-700', on);
                    a.classList.toggle('text-white', on);
                    a.classList.toggle('shadow-lg', on);
                    a.classList.toggle('border-transparent', !on);
                    a.classList.toggle('text-slate-300', !on);
                    if (on) {
                        a.setAttribute('aria-current', 'true');
                    } else {
                        a.removeAttribute('aria-current');
                    }
                });
            }

            window.addEventListener('hashchange', paint);
            paint();

            links.forEach(function (a) {
                a.addEventListener('click', function () {
                    requestAnimationFrame(function () {
                        var id = a.getAttribute('href');
                        if (!id || id.indexOf('#') === -1) return;
                        var el = document.getElementById(id.slice(1));
                        if (el && scrollRoot) {
                            el.scrollIntoView({ behavior: 'smooth', block: 'start' });
                        }
                    });
                });
            });
        });
    </script>
@endpush
