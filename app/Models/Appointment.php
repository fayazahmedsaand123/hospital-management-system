<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

class Appointment extends Model
{
    use HasFactory;

    public const STATUSES = ['Scheduled', 'Confirmed', 'Completed', 'Cancelled', 'No Show'];

    protected $fillable = [
        'patient_id',
        'doctor_id',
        'appointment_date',
        'appointment_time',
        'reason',
        'status',
        'notes',
    ];

    protected $casts = [
        'appointment_date' => 'date',
    ];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class);
    }

    public function prescription(): HasOne
    {
        return $this->hasOne(Prescription::class);
    }

    public function bill(): HasOne
    {
        return $this->hasOne(Bill::class);
    }

    public function getFormattedTimeAttribute(): string
    {
        return $this->appointment_time
            ? Carbon::parse($this->appointment_time)->format('g:i A')
            : '';
    }

    public function getStatusBadgeClassAttribute(): string
    {
        return match ($this->status) {
            'Confirmed' => 'badge-soft-info',
            'Completed' => 'badge-soft-success',
            'Cancelled' => 'badge-soft-danger',
            'No Show' => 'badge-soft-warning',
            default => 'badge-soft-secondary',
        };
    }

    /**
     * Statuses that occupy a doctor's time slot.
     */
    public function scopeActiveBooking(Builder $query): Builder
    {
        return $query->whereIn('status', ['Scheduled', 'Confirmed', 'Completed']);
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if (! $term) {
            return $query;
        }

        return $query->where(function (Builder $query) use ($term) {
            $query->whereHas('patient', function (Builder $query) use ($term) {
                $query->where('patient_name', 'like', "%{$term}%")
                    ->orWhere('patient_code', 'like', "%{$term}%");
            })->orWhereHas('doctor', function (Builder $query) use ($term) {
                $query->where('doctor_name', 'like', "%{$term}%");
            });
        });
    }
}
