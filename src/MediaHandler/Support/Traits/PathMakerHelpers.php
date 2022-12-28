<?php

namespace Cyrano\NovaMediaHubClone\MediaHandler\Support\Traits;

use Illuminate\Support\Facades\Storage;
use Cyrano\NovaMediaHubClone\MediaHandler\Support\FileHelpers;
use Cyrano\NovaMediaHubClone\MediaHub;
use Cyrano\NovaMediaHubClone\Models\Media;

trait PathMakerHelpers
{
    public function getFullPathWithFileName(Media $media)
    {
        $filePath = $this->getPath($media) . $media->file_name;
        return Storage::disk($media->disk)->path($filePath);
    }

    public function getConversionPathWithFileName(Media $media, $conversionName)
    {
        $conversionFileName = $this->getConversionFileName($media, $conversionName);
        $filePath = $this->getConversionsPath($media) . $conversionFileName;
        return Storage::disk($media->conversions_disk)->path($filePath);
    }

    public function getConversionFileName(Media $media, $conversionName)
    {
        [$fileName, $extension] = FileHelpers::splitNameAndExtension($media->file_name);
        $fileNamer = MediaHub::getFileNamer();
        return $fileNamer->formatConversionFileName($fileName, $extension, $conversionName);
    }
}
