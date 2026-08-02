<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Laravel\Sanctum\HasApiTokens;

#[Fillable(['name', 'email', 'password', 'status', 'firebase_uid', 'last_login_at', 'failed_login_count', 'locked_until'])]
#[Hidden(['password', 'remember_token', 'firebase_uid'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, HasUuids, Notifiable;

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class);
    }

    public function organizations(): BelongsToMany
    {
        return $this->belongsToMany(Organization::class)->withPivot('is_primary');
    }

    public function ownedBoats(): HasMany
    {
        return $this->hasMany(Boat::class, 'owner_id');
    }

    public function fishingTrips(): HasMany
    {
        return $this->hasMany(FishingTrip::class, 'fisher_id');
    }

    public function transportTrips(): HasMany
    {
        return $this->hasMany(TransportTrip::class, 'created_by');
    }

    public function retailSales(): HasMany
    {
        return $this->hasMany(RetailSale::class, 'sold_by');
    }

    public function retailReceipts(): HasMany
    {
        return $this->hasMany(RetailReceipt::class, 'received_by');
    }

    public function primaryOrganization(): ?Organization
    {
        $id = $this->organizations()->wherePivot('is_primary', true)->value('organizations.id') ?? $this->organizations()->value('organizations.id');

        return $id ? Organization::find($id) : null;
    }

    public function hasRole(string $role): bool
    {
        return $this->roles->contains('name', $role);
    }

    public function isLocked(): bool
    {
        $value = $this->getAttribute('locked_until');

        return $value !== null && Carbon::parse($value)->isFuture();
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
            'last_login_at' => 'datetime',
            'locked_until' => 'datetime',
        ];
    }
}
