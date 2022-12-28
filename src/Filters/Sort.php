<?php

namespace Cyrano\NovaMediaHubClone\Filters;

use Closure;
use Illuminate\Support\Str;

class Sort
{
    public function handle($query, Closure $next)
    {
        if (empty(request()->get('orderBy'))) {
            return $next($query)->orderBy('updated_at', 'DESC');
        }

        [$column, $direction] = Str::of(request()->get('orderBy'))
            ->explode(':')
            ->toArray();

        return $next($query)->orderBy($column, $direction);
    }
}
