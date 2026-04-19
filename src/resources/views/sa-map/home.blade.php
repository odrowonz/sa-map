<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'SA Map') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-100 antialiased">
    <main class="mx-auto max-w-3xl px-6 py-16">
        <h1 class="text-2xl font-semibold text-slate-800">Карта системного аналитика</h1>
        <p class="mt-4 text-slate-600">Laravel + Blade. Дальше: авторизация, проекты, уровни L1–L10 по ТЗ.</p>
        <p class="mt-2 text-sm text-slate-500">Версия приложения: dev</p>
    </main>
</body>
</html>
