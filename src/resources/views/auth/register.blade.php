@extends('layouts.app')

@section('title', 'Регистрация — ' . config('app.name'))

@section('content')
    <h1 class="text-2xl font-semibold text-slate-800">Регистрация</h1>
    <form method="POST" action="{{ route('register') }}" class="mt-8 space-y-6">
        @csrf
        <div>
            <label for="name" class="block text-sm font-medium text-slate-700">Имя (необязательно)</label>
            <input id="name" name="name" type="text" value="{{ old('name') }}" autocomplete="name"
                class="mt-1 block w-full rounded-md border border-slate-300 px-3 py-2 shadow-sm focus:border-slate-500 focus:outline-none focus:ring-1 focus:ring-slate-500">
            @error('name')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>
        <div>
            <label for="email" class="block text-sm font-medium text-slate-700">Email</label>
            <input id="email" name="email" type="email" value="{{ old('email') }}" required autocomplete="username"
                class="mt-1 block w-full rounded-md border border-slate-300 px-3 py-2 shadow-sm focus:border-slate-500 focus:outline-none focus:ring-1 focus:ring-slate-500">
            @error('email')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>
        <div>
            <label for="password" class="block text-sm font-medium text-slate-700">Пароль</label>
            <input id="password" name="password" type="password" required autocomplete="new-password"
                class="mt-1 block w-full rounded-md border border-slate-300 px-3 py-2 shadow-sm focus:border-slate-500 focus:outline-none focus:ring-1 focus:ring-slate-500">
            @error('password')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>
        <div>
            <label for="password_confirmation" class="block text-sm font-medium text-slate-700">Пароль ещё раз</label>
            <input id="password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password"
                class="mt-1 block w-full rounded-md border border-slate-300 px-3 py-2 shadow-sm focus:border-slate-500 focus:outline-none focus:ring-1 focus:ring-slate-500">
        </div>
        <button type="submit" class="rounded-md bg-slate-800 px-4 py-2 text-sm font-medium text-white hover:bg-slate-700">
            Зарегистрироваться
        </button>
    </form>
    <p class="mt-8 text-sm text-slate-600">
        Уже есть аккаунт? <a href="{{ route('login') }}" class="font-medium text-slate-800 underline">Вход</a>
    </p>
@endsection
