<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\AttachmentController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ElementController;
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\NjkTemplateController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\ProjectDataExchangeController;
use App\Http\Controllers\ProjectWorkspaceController;
use Illuminate\Support\Facades\Route;

$home = static fn () => view('sa-map.home');

$prefix = trim((string) parse_url(config('app.url'), PHP_URL_PATH), '/');

$routes = static function () use ($home) {
    Route::get('/', $home)->name('home');

    Route::post('locale', [LocaleController::class, 'update'])->name('locale.update');

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
        Route::post('projects/{project}/elements/{element}/attachments', [AttachmentController::class, 'store'])->name('attachments.store');
        Route::get('projects/{project}/attachments/{attachment}/file', [AttachmentController::class, 'file'])->name('attachments.file');
        Route::delete('projects/{project}/attachments/{attachment}', [AttachmentController::class, 'destroy'])->name('attachments.destroy');
        Route::delete('projects/{project}', [ProjectController::class, 'destroy'])->name('projects.destroy');
        Route::post('projects/{project}/export-data', [ProjectDataExchangeController::class, 'export'])->name('projects.export-data');
        Route::post('projects/{project}/import-data', [ProjectDataExchangeController::class, 'import'])->name('projects.import-data');

        Route::get('njk-templates', [NjkTemplateController::class, 'index'])->name('njk-templates.index');
        Route::get('njk-templates/create', [NjkTemplateController::class, 'create'])->name('njk-templates.create');
        Route::post('njk-templates', [NjkTemplateController::class, 'store'])->name('njk-templates.store');
        Route::get('njk-templates/{njk_template}', [NjkTemplateController::class, 'show'])->name('njk-templates.show');
        Route::get('njk-templates/{njk_template}/edit', [NjkTemplateController::class, 'edit'])->name('njk-templates.edit');
        Route::put('njk-templates/{njk_template}', [NjkTemplateController::class, 'update'])->name('njk-templates.update');
        Route::delete('njk-templates/{njk_template}', [NjkTemplateController::class, 'destroy'])->name('njk-templates.destroy');
        Route::get('njk-templates/{njk_template}/download', [NjkTemplateController::class, 'download'])->name('njk-templates.download');
    });
};

if ($prefix !== '') {
    Route::prefix($prefix)->group($routes);
    Route::get('/', $home);
} else {
    $routes();
}
