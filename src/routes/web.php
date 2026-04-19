<?php

use Illuminate\Support\Facades\Route;

$home = static fn () => view('sa-map.home');

// Прямой заход на …/public/index.php даёт путь «/»; подкаталог в APP_URL даёт путь «sa-map».
$prefix = trim((string) parse_url(config('app.url'), PHP_URL_PATH), '/');

if ($prefix !== '') {
    Route::prefix($prefix)->group(function () use ($home) {
        Route::get('/', $home)->name('home');
    });
    Route::get('/', $home);
} else {
    Route::get('/', $home)->name('home');
}
