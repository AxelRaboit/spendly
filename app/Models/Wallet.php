<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\WalletRole;
use App\Filters\Filterable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Override;

#[Fillable(['user_id', 'name', 'start_balance', 'is_favorite', 'position'])]
class Wallet extends Model
{
    use Filterable;
    use HasFactory;

    #[Override]
    protected function casts(): array
    {
        return [
            'is_favorite' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function budgets(): HasMany
    {
        return $this->hasMany(Budget::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function members(): HasMany
    {
        return $this->hasMany(WalletMember::class);
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'wallet_members')->withPivot('role')->withTimestamps();
    }

    public function invitations(): HasMany
    {
        return $this->hasMany(WalletInvitation::class);
    }

    public function roleFor(User $user): ?WalletRole
    {
        /** @var WalletRole|string|null $role */
        $role = WalletMember::where('wallet_id', $this->id)
            ->where('user_id', $user->id)
            ->value('role');

        if ($role === null) {
            return null;
        }

        return $role instanceof WalletRole ? $role : WalletRole::from($role);
    }

    public function isShared(): bool
    {
        return ($this->members_count ?? $this->members()->count()) > 1;
    }
}
