<?php

namespace App\Http\Controllers;

use App\Models\UserBankAccount;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class UserBankAccountController extends Controller
{
    public function index(Request $request): Response
    {
        Gate::authorize('bank-accounts.view');

        return Inertia::render('BankAccounts/Index', [
            'accounts' => $request->user()->bankAccounts()->latest()->paginate(10),
        ]);
    }

    public function create(): Response
    {
        Gate::authorize('bank-accounts.create');

        return Inertia::render('BankAccounts/Form', ['account' => null]);
    }

    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('bank-accounts.create');
        $this->persist($request);

        return to_route('bank-accounts.index')->with('success', 'Rekening berhasil dibuat.');
    }

    public function quickStore(Request $request): RedirectResponse
    {
        Gate::authorize('bank-accounts.create');
        $this->persist($request);

        return back()->with('success', 'Rekening berhasil dibuat.');
    }

    public function edit(UserBankAccount $bankAccount): Response
    {
        Gate::authorize('bank-accounts.update');
        abort_unless($bankAccount->user_id === request()->user()->id, 403);

        return Inertia::render('BankAccounts/Form', ['account' => $bankAccount]);
    }

    public function update(Request $request, UserBankAccount $bankAccount): RedirectResponse
    {
        Gate::authorize('bank-accounts.update');
        abort_unless($bankAccount->user_id === $request->user()->id, 403);
        $this->persist($request, $bankAccount);

        return to_route('bank-accounts.index')->with('success', 'Rekening berhasil diperbarui.');
    }

    public function destroy(Request $request, UserBankAccount $bankAccount): RedirectResponse
    {
        Gate::authorize('bank-accounts.delete');
        abort_unless($bankAccount->user_id === $request->user()->id, 403);
        $bankAccount->delete();

        return back()->with('success', 'Rekening berhasil dihapus.');
    }

    private function persist(Request $request, ?UserBankAccount $account = null): UserBankAccount
    {
        $data = $request->validate([
            'bank_name' => ['required', 'string', 'max:255'],
            'account_number' => ['required', 'string', 'max:255'],
            'account_holder_name' => ['required', 'string', 'max:255'],
            'is_active' => ['required', 'boolean'],
            'is_primary' => ['required', 'boolean'],
        ]);

        return DB::transaction(function () use ($request, $account, $data) {
            if ($data['is_primary']) {
                $request->user()->bankAccounts()->update(['is_primary' => false]);
            }

            return $account
                ? tap($account)->update($data)
                : $request->user()->bankAccounts()->create($data);
        });
    }
}
