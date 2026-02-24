<?php

namespace App\Models;

use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements FilamentUser
{
    use HasFactory, HasRoles, Notifiable;

    protected $fillable = [
        'name',
        'first_name',
        'last_name',
        'email',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function canAccessPanel(Panel $panel): bool
    {
        if ($panel->getId() === 'admin') {
            return $this->hasRole('Super Admin');
        }
        if ($panel->getId() === 'brand') {
            return $this->hasRole('Brand') && $this->brands()->exists();
        }
        return false;
    }

    public function brands(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Brand::class, 'brand_user')
            ->withPivot('role')
            ->withTimestamps();
    }

    public function getFilamentName(): string
    {
        if (($this->first_name ?? '') !== '' || ($this->last_name ?? '') !== '') {
            return trim("{$this->first_name} {$this->last_name}");
        }
        return $this->name ?? $this->email;
    }
}
