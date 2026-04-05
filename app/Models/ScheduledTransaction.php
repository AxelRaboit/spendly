<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Override;

#[Fillable(['user_id', 'wallet_id', 'category_id', 'type', 'amount', 'description', 'scheduled_date', 'is_generated'])]
class ScheduledTransaction extends Model
{
    #[Override]
    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'scheduled_date' => 'date:Y-m-d',
            'is_generated' => 'boolean',
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
}
