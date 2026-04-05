<?php

declare(strict_types=1);

namespace App\Models;

use App\Filters\Filterable;
use App\Observers\TransactionObserver;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Override;

#[ObservedBy(TransactionObserver::class)]
#[Fillable(['user_id', 'category_id', 'wallet_id', 'type', 'amount', 'description', 'date', 'tags', 'transfer_id', 'split_id', 'attachment_path'])]
class Transaction extends Model
{
    use Filterable;
    use HasFactory;

    #[Override]
    protected function casts(): array
    {
        return [
            'date' => 'date:Y-m-d',
            'amount' => 'decimal:2',
            'tags' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class);
    }

    protected function attachmentUrl(): Attribute
    {
        return Attribute::get(fn () => $this->attachment_path
            ? route('transactions.attachment', $this->id)
            : null
        );
    }

    protected $appends = ['attachment_url'];

    public function isSplit(): bool
    {
        return $this->split_id !== null;
    }

    public function splitSiblings(): Builder
    {
        return self::where('split_id', $this->split_id);
    }
}
