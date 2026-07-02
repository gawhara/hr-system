<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, HasRoles, Notifiable;

    /**
     * Legacy role-column values mapped onto the spatie role set.
     */
    private const LEGACY_ROLE_MAP = [
        'super_admin' => 'group_admin',
        'company_hr_admin' => 'hr_manager',
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'current_company_id',
        'locale',
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
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    protected static function booted(): void
    {
        // The `role` column is the write-side shorthand used by seeders and
        // tests; the matching spatie role is the enforcement mechanism.
        static::created(function (User $user) {
            if ($user->role) {
                $user->assignRole(Role::findOrCreate($user->canonicalRoleName()));
            }
        });
    }

    public function canonicalRoleName(): ?string
    {
        if (! $this->role) {
            return null;
        }

        return self::LEGACY_ROLE_MAP[$this->role] ?? $this->role;
    }

    public function companies()
    {
        return $this->belongsToMany(Company::class)->withTimestamps();
    }

    public function currentCompany()
    {
        return $this->belongsTo(Company::class, 'current_company_id');
    }

    public function employee()
    {
        return $this->hasOne(Employee::class);
    }

    public function isGroupAdmin(): bool
    {
        return $this->canonicalRoleName() === 'group_admin' || $this->hasRole('group_admin');
    }

    public function canAccessCompany(int $companyId): bool
    {
        return $this->isGroupAdmin() || $this->companies()->whereKey($companyId)->exists();
    }

    public function isHrAdmin(): bool
    {
        return in_array($this->canonicalRoleName(), ['group_admin', 'company_admin', 'hr_manager'], true)
            || $this->hasAnyRole(['group_admin', 'company_admin', 'hr_manager']);
    }

    public function canViewSensitiveHr(): bool
    {
        return $this->can('view-sensitive-hr');
    }

    public function canApproveLeaveRequests(): bool
    {
        return $this->can('approve-leave');
    }

    public function canViewEmployee(Employee $employee): bool
    {
        if (! $this->canAccessCompany($employee->company_id)) {
            return false;
        }

        return $this->isHrAdmin() || $this->employee?->is($employee);
    }
}
