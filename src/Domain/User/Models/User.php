<?php

namespace Domain\User\Models;

use Database\Factories\UserFactory;
use Domain\Post\Models\Post;
use Filament\Models\Contracts\HasName;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;



class User extends Authenticatable implements MustVerifyEmail, HasName, FilamentUser
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'password',
        'first_name',
        'last_name',
        'nickname',
        'mobile',
        'biography',
        'address',
        'country_id',
        'province_id',
        'city_id',
        'profile_photo_path',
        'bg_photo_path',
        'national_code',
        'verified_at',
        'email_verified_at',
        'point',
        'rate',
        'role_id',
        'level',
        'status',
        'email',
        'is_private',
        'is_report',
        'google_id',
        'customer_number',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_recovery_codes',
        'two_factor_secret',
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
            'verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array
     */


    protected $visible = [
        'id', 'first_name', 'level', 'last_name', 'customer_number','nickname', 'clubs','biography','profile_photo_path','bg_photo_path','point','rate','role_id', 'is_private', 'is_report', 'email', 'mobile', 'status', 'created_at','verified_at', 'email_verified_at'
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = [
        'profile_photo_url',
    ];

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->level == 3;
    }

    public function getPermissionRoleNames()
    {
        $permissions = $this->permissions;

        if (method_exists($this, 'roles')) {
            $permissions = $permissions->merge($this->getPermissionsViaRoles());
        }

        return $permissions->sort()->values()->pluck('name');
    }

    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    /**
     * Get the user's posts.
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function posts()
    {
        return $this->hasMany(Post::class);
    }

    /**
     * Get the user's notifications.
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function notifications()
    {
        return $this->hasMany(\Domain\Notification\Models\Notification::class);
    }

    public function getStatusNameAttribute()
    {
        return $this->status == 1 ? __('site.Active') : __('site.Inactive');
    }

    public static function generateCustumerNumber(): string
    {
        do {
            $number = random_int(1111111111, 9999999999);
            $exists = self::query()
                ->where('customer_number', $number)
                ->exists();
        } while ($exists);

        return (string)$number;
    }

    /**
     * Get the user's display name for Filament.
     *
     * @return string
     */
    public function getUserName(): string
    {
        return $this->getFilamentName();
    }

    /**
     * Get the user's display name for Filament.
     *
     * @return string
     */
    public function getFilamentName(): string
    {
        if (!empty($this->first_name) && !empty($this->last_name)) {
            return trim($this->first_name . ' ' . $this->last_name);
        }

        if (!empty($this->first_name)) {
            return $this->first_name;
        }

        if (!empty($this->last_name)) {
            return $this->last_name;
        }

        if (!empty($this->nickname)) {
            return $this->nickname;
        }

        if (!empty($this->email)) {
            return $this->email;
        }

        return 'User #' . $this->id;
    }

    /**
     * Send the email verification notification.
     *
     * @return void
     */
    public function sendEmailVerificationNotification()
    {
        $this->notify(new \Application\Api\User\Notifications\CustomEmailVerificationNotification());
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory()
    {
        return UserFactory::new();
    }
}
