<?php

namespace Cyrano\MediaHub\Models;

use App\Traits\HasRoles;
use Cyrano\MediaHub\MediaHub;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

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

    public function roles(): MorphToMany
    {
        return $this->morphToMany(MediaHub::getRoleModel(), 'roleable');
    }
}
