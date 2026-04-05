<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Override;

#[Fillable(['user_id', 'wallet_id', 'category_id', 'type', 'amount', 'description', 'day_of_month', 'active', 'last_generated_at'])]
class RecurringTransaction extends Model
{
    use HasFactory;

    #[Override]
    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'active' => 'boolean',
            'last_generated_at' => 'date:Y-m-d',
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
