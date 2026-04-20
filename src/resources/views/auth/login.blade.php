@extends('layouts.app')

@section('title', __('sa.auth.login_title').' — '.config('app.name'))

@section('content')
    <h1 class="text-2xl font-semibold text-slate-800">{{ __('sa.auth.login_title') }}</h1>
    <form method="POST" action="{{ route('login') }}" class="mt-8 space-y-6">
        @csrf
        <div>
            <label for="email" class="block text-sm font-medium text-slate-700">{{ __('sa.auth.email') }}</label>
            <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus autocomplete="username"
                class="mt-1 block w-full rounded-md border border-slate-300 px-3 py-2 shadow-sm focus:border-slate-500 focus:outline-none focus:ring-1 focus:ring-slate-500">
            @error('email')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>
        <div>
            <label for="password" class="block text-sm font-medium text-slate-700">{{ __('sa.auth.password') }}</label>
            <input id="password" name="password" type="password" required autocomplete="current-password"
                class="mt-1 block w-full rounded-md border border-slate-300 px-3 py-2 shadow-sm focus:border-slate-500 focus:outline-none focus:ring-1 focus:ring-slate-500">
        </div>
        <div class="flex items-center gap-2">
            <input id="remember" name="remember" type="checkbox" value="1" class="rounded border-slate-300">
            <label for="remember" class="text-sm text-slate-600">{{ __('sa.auth.remember') }}</label>
        </div>
        <button type="submit" class="rounded-md bg-slate-800 px-4 py-2 text-sm font-medium text-white hover:bg-slate-700">
            {{ __('sa.auth.submit_login') }}
        </button>
    </form>
    <p class="mt-8 text-sm text-slate-600">
        {{ __('sa.auth.no_account') }} <a href="{{ route('register') }}" class="font-medium text-slate-800 underline">{{ __('sa.auth.register_title') }}</a>
    </p>
@endsection
