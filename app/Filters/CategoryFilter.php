<?php

declare(strict_types=1);

namespace App\Filters;

use Illuminate\Database\Eloquent\Builder;

class CategoryFilter extends QueryFilter
{
    public function search(Builder $query, string $value): void
    {
        $query->whereRaw('unaccent(name) ILIKE unaccent(?)', [sprintf('%%%s%%', $value)]);
    }
}
