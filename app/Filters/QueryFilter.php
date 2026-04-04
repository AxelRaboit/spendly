<?php

declare(strict_types=1);

namespace App\Filters;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

abstract class QueryFilter
{
    public function __construct(protected readonly Request $request) {}

    public function apply(Builder $query): Builder
    {
        foreach ($this->request->all() as $key => $value) {
            if (method_exists($this, $key) && filled($value)) {
                $this->$key($query, $value);
            }
        }

        return $query;
    }
}
