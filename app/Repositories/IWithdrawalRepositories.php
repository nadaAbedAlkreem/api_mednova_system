<?php

namespace App\Repositories;

use App\Models\WithdrawalRequest;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface IWithdrawalRepositories
{
    public function createWithdrawal(array $data): WithdrawalRequest;

    public function findById(int $id): ?WithdrawalRequest;

    public function findPendingByOwner(int $ownerId, string $ownerType): ?WithdrawalRequest;

    public function getByOwner(int $ownerId, string $ownerType, int $perPage): LengthAwarePaginator;

    public function updateWithdrawal(WithdrawalRequest $withdrawal, array $data): WithdrawalRequest;
}
