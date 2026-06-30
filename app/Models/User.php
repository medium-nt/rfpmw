<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, SoftDeletes;

    protected $fillable = [
        'name',
        'username',
        'email',
        'password',
        'role_id',
        'position',
        'phone',
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
            'deleted_at' => 'datetime',
        ];
    }

    /**
     * Роль, назначенная пользователю.
     *
     * @return BelongsTo<Role, User>
     */
    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    /**
     * Контрагенты, закреплённые за менеджером.
     *
     * @return HasMany<Contractor>
     */
    public function contractors(): HasMany
    {
        return $this->hasMany(Contractor::class);
    }

    /**
     * Запросы, принятые менеджером.
     *
     * @return HasMany<Request>
     */
    public function requests(): HasMany
    {
        return $this->hasMany(Request::class);
    }

    /**
     * Коммерческие предложения, отправленные менеджером.
     *
     * @return HasMany<Proposal>
     */
    public function proposals(): HasMany
    {
        return $this->hasMany(Proposal::class);
    }

    /**
     * События, созданные менеджером.
     *
     * @return HasMany<Event>
     */
    public function events(): HasMany
    {
        return $this->hasMany(Event::class);
    }

    /**
     * Проверяет, имеет ли пользователь роль с указанным slug.
     */
    public function hasRole(string $slug): bool
    {
        return $this->role?->slug === $slug;
    }

    /**
     * Проверяет, является ли пользователь администратором.
     */
    public function isAdmin(): bool
    {
        return $this->hasRole('admin');
    }

    /**
     * Проверяет, является ли пользователь менеджером.
     */
    public function isManager(): bool
    {
        return $this->hasRole('manager');
    }

    /**
     * Подпись текущего пользователя для шапки user-меню AdminLTE (название роли).
     */
    public function adminlte_desc(): string
    {
        return $this->role?->title ?? '';
    }

    /**
     * URL страницы профиля для кнопки «Профиль» в user-меню AdminLTE.
     */
    public function adminlte_profile_url(): string
    {
        return route('profile.edit');
    }
}
