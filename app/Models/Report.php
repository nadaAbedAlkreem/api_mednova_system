<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Report extends Model
{
    /** @use HasFactory<\Database\Factories\ReportFactory> */
    use HasFactory , SoftDeletes;

    protected $table = 'reports';

    /**
     * Mass assignable attributes
     */
    protected $fillable = [
        'customer_id',
        'reported_customers_id',
        'category',
        'subcategory',
        'custom_category',
        'custom_subcategory',
        'related_type',
        'related_id',
        'description',
        'attachments',
        'severity',
        'status',
        'admin_notes',
    ];

    /**
     * Cast attributes
     */
    protected $casts = [
        'attachments' => 'array',
    ];

    /**
     * ------------------------
     * RELATIONSHIPS
     * ------------------------
     */

    // مقدم البلاغ
    public function customer(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function reportedCustomer(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Customer::class, 'reported_customers_id');
    }

    public function related(): \Illuminate\Database\Eloquent\Relations\MorphTo
    {
        return $this->morphTo(__FUNCTION__, 'related_type', 'related_id');
    }

    /**
     * Optional: Helper attributes
     */

    public function isOpen(): bool
    {
        return in_array($this->status, ['new', 'under_review', 'awaiting_response']);
    }

    public function isClosed(): bool
    {
        return in_array($this->status, ['resolved', 'rejected', 'closed']);
    }
}
