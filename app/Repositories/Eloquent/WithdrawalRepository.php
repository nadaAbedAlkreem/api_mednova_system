<?php

namespace App\Repositories\Eloquent;

use App\Enums\WithdrawalStatus;
use App\Models\WithdrawalRequest;
use App\Repositories\IWithdrawalRepositories;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class WithdrawalRepository extends BaseRepository implements IWithdrawalRepositories
{
    public function __construct()
    {
        $this->model = new WithdrawalRequest();
    }

    public function createWithdrawal(array $data): WithdrawalRequest
    {
        return WithdrawalRequest::create($data);
    }

    public function findById(int $id): ?WithdrawalRequest
    {
        return WithdrawalRequest::whereNull('deleted_at')->find($id);
    }

    public function findByIdForUpdate(int $id): ?WithdrawalRequest
    {
        return WithdrawalRequest::whereNull('deleted_at')
            ->where('id', $id)
            ->lockForUpdate()
            ->first();
    }

    public function findPendingByOwner(int $ownerId, string $ownerType): ?WithdrawalRequest
    {
        return WithdrawalRequest::where('owner_id', $ownerId)
            ->where('owner_type', $ownerType)
            ->whereIn('status', [
                WithdrawalStatus::PENDING_REVIEW->value,
                WithdrawalStatus::PROCESSING->value,
            ])
            ->whereNull('deleted_at')
            ->first();
    }

    public function getByOwner(int $ownerId, string $ownerType, int $perPage): LengthAwarePaginator
    {
        return WithdrawalRequest::where('owner_id', $ownerId)
            ->where('owner_type', $ownerType)
            ->whereNull('deleted_at')
            ->with(['bankAccount'])
            ->orderByDesc('created_at')
            ->paginate($perPage);
    }

    public function updateWithdrawal(WithdrawalRequest $withdrawal, array $data): WithdrawalRequest
    {
        $withdrawal->update($data);

        return $withdrawal->fresh();
    }
}
