<?php

namespace App\Models;

use App\Models\Core\Employee;
use Filament\Auth\MultiFactor\App\Concerns\InteractsWithAppAuthentication;
use Filament\Auth\MultiFactor\App\Concerns\InteractsWithAppAuthenticationRecovery;
use Filament\Auth\MultiFactor\App\Contracts\HasAppAuthentication;
use Filament\Auth\MultiFactor\App\Contracts\HasAppAuthenticationRecovery;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Str;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements FilamentUser, HasAppAuthentication, HasAppAuthenticationRecovery
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, InteractsWithAppAuthentication, 
    InteractsWithAppAuthenticationRecovery, HasRoles;

    protected $fillable = [
        'name',
        'email',
        'password',
        'is_active',
        'deactivated_at',
        'deactivation_reason',
        'deactivated_by',
        'must_change_password',
        'password_changed_at'
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'deactivated_at' => 'datetime',
            'password_changed_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (User $user) {
            $allowedContexts = [
                App::runningInConsole(), // artisan (install, make-user, seeders)
                app()->bound('creating_user_via_employee'), // flag posé par le service employee
            ];

            if (!in_array(true, $allowedContexts, true)) {
                throw new \RuntimeException(
                    'La création directe de User est interdite. Utilisez le module Employee.'
                );
            }
        });
    }

    public function initials(): string
    {
        return Str::of($this->name)
            ->explode(' ')
            ->map(fn (string $name) => Str::of($name)->substr(0, 1))
            ->implode('');
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->is_active;
    }

    public function employee(): HasOne
    {
        return $this->hasOne(Employee::class);
    }

    public function isHeadOffice(): bool
    {
        return $this->can('system.full-access');
    }
    
    public function currentBranchId(): ?int
    {
        return $this->employee?->branch_id;
    }

    public function deactivate(string $reason, ?User $by = null): void
    {
        $this->update([
            'is_active' => false,
            'deactivated_at' => now(),
            'deactivation_reason' => $reason,
            'deactivated_by' => $by?->id,
        ]);

        // Déconnexion immédiate de toutes ses sessions actives
        \Illuminate\Support\Facades\DB::table('sessions')->where('user_id', $this->id)->delete();
    }

    public function reactivate(): void
    {
        $this->update([
            'is_active' => true,
            'deactivated_at' => null,
            'deactivation_reason' => null,
            'deactivated_by' => null,
        ]);
    }

    public function isSuperAdmin(): bool
    {
        return $this->roles()->max('level') >= 100; // ou une permission dédiée 'system.bypass_all'
    }

    public function assignablePermissionIds(): \Illuminate\Support\Collection
    {
        if ($this->isSuperAdmin()) {
            return \Spatie\Permission\Models\Permission::pluck('id');
        }

        return $this->getAllPermissions()->pluck('id');
    }
    
}