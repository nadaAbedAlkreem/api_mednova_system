<?php

namespace App\Events;

use App\Models\WithdrawalRequest;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class WithdrawalStatusChanged
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public WithdrawalRequest $withdrawal,
        public string $actionType // مثل: withdrawal_requested, withdrawal_cancelled, withdrawal_approved
    ) {

    }
}
