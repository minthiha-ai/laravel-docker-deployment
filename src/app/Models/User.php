<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasApiTokens;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'phone',
        'password',
        'state_region_id',
        'district_id',
        'township_id',
        'postal_code',
        'city',
        'address',
        'active',
        'type_id',
        'phone_verified_at',
        'otp',
        'otp_expires_at',
        'otp_attempts',
        'last_otp_requested_at'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'otp'
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'phone_verified_at' => 'datetime',
            'otp_expires_at' => 'datetime',
            'last_otp_requested_at' => 'datetime',
            'password' => 'hashed',
            'active' => 'boolean',
        ];
    }

    public function stateRegion()
    {
        return $this->belongsTo(StateRegion::class);
    }

    public function district()
    {
        return $this->belongsTo(District::class);
    }

    public function township()
    {
        return $this->belongsTo(Township::class);
    }

    public function type()
    {
        return $this->belongsTo(Type::class, 'type_id');
    }
}
