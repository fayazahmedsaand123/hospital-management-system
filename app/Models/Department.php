<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Department extends Model
{
    use HasFactory;

    protected $fillable = [
        'department_name',
        'description',
        'status',
    ];

    public function doctors(): HasMany
    {
        return $this->hasMany(Doctor::class);
    }

    /**
     * Appointments of every doctor in this department.
     * Department has no direct foreign key, so it goes through doctors.
     */
    public function appointments(): HasManyThrough
    {
        return $this->hasManyThrough(Appointment::class, Doctor::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeSearch($query, ?string $term)
    {
        if (! $term) {
            return $query;
        }

        return $query->where(function ($query) use ($term) {
            $query->where('department_name', 'like', "%{$term}%")
                ->orWhere('description', 'like', "%{$term}%");
        });
    }
}
