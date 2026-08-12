<?php

namespace App\Services\Pic;

use App\Models\Cooperative;
use App\Models\User;
use App\Services\Audit\AuditLogService;
use App\Services\User\UserService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PicManagementService
{
    public function __construct(private readonly UserService $users, private readonly AuditLogService $audit) {}

    public function paginate(array $filters): LengthAwarePaginator
    {
        return User::query()
            ->role('pic_kdkmp')
            ->with(['city.province:id,name'])
            ->withCount('assignedCooperatives')
            ->when($filters['search'] ?? null, fn ($query, $search) => $query->where(fn ($nested) => $nested->whereLike('name', "%{$search}%", caseSensitive: false)->orWhereLike('email', "%{$search}%", caseSensitive: false)->orWhereLike('phone', "%{$search}%", caseSensitive: false)))
            ->when($filters['city_id'] ?? null, fn ($query, $city) => $query->where('city_id', $city))
            ->when(isset($filters['is_active']) && $filters['is_active'] !== '', fn ($query) => $query->where('is_active', filter_var($filters['is_active'], FILTER_VALIDATE_BOOL)))
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();
    }

    public function create(array $data): User
    {
        return $this->users->create([...$data, 'role' => 'pic_kdkmp']);
    }

    public function update(User $pic, array $data, User $actor): User
    {
        $this->ensurePic($pic);

        return $this->users->update($pic, [...$data, 'role' => 'pic_kdkmp'], $actor);
    }

    public function delete(User $pic, User $actor): void
    {
        $this->ensurePic($pic);
        $this->users->delete($pic, $actor);
    }

    public function syncVisibleAssignments(User $pic, User $actor, array $selectedIds, array $visibleIds): void
    {
        $this->ensurePic($pic);
        $validVisible = Cooperative::query()->where('city_id', $pic->city_id)->whereIn('id', $visibleIds)->pluck('id');
        if ($validVisible->count() !== count(array_unique($visibleIds))) {
            throw ValidationException::withMessages(['cooperative_ids' => 'Terdapat koperasi yang berada di luar wilayah PIC.']);
        }

        $selected = collect($selectedIds)->intersect($validVisible);
        if ($selected->count() !== count(array_unique($selectedIds))) {
            throw ValidationException::withMessages(['cooperative_ids' => 'Pilihan koperasi tidak valid untuk wilayah PIC.']);
        }

        DB::transaction(function () use ($pic, $actor, $selected, $validVisible) {
            $currentlyAssigned = $pic->assignedCooperatives()->whereIn('cooperatives.id', $validVisible)->pluck('cooperatives.id');
            $toAttach = $selected->diff($currentlyAssigned);
            $toDetach = $currentlyAssigned->diff($selected);
            $payload = $toAttach->mapWithKeys(fn ($id) => [$id => ['assigned_by' => $actor->id, 'assigned_at' => now(), 'is_primary' => false]])->all();
            $pic->assignedCooperatives()->syncWithoutDetaching($payload);
            $pic->assignedCooperatives()->detach($toDetach);
            $this->audit->record('pic.cooperatives_bulk_assigned', 'Assignment koperasi PIC diperbarui.', $pic, [], ['attached' => $toAttach->values()->all(), 'detached' => $toDetach->values()->all()]);
        });
    }

    private function ensurePic(User $pic): void
    {
        if (! $pic->hasRole('pic_kdkmp')) {
            throw ValidationException::withMessages(['pic' => 'User bukan PIC KDKMP.']);
        }
    }
}
