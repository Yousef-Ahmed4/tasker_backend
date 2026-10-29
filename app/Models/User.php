<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, HasRoles, Notifiable, SoftDeletes;

    protected string $guard_name = 'sanctum';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'code',
        'project_id',
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

    /**
     * User settings records.
     */
    public function userSettings(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(UserSetting::class);
    }

    /**
     * Settings belonging to the user through user_settings pivot.
     */
    public function settings(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Setting::class, 'user_settings')
            ->withPivot('value')
            ->withTimestamps();
    }

    /**
     * User type and responsibilities.
     */
    public function userType(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(UserType::class);
    }

    /**
     * Project / Department the user belongs to.
     */
    public function project(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Tasks created by the user.
     */
    public function createdTasks(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Task::class, 'created_by');
    }

    /**
     * Tasks fixed / resolved by the user.
     */
    public function fixedTasks(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Task::class, 'fixed_by');
    }

    /**
     * Workspaces assigned to the user.
     */
    public function workspaces(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Workspace::class, 'workspace_users')
            ->withPivot('role')
            ->withTimestamps();
    }

    /**
     * Get the user's preferred language from user_settings, or default from settings table.
     */
    public function getPreferredLocale(): string
    {
        try {
            // 1. Check if user has explicit language in user_settings
            $userSetting = $this->relationLoaded('userSettings')
                ? $this->userSettings->first(fn ($us) => ($us->setting?->name ?? null) === 'language')
                : $this->userSettings()->whereHas('setting', fn ($q) => $q->where('name', 'language'))->first();

            if ($userSetting && ! empty($userSetting->value)) {
                $val = strtolower(trim((string) $userSetting->value));
                if (in_array($val, ['en', 'ar', 'ur'])) {
                    return $val;
                }
            }

            // 2. Fallback to default in settings table
            $default = Setting::where('name', 'language')->value('default');
            if ($default && in_array(strtolower(trim((string) $default)), ['en', 'ar', 'ur'])) {
                return strtolower(trim((string) $default));
            }
        } catch (\Throwable) {
            // Gracefully handle unmigrated database or connection issues
        }

        return config('app.locale', 'en');
    }
}
