<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class LocaleController extends Controller
{
    public function update(Request $request): RedirectResponse
    {
        $available = config('app.available_locales', ['ru', 'en', 'kk']);
        $validated = $request->validate([
            'locale' => ['required', 'string', 'in:'.implode(',', $available)],
        ]);

        $locale = $validated['locale'];
        $request->session()->put('locale', $locale);

        if ($request->user() !== null) {
            $request->user()->update(['locale' => $locale]);
        }

        return back();
    }
}
