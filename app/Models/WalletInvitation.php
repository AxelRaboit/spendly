<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\WalletRole;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Override;

/**
 * @property WalletRole $role
 * @property Carbon $expires_at
 * @property Carbon|null $accepted_at
 * @property Carbon|null $declined_at
 * @property string $email
 * @property string $token
 * @property int $wallet_id
 * @property int $invited_by
 * @property-read Wallet $wallet
 * @property-read User $inviter
 */
#[Fillable(['wallet_id', 'invited_by', 'email', 'role', 'token', 'expires_at', 'accepted_at', 'declined_at'])]
class WalletInvitation extends Model
{
    #[Override]
    protected function casts(): array
    {
        return [
            'role' => WalletRole::class,
            'expires_at' => 'datetime',
            'accepted_at' => 'datetime',
            'declined_at' => 'datetime',
        ];
    }

    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class);
    }

    public function inviter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_by');
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->whereNull('accepted_at')
            ->whereNull('declined_at')
            ->where('expires_at', '>', now());
    }

    public function isPending(): bool
    {
        return $this->accepted_at === null
            && $this->declined_at === null
            && $this->expires_at->isFuture();
    }
}
