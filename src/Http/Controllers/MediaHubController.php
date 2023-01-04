<?php

namespace Cyrano\MediaHub\Http\Controllers;

use BeyondCode\QueryDetector\Outputs\Log;
use Cyrano\MediaHub\Models\Collection;
use Cyrano\MediaHub\Models\Role;
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
        $collections = MediaHub::getCollectionModel()::with('medias.tags')->get()->toArray();

        return response()->json($collections, 200);
    }

    public function getRoles()
    {
        $roles = MediaHub::getRoleModel()::all();

        return response()->json($roles, 200);
    }

    public function getTags()
    {
        $tags = MediaHub::getTagModel()::all();

        return response()->json($tags, 200);
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
        $collectionName = $request->get('collectionName') ?? 1;

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
            'hadExisting' => $uploadedMedia->where(fn($m) => $m->wasExisting)->count() > 0,
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

    /**
     * Update media data column according to fields on view.
     * @param Request $request
     * @param $mediaId
     * @return JsonResponse
     */
    public function updateMediaData(Request $request, $mediaId): JsonResponse
    {
        $media = MediaHub::getQuery()->findOrFail($mediaId);
        $fields = $request->all();
        $data = [];
        foreach ($fields as $key => $field) {
            if ($key == 'tags') {
                $arr = [];
                foreach ($field as $tag) {
                    $foundTag = MediaHub::getTagModel()::updateOrCreate([
                        'name' => $tag,
                        'description' => ''
                    ]);
                    $arr[] = $foundTag->id;
                }

                $media->tags()->sync($arr);
                continue;
            }

            if ($key == 'roles') {
                $roleNotFound = false;
                foreach ($field as $role) {
                    $role = Role::find($role);
                    if (!$role) {
                        $roleNotFound = true;
                    }
                }
                if ($roleNotFound) {
                    continue;
                }
                $media->roles()->sync($field);
            }

            $data[$key] = $field;
        }

        $media->data = $data;

        $media->save();

        return response()->json($media, 200);
    }

    /**
     * Rename collection with its new name.
     * @param Request $request
     * @param string $collectionId
     * @return JsonResponse
     */
    public function rename(Request $request, string $collectionId): JsonResponse
    {
        $collectionName = $request->input('newCollectionName');
        if (!$collectionName) return response()->json(['error' => 'Der neue Kollektionsname wird benötigt.'], 400);

        $collection = MediaHub::getCollectionModel()::findOrFail($collectionId);

        $collection->name = $collectionName;
        $collection->save();

        return response()->json('', 200);
    }

    /**
     * Delete Collection.
     * @param string $collectionId
     * @return JsonResponse
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    public function deleteCollection(string $collectionId): JsonResponse
    {
        if (!$collectionId) return response()->json(['error' => 'Es wurde keine Kollektionsid übergeben.'], 400);

        $collection = MediaHub::getCollectionModel()::findOrFail($collectionId);

        $medias = MediaHub::getQuery()->where('collection_id', $collectionId)->get();
        $defaultCollections = MediaHub::getDefaultCollections();

        if (in_array($collection->name, $defaultCollections)) {
            return response()->json(['errors' => ['Du kannst diese Kollektion nicht löschen, da diese als Standard festgelegt wurde.']], 400);
        }

        $collection->delete();

        foreach ($medias as $media) {
            $fileSystem = app()->make(Filesystem::class);
            $fileSystem->deleteFromMediaLibrary($media);
            $media->delete();
        }

        return response()->json('', 200);
    }

    /**
     * Create a new collection.
     * @param Request $request
     * @return JsonResponse
     */
    public function storeCollection(Request $request): JsonResponse
    {
        if (!$request->input('collectionName')) return response()->json(['error' => 'Bitte gebe einen Kollektionsnamen an.'], 400);

        MediaHub::getCollectionModel()::create([
            'name' => $request->input('collectionName'),
            'default' => false
        ]);

        return response()->json('', 200);
    }
}
