<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBankAccountRequest;
use App\Models\CompanyBankAccount;
use App\Services\Audit\AuditLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class CompanyBankAccountController extends Controller
{
    public function __construct(private readonly AuditLogService $audit) {}

    public function index(): Response
    {
        Gate::authorize('viewAny', CompanyBankAccount::class);

        return Inertia::render('CompanyBankAccounts/Index', ['accounts' => CompanyBankAccount::latest()->paginate(10)]);
    }

    public function create(): Response
    {
        Gate::authorize('create', CompanyBankAccount::class);

        return Inertia::render('CompanyBankAccounts/Form', ['account' => null]);
    }

    public function store(StoreBankAccountRequest $request): RedirectResponse
    {
        Gate::authorize('create', CompanyBankAccount::class);
        $this->persist($request->validated());

        return to_route('company-bank-accounts.index')->with('success', 'Rekening perusahaan berhasil dibuat.');
    }

    public function quickStore(StoreBankAccountRequest $request): RedirectResponse
    {
        Gate::authorize('create', CompanyBankAccount::class);
        $this->persist($request->validated());

        return back()->with('success', 'Rekening perusahaan berhasil dibuat.');
    }

    public function edit(CompanyBankAccount $companyBankAccount): Response
    {
        Gate::authorize('update', $companyBankAccount);

        return Inertia::render('CompanyBankAccounts/Form', ['account' => $companyBankAccount]);
    }

    public function update(StoreBankAccountRequest $request, CompanyBankAccount $companyBankAccount): RedirectResponse
    {
        Gate::authorize('update', $companyBankAccount);
        $this->persist($request->validated(), $companyBankAccount);

        return to_route('company-bank-accounts.index')->with('success', 'Rekening perusahaan berhasil diperbarui.');
    }

    public function destroy(CompanyBankAccount $companyBankAccount): RedirectResponse
    {
        Gate::authorize('delete', $companyBankAccount);
        abort_if($companyBankAccount->is_primary, 422, 'Rekening utama tidak dapat dihapus.');
        $this->audit->record('company_bank_account.deleted', 'Rekening perusahaan dihapus.', $companyBankAccount, $companyBankAccount->toArray());
        $companyBankAccount->delete();

        return back()->with('success', 'Rekening perusahaan berhasil dihapus.');
    }

    public function setPrimary(CompanyBankAccount $companyBankAccount): RedirectResponse
    {
        Gate::authorize('setPrimary', $companyBankAccount);
        DB::transaction(function () use ($companyBankAccount) {
            CompanyBankAccount::query()->update(['is_primary' => false]);
            $companyBankAccount->update(['is_primary' => true, 'is_active' => true]);
        });

        return back()->with('success', 'Rekening utama berhasil diubah.');
    }

    private function persist(array $data, ?CompanyBankAccount $account = null): CompanyBankAccount
    {
        return DB::transaction(function () use ($data, $account) {
            if ($data['is_primary']) {
                CompanyBankAccount::query()->update(['is_primary' => false]);
            }
            $old = $account?->toArray() ?? [];
            $account ? $account->update($data) : $account = CompanyBankAccount::create($data);
            $this->audit->record($old ? 'company_bank_account.updated' : 'company_bank_account.created', $old ? 'Rekening perusahaan diperbarui.' : 'Rekening perusahaan dibuat.', $account, $old, $account->toArray());

            return $account;
        });
    }
}
