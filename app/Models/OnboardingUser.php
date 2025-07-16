<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OnboardingUser extends Model
{
    use HasFactory;

    protected $table = 'onboarding_users';

    protected $fillable = [
        'email',
        'verified',
        'verification_token',
    ];
}
