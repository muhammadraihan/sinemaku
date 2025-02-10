<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

use App\Http\Controllers\LaporanController;
use App\Http\Controllers\PelaporanController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    // check if user is auth then redirect to dashboard page
    if (Auth::check()) {
        return redirect()->route('backoffice.dashboard');
    }
    return view('welcome');
});

Auth::routes(['register' => false]);

Route::group(['prefix' => 'backoffice', 'middleware' => ['auth']], function () {
    // backoffice
    Route::get('/', 'DashboardController@index');
    Route::get('dashboard', 'DashboardController@dashboard')->name('backoffice.dashboard');
    // logs
    Route::get('logs', 'ActivityController@index')->name('logs');
    // profile
    Route::get('profile', 'UserController@profile')->name('profile');
    Route::patch('profile/{user}/update', 'UserController@ProfileUpdate')->name('profile.update');
    Route::patch('profile/{user}/password', 'UserController@ChangePassword')->name('profile.password');
    // resource
    Route::resource('menus', 'MenuController');
    Route::resource('users', 'UserController');
    Route::resource('permissions', 'PermissionController');
    Route::resource('roles', 'RoleController');
    Route::resource('slide', 'SlideController');
    Route::resource('masterbioskop', 'MasterBioskopController');
    Route::resource('kategoribioskop', 'KategoriBioskopController');
    Route::resource('typetiket', 'TypeTiketController');
    Route::resource('pelaporan', 'PelaporanController');
    Route::resource('laporan', 'LaporanController');
    Route::resource('kapasitas', 'KapasitasController');
    Route::get('get-cinema', [PelaporanController::class,'getCinemaByCategory'])->name('ref.cinema');
    Route::get('get-kota', [PelaporanController::class,'getCityByCinema'])->name('ref.kota');
    Route::get('get-provonsi', [PelaporanController::class,'getProvinsiByCinema'])->name('ref.provinsi');
    Route::get('get-city', [PelaporanController::class,'getCityByCategory'])->name('ref.city');
    Route::get('get-type', [PelaporanController::class,'getTypeByCategory'])->name('ref.type');
    Route::get('get-studio', [PelaporanController::class,'getStudio'])->name('ref.studio');
    Route::get('get-data', [LaporanController::class,'listData'])->name('laporan.search');
});
