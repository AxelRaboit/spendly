<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Override;

#[Fillable(['user_id', 'wallet_id', 'category_id', 'name', 'target_amount', 'saved_amount', 'deadline', 'color'])]
class Goal extends Model
{
    use HasFactory;

    #[Override]
    protected function casts(): array
    {
        return [
            'target_amount' => 'decimal:2',
            'saved_amount' => 'decimal:2',
            'deadline' => 'date:Y-m-d',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function getProgressAttribute(): float
    {
        if ((float) $this->target_amount <= 0) {
            return 0;
        }

        return min(100, round((float) $this->saved_amount / (float) $this->target_amount * 100, 1));
    }
}
