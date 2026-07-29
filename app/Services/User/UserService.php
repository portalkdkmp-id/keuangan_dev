<?php

namespace App\Services\User;

use App\Models\User;
use App\Services\Audit\AuditLogService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;

class UserService
{
    public function __construct(private readonly AuditLogService $auditLog) {}

    public function paginate(array $filters): LengthAwarePaginator
    {
        return User::query()
            ->with('roles:id,name')
            ->when($filters['search'] ?? null, fn ($query, $search) => $query->where(fn ($q) => $q
                ->where('name', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%")))
            ->when($filters['role'] ?? null, fn ($query, $role) => $query->role($role))
            ->when(isset($filters['is_active']) && $filters['is_active'] !== '', fn ($query) => $query->where('is_active', filter_var($filters['is_active'], FILTER_VALIDATE_BOOL)))
            ->latest()
            ->paginate(10)
            ->withQueryString();
    }

    public function create(array $data): User
    {
        return DB::transaction(function () use ($data) {
            $role = $this->role($data['role']);
            $user = User::create(collect($data)->except('role')->all());
            $user->syncRoles([$role]);
            $this->auditLog->record('user.created', 'User dibuat.', $user, [], $user->only(['id', 'name', 'email', 'is_active']));
            $this->auditLog->record('user.role_changed', 'Role user diubah.', $user, [], ['role' => $role->name]);

            return $user;
        });
    }

    public function update(User $user, array $data, User $actor): User
    {
        if ($actor->is($user) && array_key_exists('is_active', $data) && ! $data['is_active']) {
            throw ValidationException::withMessages(['is_active' => 'Anda tidak dapat menonaktifkan akun sendiri.']);
        }

        return DB::transaction(function () use ($user, $data) {
            $old = $user->only(['name', 'email', 'is_active']);
            $roleName = $data['role'] ?? null;
            unset($data['role']);

            if (($data['password'] ?? null) === null) {
                unset($data['password']);
            }

            $user->update($data);
            $this->auditLog->record('user.updated', 'User diperbarui.', $user, $old, $user->only(['name', 'email', 'is_active']));

            if ($roleName !== null) {
                $role = $this->role($roleName);
                $oldRole = $user->roles()->pluck('name')->first();
                $user->syncRoles([$role]);
                $this->auditLog->record('user.role_changed', 'Role user diubah.', $user, ['role' => $oldRole], ['role' => $role->name]);
            }

            return $user;
        });
    }

    public function delete(User $user, User $actor): void
    {
        if ($actor->is($user)) {
            throw ValidationException::withMessages(['user' => 'Anda tidak dapat menghapus akun sendiri.']);
        }

        $old = $user->only(['id', 'name', 'email']);
        $user->delete();
        $this->auditLog->record('user.deleted', 'User dihapus.', null, $old);
    }

    private function role(string $role): Role
    {
        $model = Role::where('guard_name', 'web')->where('name', $role)->first();
        if (! $model) {
            throw ValidationException::withMessages(['role' => 'Role tidak tersedia.']);
        }

        return $model;
    }
}
