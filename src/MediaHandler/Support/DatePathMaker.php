<?php

namespace Cyrano\MediaHub\MediaHandler\Support;

use Illuminate\Support\Str;
use Cyrano\MediaHub\Models\Media;

class DatePathMaker extends PathMaker
{
    // Get a unique base path for the given media.
    protected function getBasePath(Media $media): string
    {
        $prefix = config('nova-media-hub.path_prefix', '');
        $year = $media->created_at->year;
        $month = Str::padLeft($media->created_at->month, 2, '0');
        return $prefix . '/' . $year . '/' . $month . '/' . $media->getKey();
    }
}
