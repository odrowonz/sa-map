<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ElementController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\ProjectWorkspaceController;
use Illuminate\Support\Facades\Route;

$home = static fn () => view('sa-map.home');

$prefix = trim((string) parse_url(config('app.url'), PHP_URL_PATH), '/');

$routes = static function () use ($home) {
    Route::get('/', $home)->name('home');

    Route::middleware('guest')->group(function () {
        Route::get('login', [LoginController::class, 'create'])->name('login');
        Route::post('login', [LoginController::class, 'store']);
        Route::get('register', [RegisterController::class, 'create'])->name('register');
        Route::post('register', [RegisterController::class, 'store']);
    });

    Route::post('logout', [LoginController::class, 'destroy'])->middleware('auth')->name('logout');

    Route::middleware('auth')->group(function () {
        Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');
        Route::post('projects', [ProjectController::class, 'store'])->name('projects.store');
        Route::get('projects/{project}', [ProjectWorkspaceController::class, 'show'])->name('projects.show');
        Route::get('projects/{project}/level/{level}', [ProjectWorkspaceController::class, 'level'])
            ->whereNumber('level')
            ->name('projects.level');
        Route::post('projects/{project}/elements', [ElementController::class, 'store'])->name('elements.store');
        Route::patch('projects/{project}/elements/{element}', [ElementController::class, 'update'])->name('elements.update');
        Route::delete('projects/{project}/elements/{element}', [ElementController::class, 'destroy'])->name('elements.destroy');
        Route::delete('projects/{project}', [ProjectController::class, 'destroy'])->name('projects.destroy');
    });
};

if ($prefix !== '') {
    Route::prefix($prefix)->group($routes);
    Route::get('/', $home);
} else {
    $routes();
}
