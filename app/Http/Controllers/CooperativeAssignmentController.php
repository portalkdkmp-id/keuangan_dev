<?php

namespace App\Http\Controllers;

use App\Http\Requests\Cooperative\AssignPicRequest;
use App\Models\Cooperative;
use App\Models\User;
use App\Services\Cooperative\CooperativeAssignmentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class CooperativeAssignmentController extends Controller
{
    public function __construct(private readonly CooperativeAssignmentService $assignments) {}

    public function store(AssignPicRequest $request, Cooperative $cooperative): RedirectResponse
    {
        $pic = User::findOrFail($request->validated('user_id'));
        $this->assignments->assign($cooperative, $pic, $request->user(), (bool) $request->boolean('is_primary'));

        return back()->with('success', 'PIC berhasil ditugaskan.');
    }

    public function destroy(Cooperative $cooperative, User $user): RedirectResponse
    {
        Gate::authorize('assignPic', $cooperative);
        $this->assignments->unassign($cooperative, $user);

        return back()->with('success', 'PIC berhasil dilepas.');
    }

    public function primary(Cooperative $cooperative, User $user): RedirectResponse
    {
        Gate::authorize('assignPic', $cooperative);
        $this->assignments->makePrimary($cooperative, $user);

        return back()->with('success', 'PIC utama berhasil diubah.');
    }
}
