<?php

namespace Cyrano\MediaHub\Jobs;

use Illuminate\Bus\Queueable;
use Cyrano\MediaHub\MediaHub;
use Illuminate\Queue\SerializesModels;
use Cyrano\MediaHub\Models\Media;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Cyrano\MediaHub\MediaHandler\Support\Filesystem;
use Cyrano\MediaHub\MediaHandler\Support\FileHelpers;
use Cyrano\MediaHub\MediaHandler\Support\MediaOptimizer;

class MediaHubOptimizeAndConvertJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 180;

    protected $mediaId = null;

    public function __construct(Media $media)
    {
        $this->mediaId = $media->id;
        $this->onQueue(MediaHub::getImageConversionsJobQueue());
    }

    public function handle()
    {
        $media = MediaHub::getQuery()->find($this->mediaId);
        if (!$media) return;

        $fileSystem = $this->getFileSystem();
        $localFilePath = $fileSystem->copyFromMediaLibrary($media, FileHelpers::getTemporaryFilePath('job-tmp-media-'));

        // Optimize original
        MediaOptimizer::optimizeOriginalImage($media, $localFilePath);

        // Create conversions
        $conversions = MediaHub::getConversionForMedia($media);
        foreach ($conversions as $conversionName => $conversion) {
            MediaOptimizer::makeConversion($media, $localFilePath, $conversionName, $conversion);
        }

        // Delete local file
        if (is_file($localFilePath)) {
            unlink($localFilePath);
        }
    }

    protected function getFileSystem(): Filesystem
    {
        return app()->make(Filesystem::class);
    }
}
