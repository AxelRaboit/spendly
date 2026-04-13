<?php

declare(strict_types=1);

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\PlanType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Override;
use Spatie\Permission\Traits\HasRoles;

/**
 * @property Carbon|null $trial_ends_at
 */
#[Fillable(['name', 'email', 'password', 'currency', 'locale', 'plan', 'trial_ends_at', 'is_demo'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    use HasFactory;
    use HasRoles;
    use Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    #[Override]
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'plan' => PlanType::class,
            'trial_ends_at' => 'datetime',
            'is_demo' => 'boolean',
        ];
    }

    public function categories(): HasMany
    {
        return $this->hasMany(Category::class)->where('is_system', false);
    }

    /** Categories from all wallets the user can access. */
    public function accessibleCategories(): Builder
    {
        return Category::where('is_system', false)
            ->whereIn('wallet_id', $this->accessibleWallets()->select('id'));
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    /** Wallets owned by this user (for plan limits). */
    public function wallets(): HasMany
    {
        return $this->hasMany(Wallet::class);
    }

    /** All wallets the user can access (owned + shared). */
    public function accessibleWallets(): Builder
    {
        return Wallet::whereIn('id', WalletMember::where('user_id', $this->id)->select('wallet_id'));
    }

    public function sharedWallets(): BelongsToMany
    {
        return $this->belongsToMany(Wallet::class, 'wallet_members')->withPivot('role')->withTimestamps();
    }

    public function walletMemberships(): HasMany
    {
        return $this->hasMany(WalletMember::class);
    }

    public function goals(): HasMany
    {
        return $this->hasMany(Goal::class);
    }

    public function categorizationRules(): HasMany
    {
        return $this->hasMany(CategorizationRule::class);
    }

    public function recurringTransactions(): HasMany
    {
        return $this->hasMany(RecurringTransaction::class);
    }

    public function scheduledTransactions(): HasMany
    {
        return $this->hasMany(ScheduledTransaction::class);
    }

    /** Wallet list formatted for select dropdowns. */
    public function walletOptions(): Collection
    {
        return $this->accessibleWallets()->orderBy('name')->get(['id', 'name']);
    }

    /** Category list formatted for select dropdowns. */
    public function categoryOptions(): Collection
    {
        return $this->accessibleCategories()->orderBy('name')->get(['id', 'name']);
    }
}
