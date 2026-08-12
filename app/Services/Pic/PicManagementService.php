<?php

namespace App\Services\Pic;

use App\Models\Cooperative;
use App\Models\User;
use App\Services\Audit\AuditLogService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PicManagementService
{
    public function __construct(private readonly AuditLogService $audit) {}

    public function paginate(array $filters): LengthAwarePaginator
    {
        return User::query()
            ->role('pic_kdkmp')
            ->with('city:id,name')
            ->withCount('assignedCooperatives')
            ->when($filters['search'] ?? null, fn ($query, $search) => $query->where(fn ($nested) => $nested->whereLike('name', "%{$search}%", caseSensitive: false)->orWhereLike('email', "%{$search}%", caseSensitive: false)->orWhereLike('phone', "%{$search}%", caseSensitive: false)))
            ->when($filters['city_id'] ?? null, fn ($query, $cityId) => $query->where('city_id', $cityId))
            ->when(isset($filters['is_active']) && $filters['is_active'] !== '', fn ($query) => $query->where('is_active', filter_var($filters['is_active'], FILTER_VALIDATE_BOOL)))
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();
    }

    public function syncVisibleAssignments(User $pic, User $actor, array $selectedIds, array $visibleIds): void
    {
        if (! $pic->hasRole('pic_kdkmp') || ! $pic->city_id) {
            throw ValidationException::withMessages(['pic' => 'PIC atau wilayah PIC tidak valid.']);
        }

        $visibleIds = array_values(array_unique($visibleIds));
        $selectedIds = array_values(array_unique($selectedIds));
        $validVisible = Cooperative::where('city_id', $pic->city_id)->whereIn('id', $visibleIds)->pluck('id');

        if ($validVisible->count() !== count($visibleIds)) {
            throw ValidationException::withMessages(['cooperative_ids' => 'Terdapat koperasi di luar wilayah PIC.']);
        }

        $selected = collect($selectedIds)->intersect($validVisible);
        if ($selected->count() !== count($selectedIds)) {
            throw ValidationException::withMessages(['cooperative_ids' => 'Pilihan koperasi tidak valid untuk wilayah PIC.']);
        }

        DB::transaction(function () use ($pic, $actor, $selected, $validVisible) {
            $current = $pic->assignedCooperatives()->whereIn('cooperatives.id', $validVisible)->pluck('cooperatives.id');
            $toAttach = $selected->diff($current);
            $toDetach = $current->diff($selected);
            $payload = $toAttach->mapWithKeys(fn ($id) => [$id => [
                'assigned_by' => $actor->id,
                'assigned_at' => now(),
                'is_primary' => false,
            ]])->all();

            $pic->assignedCooperatives()->syncWithoutDetaching($payload);
            $pic->assignedCooperatives()->detach($toDetach);
            $this->audit->record('pic.cooperatives_bulk_assigned', 'Assignment koperasi PIC diperbarui.', $pic, [], [
                'attached' => $toAttach->values()->all(),
                'detached' => $toDetach->values()->all(),
            ]);
        });
    }
}
