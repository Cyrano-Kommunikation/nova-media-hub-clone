<?php

namespace Cyrano\MediaHub\MediaHandler;

use Cyrano\MediaHub\MediaHub;
use Illuminate\Support\Facades\File;
use Cyrano\MediaHub\Models\Media;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Cyrano\MediaHub\MediaHandler\Support\Base64File;
use Cyrano\MediaHub\MediaHandler\Support\RemoteFile;
use Cyrano\MediaHub\MediaHandler\Support\Filesystem;
use Cyrano\MediaHub\MediaHandler\Support\FileHelpers;
use Cyrano\MediaHub\Jobs\MediaHubOptimizeAndConvertJob;
use Cyrano\MediaHub\Exceptions\NoFileProvidedException;
use Cyrano\MediaHub\Exceptions\UnknownFileTypeException;
use Symfony\Component\HttpFoundation\File\File as SymfonyFile;
use Cyrano\MediaHub\Exceptions\FileDoesNotExistException;
use Cyrano\MediaHub\Exceptions\DiskDoesNotExistException;
use Cyrano\MediaHub\Exceptions\FileValidationException;

class FileHandler
{
    /** @var Filesystem */
    protected $filesystem;

    /** @var \Symfony\Component\HttpFoundation\File\UploadedFile|string */
    protected $file;

    protected string $fileName = '';
    protected string $pathToFile = '';
    protected string $diskName = '';
    protected string $conversionsDiskName = '';
    protected int $collection_id = 1;
    protected array $modelData = [];
    protected bool $deleteOriginal = false;

    public function __construct()
    {
        $this->filesystem = app()->make(Filesystem::class);
        $this->fileNamer = config('nova-media-hub.file_namer');
    }

    public static function fromFile($file): self
    {
        return (new static)->withFile($file);
    }

    public function withFile($file): self
    {
        $this->file = $file;

        if (is_string($file)) {
            $this->pathToFile = $file;
            $this->fileName = pathinfo($file, PATHINFO_BASENAME);
            return $this;
        }

        if ($file instanceof RemoteFile) {
            $file->downloadFileToCurrentFilesystem();
            $this->pathToFile = $file->getKey();
            $this->fileName = $file->getFilename();
            return $this;
        }

        if ($file instanceof UploadedFile) {
            if ($file->getError()) {
                throw new FileValidationException($file->getErrorMessage());
            } else {
                $this->pathToFile = $file->getPath() . '/' . $file->getFilename();
                $this->fileName = $file->getClientOriginalName();
                return $this;
            }
        }

        if ($file instanceof SymfonyFile) {
            $this->pathToFile = $file->getPath() . '/' . $file->getFilename();
            $this->fileName = pathinfo($file->getFilename(), PATHINFO_BASENAME);
            return $this;
        }

        if ($file instanceof Base64File) {
            $filePath = $file->saveBase64ImageToTemporaryFile();
            $this->pathToFile = $filePath;
            $this->fileName = pathinfo($file->getFilename(), PATHINFO_BASENAME);
            return $this;
        }

        $this->file = null;
        throw new UnknownFileTypeException();
    }

    public function deleteOriginal($deleteOriginal = true)
    {
        $this->deleteOriginal = $deleteOriginal;
        return $this;
    }

    public function withCollection(int $collectionName)
    {
        $this->collection_id = $collectionName;
        return $this;
    }

    public function storeOnDisk($diskName)
    {
        $this->diskName = $diskName;
        return $this;
    }

    public function storeConversionOnDisk($diskName)
    {
        $this->conversionsDiskName = $diskName;
        return $this;
    }

    public function withModelData(array $modelData)
    {
        $this->modelData = $modelData;
        return $this;
    }

    public function save($file = null): Media
    {
        if (!empty($file)) $this->withFile($file);
        if (empty($this->file)) throw new NoFileProvidedException();
        if (!is_file($this->pathToFile)) throw new FileDoesNotExistException($this->pathToFile);

        // Check if file already exists
        $fileHash = FileHelpers::getFileHash($this->pathToFile);
        $existingMedia = MediaHub::getQuery()->where('original_file_hash', $fileHash)->first();
        if ($existingMedia) {
            $existingMedia->updated_at = now();
            $existingMedia->save();
            $existingMedia->wasExisting = true;
            return $existingMedia;
        }

        $sanitizedFileName = FileHelpers::sanitizeFileName($this->fileName);
        [$fileName, $rawExtension] = FileHelpers::splitNameAndExtension($sanitizedFileName);
        $extension = File::guessExtension($this->pathToFile) ?? $rawExtension;
        $mimeType = File::mimeType($this->pathToFile);
        $fileSize = File::size($this->pathToFile);

        $this->fileName = MediaHub::getFileNamer()->formatFileName($fileName, $extension);

        // Validate file
        $fileValidator = MediaHub::getFileValidator();
        $fileValidator->validateFile($this->collection_id, $this->pathToFile, $this->fileName, $extension, $mimeType, $fileSize);

        $mediaClass = MediaHub::getMediaModel();
        $media = new $mediaClass($this->modelData ?? []);

        $media->file_name = $this->fileName;
        $media->collection_id = $this->collection_id;
        $media->size = $fileSize;
        $media->mime_type = $mimeType;
        $media->original_file_hash = $fileHash;
        $media->data = [];
        $media->conversions = [];

        $media->disk = $this->getDiskName();
        $this->ensureDiskExists($media->disk);

        $media->conversions_disk = $this->getConversionsDiskName();
        $this->ensureDiskExists($media->conversions_disk);

        $media->save();

        $this->filesystem->copyFileToMediaLibrary($this->pathToFile, $media, $this->fileName, Filesystem::TYPE_ORIGINAL, $this->deleteOriginal);

        MediaHubOptimizeAndConvertJob::dispatch($media);

        return $media;
    }


    // Helpers
    protected function getDiskName(): string
    {
        return $this->diskName ?: config('nova-media-hub.disk_name');
    }

    protected function getConversionsDiskName(): string
    {
        return $this->conversionsDiskName ?: config('nova-media-hub.conversions_disk_name');
    }

    protected function ensureDiskExists(string $diskName)
    {
        if (is_null(config("filesystems.disks.{$diskName}"))) {
            throw new DiskDoesNotExistException($diskName);
        }
    }
}
