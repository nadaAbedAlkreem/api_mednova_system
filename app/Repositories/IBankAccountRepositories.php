<?php

namespace App\Repositories;

use App\Models\BankAccount;

interface IBankAccountRepositories
{
    public function storeFromGateway($owner, array $bankData): BankAccount;

    public function findByOwner(int $ownerId, string $ownerType): ?BankAccount;

    public function findVerifiedByOwner(int $ownerId, string $ownerType): ?BankAccount;

    public function createManual(array $data): BankAccount;

    public function updateAccount(BankAccount $bankAccount, array $data): BankAccount;
}
