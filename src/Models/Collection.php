<?php

namespace Cyrano\MediaHub\Models;

use Cyrano\MediaHub\MediaHub;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Collection extends Model
{
    protected $fillable = [
        'name'
    ];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->setTable(MediaHub::getCollectionTableName());
    }

    public function medias(): HasMany
    {
        return $this->hasMany(Media::class);
    }
}
