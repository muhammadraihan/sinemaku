<?php
use Illuminate\Support\Facades\Route;
Route::group(['prefix'=>'v1'], function () { Route::post('login', 'Api\AuthController@Login'); });
Route::group(['prefix'=>'v1','middleware'=>['jwt']], function () { Route::get('logout','Api\AuthController@Logout'); });
Route::prefix('internal/cinepoint')->middleware('cinepoint.collector')->group(function () {
    Route::post('snapshots', 'Api\CinepointCollectorController@store')->middleware('throttle:60,1')->name('api.internal.cinepoint.snapshots');
    Route::post('sync-jobs/claim', 'Api\CinepointCollectorController@claim')->middleware('throttle:60,1');
    Route::post('sync-jobs/{id}/result', 'Api\CinepointCollectorController@result')->where('id', '[0-9]+')->middleware('throttle:60,1');
});
