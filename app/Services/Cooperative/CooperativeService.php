<?php

namespace App\Services\Cooperative;

use App\Models\Cooperative;
use App\Models\User;
use App\Services\Audit\AuditLogService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class CooperativeService
{
    public function __construct(private readonly AuditLogService $auditLog) {}

    public function paginate(User $user, array $filters): LengthAwarePaginator
    {
        return Cooperative::query()
            ->accessibleBy($user)
            ->with(['province:id,name', 'city:id,name', 'district:id,name', 'village:id,name'])
            ->withCount('pics')
            ->with(['pics' => fn ($q) => $q->wherePivot('is_primary', true)->select('users.id', 'users.name')])
            ->when($filters['search'] ?? null, fn ($query, $search) => $query->where(fn ($q) => $q->where('nik', 'like', "%{$search}%")->orWhere('name', 'like', "%{$search}%")))
            ->when($filters['province_id'] ?? null, fn ($query, $id) => $query->where('province_id', $id))
            ->when($filters['city_id'] ?? null, fn ($query, $id) => $query->where('city_id', $id))
            ->when($filters['district_id'] ?? null, fn ($query, $id) => $query->where('district_id', $id))
            ->when($filters['village_id'] ?? null, fn ($query, $id) => $query->where('village_id', $id))
            ->when(isset($filters['is_active']) && $filters['is_active'] !== '', fn ($query) => $query->where('is_active', filter_var($filters['is_active'], FILTER_VALIDATE_BOOL)))
            ->latest()
            ->paginate(10)
            ->withQueryString();
    }

    public function create(array $data): Cooperative
    {
        return DB::transaction(function () use ($data) {
            $cooperative = Cooperative::create($data);
            $this->auditLog->record('cooperative.created', 'Koperasi dibuat.', $cooperative, [], $cooperative->toArray());

            return $cooperative;
        });
    }

    public function update(Cooperative $cooperative, array $data): Cooperative
    {
        return DB::transaction(function () use ($cooperative, $data) {
            $old = $cooperative->toArray();
            $cooperative->update($data);
            $this->auditLog->record('cooperative.updated', 'Koperasi diperbarui.', $cooperative, $old, $cooperative->fresh()->toArray());

            return $cooperative;
        });
    }

    public function delete(Cooperative $cooperative): void
    {
        DB::transaction(function () use ($cooperative) {
            $old = $cooperative->toArray();
            $cooperative->delete();
            $this->auditLog->record('cooperative.deleted', 'Koperasi dihapus.', $cooperative, $old);
        });
    }
}
