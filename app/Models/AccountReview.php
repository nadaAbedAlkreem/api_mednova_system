<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AccountReview extends Model
{
    /** @use HasFactory<\Database\Factories\AccountReviewFactory> */
    use HasFactory , SoftDeletes;
    protected $table = 'account_reviews';

    protected $fillable = [
        'customer_id',
        'status',
        'reason',
        'reviewed_by',
    ];
    public function customer(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    /**
     * العلاقة مع الادمن الذي قام بالرفض
     */
    public function reviewer(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Admin::class, 'reviewed_by');
    }

}
