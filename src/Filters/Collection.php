<?php

namespace Cyrano\MediaHub\Filters;

use Closure;

class Collection
{
    public function handle($query, Closure $next)
    {
        if (empty(request()->get('collection'))) {
            return $next($query);
        }

        return $next($query)->where('collection_name', request()->get('collection'));
    }
}
