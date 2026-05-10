<?php

namespace App\Services\Api\Financial\Admin;

use App\Enums\TransactionType;
use App\Models\ConsultationChatRequest;
use App\Models\ConsultationVideoRequest;
use App\Models\Dispute;
use App\Models\Transaction;
use App\Models\Wallet;
use App\Repositories\IWalletRepositories;
use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;

class AdminDashboardService
{
    public function __construct(
        protected  IWalletRepositories $wallet,
    ) {}
    public function getSummary(): array
    {
        return [
            'wallet'        => $this->getWalletSection(),
            'consultations' => $this->getConsultationStats(),
            'revenue'       => $this->getRevenueStats(),
            'disputes'      => $this->getDisputeStats(),
        ];
    }

    public function getRevenue(int $perPage, ?string $month): array
    {
        $wallet = $this->getPlatformWalletReadOnly();

        $baseQuery = Transaction::query()
            ->where('wallet_id', $wallet->id)
            ->where('transaction_type', TransactionType::PLATFORM_FEE->value)
            ->when($month, fn ($q) => $q
                ->whereYear('created_at', substr($month, 0, 4))
                ->whereMonth('created_at', substr($month, 5, 2)))
            ->whereNull('deleted_at');

        $totalRevenue = (clone $baseQuery)->sum('net_amount');

        $paginator = (clone $baseQuery)
            ->with(['reference.patient:id,full_name', 'reference.consultant:id,full_name'])
            ->orderByDesc('created_at')
            ->paginate($perPage);

        return [
            'paginator' => $paginator,
            'summary'   => [
                'total_revenue' => number_format((float) $totalRevenue, 3, '.', ''),
                'total_count'   => $paginator->total(), // عدد العمليات او حركات المالية
                'currency'      => 'OMR',
            ],
        ];
    }

    public function getEscrow(int $perPage, int $page, string $status): array
    {
        $statuses = ['held', 'review_window'];

        $chats = ConsultationChatRequest::whereIn('financial_status', $statuses)
            ->when($status !== 'all', fn ($q) => $q->where('financial_status', $status))
            ->with(['patient:id,full_name', 'consultant:id,full_name'])
            ->get();

        $videos = ConsultationVideoRequest::whereIn('financial_status', $statuses)
            ->when($status !== 'all', fn ($q) => $q->where('financial_status', $status))
            ->with(['patient:id,full_name', 'consultant:id,full_name'])
            ->get();

        $merged = $chats->concat($videos)->sortByDesc('created_at')->values();

        $total    = $merged->count();
        $items    = $merged->slice(($page - 1) * $perPage, $perPage)->values();
        $paginator = new LengthAwarePaginator($items, $total, $perPage, $page, [
            'path' => request()->url(),
        ]);

        return [
            'paginator' => $paginator,
            'summary'   => $this->buildEscrowSummary(),
        ];
    }

    private function buildEscrowSummary(): array
    {
        $heldAmount   = (float) ConsultationChatRequest::where('financial_status', 'held')->sum('consultation_price')
                      + (float) ConsultationVideoRequest::where('financial_status', 'held')->sum('consultation_price');

        $reviewAmount = (float) ConsultationChatRequest::where('financial_status', 'review_window')->sum('consultation_price')
                      + (float) ConsultationVideoRequest::where('financial_status', 'review_window')->sum('consultation_price');

        $countHeld   = ConsultationChatRequest::where('financial_status', 'held')->count()
                     + ConsultationVideoRequest::where('financial_status', 'held')->count();

        $countReview = ConsultationChatRequest::where('financial_status', 'review_window')->count()
                     + ConsultationVideoRequest::where('financial_status', 'review_window')->count();

        return [
            'count_held'          => $countHeld,
            'count_review'        => $countReview,
            'total_held_amount'   => number_format($heldAmount,                3, '.', ''),
            'total_review_amount' => number_format($reviewAmount,              3, '.', ''),
            'total_escrow'        => number_format($heldAmount + $reviewAmount, 3, '.', ''),
            'currency'            => 'OMR',
        ];
    }

