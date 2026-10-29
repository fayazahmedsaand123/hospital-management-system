<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Bill extends Model
{
    use HasFactory;

    public const PAYMENT_STATUSES = ['Unpaid', 'Partially Paid', 'Paid'];

    protected $fillable = [
        'patient_id',
        'appointment_id',
        'bill_number',
        'bill_date',
        'subtotal',
        'discount',
        'total_amount',
        'paid_amount',
        'balance_amount',
        'payment_status',
    ];

    protected $casts = [
        'bill_date' => 'date',
        'subtotal' => 'decimal:2',
        'discount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'balance_amount' => 'decimal:2',
    ];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(BillItem::class);
    }

    public function getPaymentStatusBadgeClassAttribute(): string
    {
        return match ($this->payment_status) {
            'Paid' => 'badge-soft-success',
            'Partially Paid' => 'badge-soft-warning',
            default => 'badge-soft-danger',
        };
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if (! $term) {
            return $query;
        }

        return $query->where(function (Builder $query) use ($term) {
            $query->where('bill_number', 'like', "%{$term}%")
                ->orWhereHas('patient', function (Builder $query) use ($term) {
                    $query->where('patient_name', 'like', "%{$term}%")
                        ->orWhere('patient_code', 'like', "%{$term}%");
                });
        });
    }

    public static function generateBillNumber(string $date): string
    {
        $sequence = static::whereDate('bill_date', $date)->count() + 1;

        return 'BILL-'.date('Ymd', strtotime($date)).'-'.str_pad((string) $sequence, 4, '0', STR_PAD_LEFT);
    }
}
