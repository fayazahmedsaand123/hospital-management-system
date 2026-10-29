<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * Mass assignment means: User::create([...]) or $user->fill([...])
     * Only fields listed here can be filled that way — this prevents
     * a hacker from injecting unexpected fields (e.g. role escalation).
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',     // admin | receptionist | doctor
        'status',   // active | inactive
    ];

    /**
     * The attributes that should be hidden when model is converted to JSON.
     * This protects sensitive data in API responses.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast to native types.
     * 'hashed' automatically bcrypt-hashes the password when you assign it.
     * 'datetime' converts the DB timestamp to a Carbon date object.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
        ];
    }

    // =========================================================
    // Role Helper Methods
    // Instead of: if ($user->role === 'admin')
    // We write:   if ($user->isAdmin())
    // This is cleaner, easier to read, and easier to change later.
    // =========================================================

    /**
     * Check if this user is an Administrator.
     * Admins have full access to all modules.
     */
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    /**
     * Check if this user is a Doctor.
     * Doctors can view their own appointments and write prescriptions.
     */
    public function isDoctor(): bool
    {
        return $this->role === 'doctor';
    }

    /**
     * Check if this user is a Receptionist.
     * Receptionists manage patients, appointments, and billing.
     */
    public function isReceptionist(): bool
    {
        return $this->role === 'receptionist';
    }

    /**
     * Check if this user account is currently active.
     * Inactive users cannot log in to the system.
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Check if this user has admin OR receptionist access.
     * Useful for routes that both roles can access (e.g. patient list).
     */
    public function isAdminOrReceptionist(): bool
    {
        return in_array($this->role, ['admin', 'receptionist']);
    }

    /**
     * Doctor profile linked to this login account, when the user is a doctor.
     */
    public function doctorProfile(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(\App\Models\Doctor::class, 'user_id');
    }
}
