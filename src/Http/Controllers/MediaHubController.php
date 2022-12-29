<?php

namespace Cyrano\MediaHub\Http\Controllers;

use BeyondCode\QueryDetector\Outputs\Log;
use Exception;
use Illuminate\Support\Arr;
use Illuminate\Http\Request;
use Illuminate\Pipeline\Pipeline;
use Illuminate\Routing\Controller;
use Cyrano\MediaHub\MediaHub;
use Cyrano\MediaHub\MediaHandler\Support\Filesystem;
use Cyrano\MediaHub\Models\Media;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class MediaHubController extends Controller
{
    public function getCollections(Request $request)
    {
        $defaultCollections = MediaHub::getDefaultCollections();

        $collections = MediaHub::getMediaModel()::select('collection_name')
            ->groupBy('collection_name')
            ->get()
            ->pluck('collection_name')
            ->merge($defaultCollections)
            ->unique()
            ->values()
            ->toArray();

        return response()->json($collections, 200);
    }

    public function getMedia()
    {
        $media = app(Pipeline::class)
            ->send(MediaHub::getQuery())->through([
                \Cyrano\MediaHub\Filters\Collection::class,
                \Cyrano\MediaHub\Filters\Search::class,
                \Cyrano\MediaHub\Filters\Sort::class,
            ])->thenReturn()->paginate(72);


        $newCollection = $media->getCollection()->map->formatForNova();
        $media->setCollection($newCollection);

        return response()->json($media, 200);
    }

    public function uploadMediaToCollection(Request $request)
    {
        $files = $request->allFiles()['files'] ?? [];
        $collectionName = $request->get('collectionName') ?? 'default';

        $exceptions = [];

        $uploadedMedia = [];
        foreach ($files as $file) {
            try {
                $uploadedMedia[] = MediaHub::fileHandler()
                    ->withFile($file)
                    ->withCollection($collectionName)
                    ->save();
            } catch (Exception $e) {
                $exceptions[] = $e;
                report($e);
            }
        }

        $uploadedMedia = collect($uploadedMedia);
        $coreResponse = [
            'media' => $uploadedMedia->map->formatForNova(),
            'hadExisting' => $uploadedMedia->where(fn ($m) => $m->wasExisting)->count() > 0,
            'success_count' => count($files) - count($exceptions),
        ];

        if (!empty($exceptions)) {
            return response()->json([
                ...$coreResponse,
                'errors' => Arr::map($exceptions, function ($e) {
                    $className = class_basename(get_class($e));
                    return "{$className}: {$e->getMessage()}";
                }),
            ], 400);
        }

        return response()->json($coreResponse, 200);
    }

    public function deleteMedia(Request $request)
    {
        $mediaId = $request->route('mediaId');
        if ($mediaId && $media = MediaHub::getQuery()->find($mediaId)) {
            /** @var Filesystem */
            $fileSystem = app()->make(Filesystem::class);
            $fileSystem->deleteFromMediaLibrary($media);
            $media->delete();
        }
        return response()->json('', 204);
    }

    public function moveMediaToCollection(Request $request, $mediaId)
    {
        $collectionName = $request->get('collection');
        if (!$collectionName) return response()->json(['error' => 'Collection name required.'], 400);

        $media = MediaHub::getQuery()->findOrFail($mediaId);

        $media->collection_name = $collectionName;
        $media->save();

        return response()->json($media, 200);
    }

    public function updateMediaData(Request $request, $mediaId)
    {
        dd($request->all());
        $media = MediaHub::getQuery()->findOrFail($mediaId);
        $locales = MediaHub::getLocales();
        $fieldKeys = array_keys(MediaHub::getDataFields());

        // No translations, we hardcoded frontend to always send data as 'en'
        if (empty($locales)) {
            $mediaData = $media->data;
            dd($fieldKeys);
            foreach ($fieldKeys as $key) {
                if ($key == 'tags') {

                }
                $mediaData[$key] = $request->input("{$key}.en") ?? null;
            }
            $media->data = $mediaData;
        } else {
            $mediaData = $media->data;
            foreach ($fieldKeys as $key) {
                $mediaData[$key] = $request->input($key) ?? null;
            }
            $media->data = $mediaData;
        }

        $media->save();

        return response()->json($media, 200);
    }

    public function rename(Request $request, string $collectionId): JsonResponse
    {
        $collectionName = $request->input('newCollectionName');
        if (!$collectionName) return response()->json(['error' => 'Der neue Kollektionsname wird benötigt.'], 400);


        DB::transaction(function () use ($collectionId, $collectionName) {
            $medias = MediaHub::getQuery()->where('collection_name', $collectionId)->get();

            foreach ($medias as $media) {
                $media->update([
                    'collection_name' => $collectionName
                ]);
            }
        });

        return response()->json('', 200);
    }

    public function deleteCollection(string $collectionId): JsonResponse
    {
        if (!$collectionId) return response()->json(['error' => 'Es wurde keine Kollektionsid übergeben.'], 400);

        $medias = MediaHub::getQuery()->where('collection_name', $collectionId)->get();
        $defaultCollections = MediaHub::getDefaultCollections();

        if (in_array($collectionId, $defaultCollections)) {
            return response()->json(['errors' => ['Du kannst diese Kollektion nicht löschen, da diese als Standard festgelegt wurde.']], 400);
        }

        foreach ($medias as $media) {
            $fileSystem = app()->make(Filesystem::class);
            $fileSystem->deleteFromMediaLibrary($media);
            $media->delete();
        }

        return response()->json('', 200);
    }
}
