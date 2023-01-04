<?php

use Illuminate\Support\Facades\Route;
use Cyrano\MediaHub\Http\Controllers\MediaHubController;
use Cyrano\MediaHub\Models\Media;

/*
|--------------------------------------------------------------------------
| Tool API Routes
|--------------------------------------------------------------------------
|
| Here is where you may register API routes for your tool. These routes
| are loaded by the ServiceProvider of your tool. They are protected
| by your tool's "Authorize" middleware by default. Now, go build!
|
*/

Route::prefix('/nova-vendor/media-hub')->group(function () {
    Route::get('/media', [MediaHubController::class, 'getMedia']);
    Route::get('/collections', [MediaHubController::class, 'getCollections']);

    Route::post('/media/{mediaId}/data', [MediaHubController::class, 'updateMediaData']);
    Route::post('/media/{mediaId}/move', [MediaHubController::class, 'moveMediaToCollection']);
    Route::post('/media/save', [MediaHubController::class, 'uploadMediaToCollection']);
    Route::delete('media/{mediaId}', [MediaHubController::class, 'deleteMedia']);

    Route::post('collection/store', [MediaHubController::class, 'storeCollection']);
    Route::post('collection/{collection}/rename', [MediaHubController::class, 'rename']);
    Route::delete('collection/{collection}/delete', [MediaHubController::class, 'deleteCollection']);

    Route::get('roles/retrieve', [MediaHubController::class, 'getRoles']);
    Route::get('tags/retrieve', [MediaHubController::class, 'getTags']);
});
