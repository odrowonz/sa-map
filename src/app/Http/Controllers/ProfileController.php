<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\Rule;

class ProfileController extends Controller
{
    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();
        $available = config('app.available_locales', ['ru', 'en', 'kk']);

        $emailChanged = strcasecmp(
            trim((string) $request->input('email', '')),
            (string) $user->email
        ) !== 0;

        $rules = [
            'name' => ['nullable', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('sa_users', 'email')->ignore($user->id)],
            'locale' => ['required', 'string', 'in:'.implode(',', $available)],
        ];

        if ($request->filled('password') || $emailChanged) {
            $rules['current_password'] = ['required', 'current_password'];
        }

        if ($request->filled('password')) {
            $rules['password'] = ['required', 'string', 'confirmed', Password::defaults()];
        }

        $messages = [
            'email.unique' => __('sa.dashboard.profile_email_save_failed'),
        ];

        $validated = $request->validate($rules, $messages);

        $user->name = $validated['name'] ?? null;
        $user->email = $validated['email'];
        $user->locale = $validated['locale'];

        if (! empty($validated['password'] ?? null)) {
            $user->password = $validated['password'];
        }

        $user->save();

        $request->session()->put('locale', $user->locale);

        return redirect()->to(route('dashboard').'#profile')->with('status', __('sa.dashboard.profile_updated'));
    }
}
