<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['budget_id', 'type', 'label', 'planned_amount', 'carried_over', 'target_type', 'target_amount', 'target_deadline', 'category_id', 'position', 'notes', 'repeat_next_month'])]
class BudgetItem extends Model
{
    use HasFactory;

    protected $casts = [
        'target_deadline' => 'date:Y-m-d',
    ];

    /** @return BelongsTo<Budget, $this> */
    public function budget(): BelongsTo
    {
        return $this->belongsTo(Budget::class);
    }

    /** @return BelongsTo<Category, $this> */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }
}
