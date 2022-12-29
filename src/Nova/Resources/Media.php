<?php

namespace Cyrano\MediaHub\Nova\Resources;

use Laravel\Nova\Resource;
use Illuminate\Http\Request;
use Cyrano\MediaHub\MediaHub;

class Media extends Resource
{
    public static $title = 'key';
    public static $model = null;
    public static $displayInNavigation = false;

    public function __construct($resource)
    {
        self::$model = MediaHub::getMediaModel();
        parent::__construct($resource);
    }

    public function fields(Request $request)
    {
        return [];
    }
}
