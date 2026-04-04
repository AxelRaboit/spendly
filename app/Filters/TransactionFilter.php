<?php

declare(strict_types=1);

namespace App\Filters;

use Illuminate\Database\Eloquent\Builder;

class TransactionFilter extends QueryFilter
{
    public function search(Builder $query, string $value): void
    {
        $query->whereRaw('unaccent(description) ILIKE unaccent(?)', [sprintf('%%%s%%', $value)]);
    }

    public function category_id(Builder $query, string $value): void
    {
        $query->where('category_id', (int) $value);
    }
}
