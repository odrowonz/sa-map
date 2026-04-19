@extends('layouts.app')

@section('title', config('app.name'))

@section('content')
    <h1 class="text-2xl font-semibold text-slate-800">Карта системного аналитика</h1>
    <p class="mt-4 text-slate-600">Laravel + Blade. Дальше: проекты, уровни L1–L10 по ТЗ.</p>
    <p class="mt-2 text-sm text-slate-500">Версия приложения: dev</p>
    @auth
        <p class="mt-6 text-sm text-slate-600">
            Вы вошли как <strong>{{ Auth::user()->email }}</strong>.
            <a href="{{ route('dashboard') }}" class="ml-1 font-medium text-slate-800 underline">Перейти в личный кабинет</a>.
        </p>
    @else
        <p class="mt-6 text-sm text-slate-600">
            <a href="{{ route('login') }}" class="font-medium text-slate-800 underline">Войдите</a>
            или
            <a href="{{ route('register') }}" class="font-medium text-slate-800 underline">зарегистрируйтесь</a>.
        </p>
    @endauth
@endsection