    public function getTransactions(int $perPage, string $type, ?string $dateFrom, ?string $dateTo): array
    {
        $wallet = $this->getPlatformWalletReadOnly();

        $paginator = Transaction::query()
            ->where('wallet_id', $wallet->id)
            ->when($type !== 'all', fn ($q) => $q->where('transaction_type', $type))
            ->when($dateFrom, fn ($q) => $q->whereDate('created_at', '>=', $dateFrom))
            ->when($dateTo,   fn ($q) => $q->whereDate('created_at', '<=', $dateTo))
            ->whereNull('deleted_at')
            ->with(['reference.patient:id,full_name', 'reference.consultant:id,full_name'])
            ->orderByDesc('created_at')
            ->paginate($perPage);

        return ['paginator' => $paginator];
    }

    private function getPlatformWalletReadOnly(): Wallet
    {
        return $this->wallet->getPlatformWalletReadOnlyInRepo();
    }

    private function getWalletSection(): array
    {
        $wallet = $this->getPlatformWalletReadOnly();

        $total = bcadd(
            bcadd((string) $wallet->available_balance, (string) $wallet->pending_balance, 3),
            (string) $wallet->frozen_balance,
            3
        );

        return [
            'available_balance' => number_format((float) $wallet->available_balance, 3, '.', ''),
            'pending_balance'   => number_format((float) $wallet->pending_balance,   3, '.', ''),
            'frozen_balance'    => number_format((float) $wallet->frozen_balance,    3, '.', ''),
            'total_balance'     => number_format((float) $total,                     3, '.', ''),
            'currency'          => $wallet->currency ?? 'OMR',
        ];
    }

    private function getConsultationStats(): array
    {
        $settled  = ['withdrawable', 'withdrawn'];
        $refunded = ['refunded', 'refunded_internal'];

        return [
            'total_paid'       => $this->countBoth(fn ($q) => $q->where('financial_status', '!=', 'unpaid')),
            'total_settled'    => $this->countBoth(fn ($q) => $q->whereIn('financial_status', $settled)),
            'total_held'       => $this->countBoth(fn ($q) => $q->where('financial_status', 'held')),
            'total_in_review'  => $this->countBoth(fn ($q) => $q->where('financial_status', 'review_window')),
            'total_in_dispute' => $this->countBoth(fn ($q) => $q->where('financial_status', 'frozen')),
            'total_refunded'   => $this->countBoth(fn ($q) => $q->whereIn('financial_status', $refunded)),
        ];
    }

    private function countBoth(callable $scope): int
    {
        return $scope(ConsultationChatRequest::query())->count()
             + $scope(ConsultationVideoRequest::query())->count();
    }

    private function getRevenueStats(): array
    {
        $now = Carbon::now();

        return [
            'this_month' => number_format($this->revenueForMonth($now->year, $now->month), 3, '.', ''),
            'last_month' => number_format($this->revenueForMonth(
                $now->copy()->subMonth()->year,
                $now->copy()->subMonth()->month
            ), 3, '.', ''),
            'currency' => 'OMR',
        ];
    }

    private function revenueForMonth(int $year, int $month): float
    {
        $statuses = ['withdrawable', 'withdrawn'];

        $chat = (float) ConsultationChatRequest::whereIn('financial_status', $statuses)
            ->whereYear('settled_at', $year)
            ->whereMonth('settled_at', $month)
            ->sum('platform_commission_amount');

        $video = (float) ConsultationVideoRequest::whereIn('financial_status', $statuses)
            ->whereYear('settled_at', $year)
            ->whereMonth('settled_at', $month)
            ->sum('platform_commission_amount');

        return $chat + $video;
    }

    private function getDisputeStats(): array
    {
        $pendingCount = Dispute::where('status', 'opened')->count();

        $avgHours = Dispute::whereNotNull('resolved_at')
            ->get(['opened_at', 'resolved_at'])
            ->avg(fn ($d) => Carbon::parse($d->opened_at)->diffInHours(Carbon::parse($d->resolved_at)));

        return [
            'pending_count'        => $pendingCount,
            'avg_resolution_hours' => $avgHours !== null ? (int) round($avgHours) : null,
        ];
    }
}
