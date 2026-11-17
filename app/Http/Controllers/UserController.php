<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Accounts/Index', [
            'users' => User::orderBy('name')
                ->get(['id', 'name', 'email', 'role', 'is_active']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'role' => ['required', Rule::in(array_column(Role::cases(), 'value'))],
        ]);

        User::create($data + ['is_active' => true]);

        return Redirect::back()->with('success', 'User created.');
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate([
            'role' => ['sometimes', 'required', Rule::in(array_column(Role::cases(), 'value'))],
            'is_active' => ['sometimes', 'boolean'],
            'password' => ['sometimes', 'required', 'string', 'min:8'],
        ]);

        $losesAdmin = (isset($data['role']) && $data['role'] !== Role::Admin->value)
            || (array_key_exists('is_active', $data) && ! $data['is_active']);

        if ($losesAdmin && $user->role === Role::Admin && $user->is_active
            && User::where('role', Role::Admin)->where('is_active', true)->count() <= 1) {
            return Redirect::back()->with('error', 'At least one active admin must remain.');
        }

        $user->update($data);

        return Redirect::back()->with('success', 'User updated.');
    }
}
