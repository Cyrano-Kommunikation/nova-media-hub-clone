<?php

namespace Cyrano\MediaHub\Models;

use Cyrano\MediaHub\MediaHub;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;
use Cyrano\MediaHub\MediaHandler\Support\FileNamer;

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
        if (!$thumbnailConversionName) return null;

        $thumbnailName = $this->conversions[$thumbnailConversionName] ?? null;
        if (!$thumbnailName) return null;

        return Storage::disk($this->conversions_disk)->url($this->conversionsPath . $thumbnailName);
    }

    public function formatForNova()
    {
        $data = null;
        if (str_contains($this->mime_type, 'image')) {
            $data = file_get_contents(storage_path("app/media/$this->id/$this->file_name"));
        }
        return [
            'id' => $this->id,
            'collection_id' => $this->collection_id,
            'url' => $data ? 'data:' . $this->mime_type . ';base64,' . base64_encode($data) : $this->url,
            'thumbnail_url' => $data ? 'data:' . $this->mime_type . ';base64,' . base64_encode($data) : $this->thumbnailUrl,
            'mime_type' => $this->mime_type,
            'size' => $this->size,
            'file_name' => $this->file_name,
            'data' => $this->data,
            'conversions' => $this->conversions,
        ];
    }

    public function collection(): BelongsTo
    {
        return $this->belongsTo(Collection::class);
    }
}
