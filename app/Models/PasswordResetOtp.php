<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class PasswordResetOtp extends Model
{
    use HasUuids;

    protected $fillable = ['email', 'otp_hash', 'verification_token_hash', 'attempts', 'expires_at', 'verified_at'];

    protected $hidden = ['otp_hash', 'verification_token_hash'];

    protected function casts(): array
    {
        return ['expires_at' => 'datetime', 'verified_at' => 'datetime'];
    }

    public function isExpired(): bool
    {
        return Carbon::parse($this->getAttribute('expires_at'))->isPast();
    }
}
