<?php

namespace App\Repositories;

use App\Models\Wallet;

interface IWalletRepositories
{
    public function getByOwner($ownerId): Wallet;

    public function findByOwnerForUpdate($ownerId): ?Wallet;
    public function getOrCreateByOwnerForUpdate($ownerId, ?string $currency = null): Wallet;
    public function increaseAvailableBalance(Wallet $wallet, float $amount): Wallet;

    public function increasePendingBalance(Wallet $wallet, float $amount): Wallet;

    public function getPlatformWallet(): Wallet;
}
