<?php

namespace Cyrano\MediaHub\Http\Controllers;

use Cyrano\MediaHub\Filters\Collection;
use Cyrano\MediaHub\Filters\Search;
use Cyrano\MediaHub\Filters\Sort;
use Cyrano\MediaHub\MediaHandler\Support\Filesystem;
use Cyrano\MediaHub\MediaHub;
use Cyrano\MediaHub\Models\Role;
use Exception;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pipeline\Pipeline;
use Illuminate\Routing\Controller;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\File;
use Intervention\Image\Facades\Image;

class MediaHubController extends Controller
{
    public function getCollections()
    {
        $collections = MediaHub::getCollectionModel()::all()->toArray();

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
                Collection::class,
                Search::class,
                Sort::class,
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
                    ->withCollection((int)$collectionName)
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
        if (!$collectionName) {
            return response()->json(
                ['error' => 'Es wird eine Kollektion benötigt, zu der du die Datei verschieben möchtest.'],
                400
            );
        }

        $media = MediaHub::getQuery()->findOrFail($mediaId);
        $collection = MediaHub::getCollectionModel()::findOrFail($collectionName);

        $media->collection_id = $collectionName;
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
                    $foundTag = MediaHub::getTagModel()::where('name', $tag)->first();
                    if (!$foundTag) {
                        MediaHub::getTagModel()::create([
                            'name' => $tag,
                            'description' => ''
                        ]);
                    }
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
    public function update(Request $request, string $collectionId): JsonResponse
    {
        $collectionData = $request->input('collectionData');
        if (!array_key_exists('name', $collectionData)) {
            return response()->json(['error' => 'Der Kollektionsname wird benötigt.'], 400);
        }

        if (!array_key_exists('roles', $collectionData)) {
            return response()->json(
                ['error' => 'Irgendetwas ist beim Aktualisieren der Kollektion schiefgelaufen. Bitte aktualisiere die Seite und versuche es erneut.'],
                400
            );
        }

        $collection = MediaHub::getCollectionModel()::findOrFail($collectionId);

        $collection->name = $collectionData['name'];
        $collection->roles()->sync($collectionData['roles']);
        $collection->save();

        return response()->json('', 200);
    }

    /**
     * Delete Collection.
     * @param string $collectionId
     * @return JsonResponse
     * @throws BindingResolutionException
     */
    public function deleteCollection(string $collectionId): JsonResponse
    {
        if (!$collectionId) {
            return response()->json(['error' => 'Es wurde keine Kollektionsid übergeben.'], 400);
        }

        $collection = MediaHub::getCollectionModel()::findOrFail($collectionId);

        $medias = MediaHub::getQuery()->where('collection_id', $collectionId)->get();
        $defaultCollections = MediaHub::getDefaultCollections();

        if (in_array($collection->name, $defaultCollections)) {
            return response()->json(
                ['errors' => ['Du kannst diese Kollektion nicht löschen, da diese als Standard festgelegt wurde.']],
                400
            );
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
        if (!$request->input('collectionName')) {
            return response()->json(['error' => 'Bitte gebe einen Kollektionsnamen an.'], 400);
        }

        MediaHub::getCollectionModel()::create([
            'name' => $request->input('collectionName'),
            'default' => false
        ]);

        return response()->json('', 200);
    }


    public function getImage(Request $request)
    {
        $fileName = $request->get('fileName');
        $fileId = $request->get('fileId');
        if (str_contains($request->get('mime_type'), 'image')) {
            $path = storage_path() . '/app/media/' . $fileId . '/' . $fileName;

            if (!File::exists($path)) {
                return null;
            }

            $imageStream = Image::make(File::get($path))->stream();
            return $imageStream->__toString();
        }

        return null;
    }

    public function downloadFile($id)
    {
        ob_end_clean();
        $mediaItem = MediaHub::getMediaModel()::findOrFail($id);

        $path = storage_path() . '/app/media/' . $id . '/' . $mediaItem->file_name;
        if (!File::exists($path)) {
            return response()->json([
                'error' => 'Diese Datei existiert nicht.'
            ], 200);
        }

        return response()->download($path);
    }

    public function retrieveCollection($id): JsonResponse
    {
        $collection = MediaHub::getCollectionModel()::with('roles')->findOrFail($id);

        return response()->json($collection);
    }
}
