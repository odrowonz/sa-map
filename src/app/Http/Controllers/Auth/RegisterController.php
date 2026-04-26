<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class RegisterController extends Controller
{
    public function create(): View
    {
        return view('auth.register');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate(
            [
                'name' => ['nullable', 'string', 'max:255'],
                'email' => ['required', 'string', 'email', 'max:255', 'unique:sa_users,email'],
                'password' => ['required', 'string', 'confirmed', Password::defaults()],
            ],
            [
                'email.unique' => __('sa.auth.register_email_failed'),
            ]
        );

        $user = DB::transaction(function () use ($validated) {
            $isFirstUser = User::query()->lockForUpdate()->count() === 0;

            return User::create([
                'name' => $validated['name'] ?? null,
                'email' => $validated['email'],
                'password' => $validated['password'],
                'locale' => 'ru',
                'is_admin' => $isFirstUser,
            ]);
        });

        Auth::login($user);

        $request->session()->regenerate();

        return redirect()->route('dashboard');
    }
}
