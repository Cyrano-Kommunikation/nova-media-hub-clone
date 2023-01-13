<?php

namespace Cyrano\MediaHub\Models;

use Cyrano\MediaHub\MediaHub;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Facades\Image;

class Media extends Model
{
    protected $casts = [
        'data' => 'array',
        'conversions' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'optimized_at' => 'datetime',
    ];

    protected $appends = ['url', 'thumbnail_url'];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->setTable(MediaHub::getTableName());
    }

    public function getPathAttribute()
    {
        $pathMaker = MediaHub::getPathMaker();
        return $pathMaker->getPath($this);
    }

    public function getConversionsPathAttribute()
    {
        $pathMaker = MediaHub::getPathMaker();
        return $pathMaker->getConversionsPath($this);
    }

    public function getUrlAttribute()
    {
        return Storage::disk($this->disk)->url($this->path . $this->file_name);
    }

    public function getThumbnailUrlAttribute()
    {
        $thumbnailConversionName = MediaHub::getThumbnailConversionName();
        if (!$thumbnailConversionName) {
            return null;
        }

        $thumbnailName = $this->conversions[$thumbnailConversionName] ?? null;
        if (!$thumbnailName) {
            return null;
        }

        return Storage::disk($this->conversions_disk)->url($this->conversionsPath . $thumbnailName);
    }

    public function formatForNova(): array
    {
        return [
            'id' => $this->id,
            'collection_id' => $this->collection_id,
            'url' => null,
            'thumbnail_url' => null,
            'mime_type' => $this->mime_type,
            'size' => $this->size,
            'file_name' => $this->file_name,
            'data' => $this->data,
            'conversions' => $this->conversions,
            'tags' => $this->tags()->get(),
            'roles' => $this->roles()->get()
        ];
    }

    public function collection(): BelongsTo
    {
        return $this->belongsTo(Collection::class);
    }

    public function tags(): MorphToMany
    {
        return $this->morphToMany(MediaHub::getTagModel(), 'taggable');
    }

    public function roles(): MorphToMany
    {
        return $this->morphToMany(MediaHub::getRoleModel(), 'roleable');
    }
}
