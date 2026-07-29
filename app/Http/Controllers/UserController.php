<?php

namespace App\Http\Controllers;

use App\Http\Requests\User\StoreUserRequest;
use App\Http\Requests\User\UpdateUserRequest;
use App\Models\User;
use App\Services\User\UserService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function __construct(private readonly UserService $users) {}

    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', User::class);

        return Inertia::render('Users/Index', [
            'users' => $this->users->paginate($request->only(['search', 'role', 'is_active'])),
            'roles' => Role::orderBy('name')->pluck('name'),
            'filters' => $request->only(['search', 'role', 'is_active']),
        ]);
    }

    public function create(): Response
    {
        Gate::authorize('create', User::class);

        return Inertia::render('Users/Create', ['roles' => Role::orderBy('name')->pluck('name')]);
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        $this->users->create($request->validated());

        return to_route('users.index')->with('success', 'User berhasil dibuat.');
    }

    public function edit(User $user): Response
    {
        Gate::authorize('update', $user);
        $user->load('roles:id,name');

        return Inertia::render('Users/Edit', ['managedUser' => $user, 'roles' => Role::orderBy('name')->pluck('name')]);
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $this->users->update($user, $request->validated(), $request->user());

        return to_route('users.index')->with('success', 'User berhasil diperbarui.');
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        Gate::authorize('delete', $user);
        $this->users->delete($user, $request->user());

        return to_route('users.index')->with('success', 'User berhasil dihapus.');
    }
}
