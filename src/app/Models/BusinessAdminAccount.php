<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\HasApiTokens;

class BusinessAdminAccount extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $guarded = [];
    protected  $fillable=['name','phone','email','password','otp','otp_expires_at','last_otp_requested_at','otp_attempts','phone_verified_at'];
}
