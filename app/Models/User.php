<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\Role;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'first_name',
        'last_name',
        'current_league_id',
        'email',
        'phone',
        'handicap_index',
        'manual_handicap_index',
        'password',
        'created_at',
        'updated_at',
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
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'handicap_index' => 'decimal:1',
        'manual_handicap_index' => 'decimal:1',
    ];

    /**
     * The player's effective WHS Handicap Index. The admin-entered "established
     * index" only seeds a thin record: once the player has enough rounds to
     * compute one (handicap_index stays null until the 3-round minimum, see
     * HandicapService::MINIMUM_ROUNDS), the computed value automatically takes
     * over. Null = N/A.
     */
    public function effectiveHandicapIndex(): ?float
    {
        $value = $this->handicap_index ?? $this->manual_handicap_index;

        return is_null($value) ? null : (float) $value;
    }

    /**
     * Leagues this user belongs to, with their per-league role.
     *
     * @return BelongsToMany<League, $this>
     */
    public function leagues(): BelongsToMany
    {
        return $this->belongsToMany(League::class, 'league_user')
            ->withPivot('role')
            ->withTimestamps();
    }

    /**
     * The rounds this user has posted (across all leagues).
     *
     * @return HasMany<Round, $this>
     */
    public function rounds(): HasMany
    {
        return $this->hasMany(Round::class);
    }

    /**
     * Whether this user can sign in (golfer-derived roster users have no
     * password and exist only to own their rounds/handicap).
     */
    public function canLogin(): bool
    {
        return ! is_null($this->password);
    }

    /**
     * Leagues this user created/owns.
     *
     * @return HasMany<League, $this>
     */
    public function ownedLeagues(): HasMany
    {
        return $this->hasMany(League::class, 'owner_id');
    }

    /**
     * The user's currently active league.
     *
     * @return BelongsTo<League, $this>
     */
    public function currentLeague(): BelongsTo
    {
        return $this->belongsTo(League::class, 'current_league_id');
    }

    /**
     * This user's role within the given league (or null if not a member).
     */
    public function roleIn(?League $league): ?string
    {
        if (! $league) {
            return null;
        }

        $role = DB::table('league_user')
            ->where('user_id', $this->id)
            ->where('league_id', $league->id)
            ->value('role');

        return is_string($role) ? $role : null;
    }

    /**
     * Whether this user is an admin of the given league.
     */
    public function isAdminOf(?League $league): bool
    {
        return $this->roleIn($league) === Role::Admin->value;
    }
}
