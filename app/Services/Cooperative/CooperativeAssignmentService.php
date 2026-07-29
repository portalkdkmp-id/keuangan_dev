<?php

namespace App\Services\Cooperative;

use App\Models\Cooperative;
use App\Models\User;
use App\Services\Audit\AuditLogService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CooperativeAssignmentService
{
    public function __construct(private readonly AuditLogService $auditLog) {}

    public function assign(Cooperative $cooperative, User $pic, User $actor, bool $isPrimary): void
    {
        if (! $pic->hasRole('pic_kdkmp')) {
            throw ValidationException::withMessages(['user_id' => 'Hanya user dengan role pic_kdkmp yang dapat ditugaskan.']);
        }

        DB::transaction(function () use ($cooperative, $pic, $actor, $isPrimary) {
            if ($cooperative->pics()->whereKey($pic->id)->exists()) {
                throw ValidationException::withMessages(['user_id' => 'PIC sudah ditugaskan pada koperasi ini.']);
            }

            if ($isPrimary) {
                $cooperative->pics()->updateExistingPivot($cooperative->pics()->pluck('users.id'), ['is_primary' => false]);
            }

            $cooperative->pics()->attach($pic->id, [
                'assigned_by' => $actor->id,
                'assigned_at' => now(),
                'is_primary' => $isPrimary,
            ]);

            $this->auditLog->record('cooperative.pic_assigned', 'PIC ditugaskan.', $cooperative, [], ['user_id' => $pic->id, 'is_primary' => $isPrimary]);
        });
    }

    public function unassign(Cooperative $cooperative, User $pic): void
    {
        DB::transaction(function () use ($cooperative, $pic) {
            $cooperative->pics()->detach($pic->id);
            $this->auditLog->record('cooperative.pic_unassigned', 'PIC dilepas.', $cooperative, ['user_id' => $pic->id]);
        });
    }

    public function makePrimary(Cooperative $cooperative, User $pic): void
    {
        DB::transaction(function () use ($cooperative, $pic) {
            if (! $cooperative->pics()->whereKey($pic->id)->exists()) {
                throw ValidationException::withMessages(['user_id' => 'PIC belum ditugaskan pada koperasi ini.']);
            }

            $cooperative->pics()->updateExistingPivot($cooperative->pics()->pluck('users.id'), ['is_primary' => false]);
            $cooperative->pics()->updateExistingPivot($pic->id, ['is_primary' => true]);
            $this->auditLog->record('cooperative.primary_pic_changed', 'PIC utama diubah.', $cooperative, [], ['user_id' => $pic->id]);
        });
    }
}
