<?php

namespace App\Repositories\Eloquent;

use App\Models\BankAccount;
use App\Repositories\IBankAccountRepositories;

class BankAccountRepository extends BaseRepository implements IBankAccountRepositories
{
    public function __construct()
    {
        $this->model = new BankAccount();
    }

    public function storeFromGateway($owner, array $bankData): BankAccount
    {
        return BankAccount::create([
            'owner_type'          => get_class($owner),
            'owner_id'            => $owner->id,
            'gateway'             => $bankData['gateway'] ?? 'amwal',
            'bank_name'           => $bankData['bank_name'],
            'account_holder_name' => $bankData['account_holder_name'],
            'iban'                => $bankData['iban'] ?? null,
            'account_number'      => $bankData['account_number'] ?? null,
            'swift_code'          => $bankData['swift_code'] ?? null,
            'bank_country'        => $bankData['country'] ?? 'OM',
            'status'              => 'verified',
            'is_default'          => true,
            'meta'                => $bankData['meta'] ?? null,
        ]);
    }

    public function findByOwner(int $ownerId, string $ownerType): ?BankAccount
    {
        return BankAccount::where('owner_id', $ownerId)
            ->where('owner_type', $ownerType)
            ->whereNull('deleted_at')
            ->latest()
            ->first();
    }

    public function findVerifiedByOwner(int $ownerId, string $ownerType): ?BankAccount
    {
        return BankAccount::where('owner_id', $ownerId)
            ->where('owner_type', $ownerType)
            ->where('status', 'verified')
            ->whereNull('deleted_at')
            ->latest()
            ->first();
    }

    public function createManual(array $data): BankAccount
    {
        return BankAccount::create($data);
    }

    public function updateAccount(BankAccount $bankAccount, array $data): BankAccount
    {
        $bankAccount->update($data);

        return $bankAccount->fresh();
    }
}
