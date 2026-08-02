<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBankAccountRequest;
use App\Models\Cooperative;
use App\Models\CooperativeBankAccount;
use App\Services\Audit\AuditLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class CooperativeBankAccountController extends Controller
{
    public function __construct(private readonly AuditLogService $audit) {}

    public function index(): Response
    {
        Gate::authorize('viewAny', CooperativeBankAccount::class);

        return Inertia::render('CooperativeBankAccounts/Index', ['accounts' => CooperativeBankAccount::with('cooperative:id,name')->latest()->paginate(10)]);
    }

    public function create(): Response
    {
        Gate::authorize('create', CooperativeBankAccount::class);

        return Inertia::render('CooperativeBankAccounts/Form', ['account' => null, 'cooperatives' => Cooperative::orderBy('name')->get(['id', 'name'])]);
    }

    public function store(StoreBankAccountRequest $request): RedirectResponse
    {
        Gate::authorize('create', CooperativeBankAccount::class);
        $this->persist($request->validated());

        return to_route('cooperative-bank-accounts.index')->with('success', 'Rekening koperasi berhasil dibuat.');
    }

    public function edit(CooperativeBankAccount $cooperativeBankAccount): Response
    {
        Gate::authorize('update', $cooperativeBankAccount);

        return Inertia::render('CooperativeBankAccounts/Form', ['account' => $cooperativeBankAccount, 'cooperatives' => Cooperative::orderBy('name')->get(['id', 'name'])]);
    }

    public function update(StoreBankAccountRequest $request, CooperativeBankAccount $cooperativeBankAccount): RedirectResponse
    {
        Gate::authorize('update', $cooperativeBankAccount);
        $this->persist($request->validated(), $cooperativeBankAccount);

        return to_route('cooperative-bank-accounts.index')->with('success', 'Rekening koperasi berhasil diperbarui.');
    }

    public function destroy(CooperativeBankAccount $cooperativeBankAccount): RedirectResponse
    {
        Gate::authorize('delete', $cooperativeBankAccount);
        abort_if($cooperativeBankAccount->is_primary, 422, 'Rekening utama tidak dapat dihapus.');
        $cooperativeBankAccount->delete();

        return back()->with('success', 'Rekening koperasi berhasil dihapus.');
    }

    public function setPrimary(CooperativeBankAccount $cooperativeBankAccount): RedirectResponse
    {
        Gate::authorize('setPrimary', $cooperativeBankAccount);
        DB::transaction(function () use ($cooperativeBankAccount) {
            CooperativeBankAccount::where('cooperative_id', $cooperativeBankAccount->cooperative_id)->update(['is_primary' => false]);
            $cooperativeBankAccount->update(['is_primary' => true, 'is_active' => true]);
        });

        return back()->with('success', 'Rekening utama koperasi berhasil diubah.');
    }

    private function persist(array $data, ?CooperativeBankAccount $account = null): CooperativeBankAccount
    {
        abort_unless(isset($data['cooperative_id']) || $account, 422, 'Koperasi wajib dipilih.');

        return DB::transaction(function () use ($data, $account) {
            $cooperativeId = $data['cooperative_id'] ?? $account->cooperative_id;
            if ($data['is_primary']) {
                CooperativeBankAccount::where('cooperative_id', $cooperativeId)->update(['is_primary' => false]);
            }
            $old = $account?->toArray() ?? [];
            $account ? $account->update($data) : $account = CooperativeBankAccount::create($data);
            $this->audit->record($old ? 'cooperative_bank_account.updated' : 'cooperative_bank_account.created', 'Master rekening koperasi disimpan.', $account, $old, $account->toArray());

            return $account;
        });
    }
}
