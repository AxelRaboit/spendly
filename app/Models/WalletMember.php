<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\WalletRole;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Override;

/**
 * @property WalletRole $role
 */
#[Fillable(['wallet_id', 'user_id', 'role'])]
class WalletMember extends Model
{
    #[Override]
    protected function casts(): array
    {
        return [
            'role' => WalletRole::class,
        ];
    }

    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
