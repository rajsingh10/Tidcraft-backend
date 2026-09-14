<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'company_name',
        'profile_image',
        'phone_number',
        'status',
        'otp',
        'otp_expires_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array<int, string>
     */
    protected $appends = [
        'client_logo_url'
    ];

    /**
     * Get the full path URL for the client logo.
     */
    public function getClientLogoUrlAttribute()
    {
        if (!$this->profile_image) {
            return null;
        }

        if (filter_var($this->profile_image, FILTER_VALIDATE_URL) || str_starts_with($this->profile_image, 'http')) {
            return $this->profile_image;
        }

        return asset(ltrim($this->profile_image, '/'));
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'otp_expires_at' => 'datetime',
        ];
    }

    public function subscriptions()
    {
        return $this->hasMany(Subscription::class, 'client_id');
    }

    public function tenants()
    {
        return $this->hasMany(Tenant::class, 'client_id');
    }

    public function payments()
    {
        return $this->hasMany(Payment::class, 'client_id');
    }

    public function firebaseProjects()
    {
        return $this->hasMany(FirebaseProject::class, 'client_id');
    }

    public function domains()
    {
        return $this->hasMany(Domain::class, 'client_id');
    }
}
