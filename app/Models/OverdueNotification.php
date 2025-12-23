<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OverdueNotification extends Model
{
    use HasFactory;

    protected $fillable = [
        'receipt_id',
        'notification_date',
        'overdue_fee',
        'overdue_days',
    ];

    protected $casts = [
        'notification_date' => 'date',
        'overdue_fee' => 'decimal:2',
    ];

    /**
     * Get the rental receipt associated with the notification.
     */
    public function rentalReceipt()
    {
        return $this->belongsTo(RentalReceipt::class, 'receipt_id', 'receipt_id');
    }
}
