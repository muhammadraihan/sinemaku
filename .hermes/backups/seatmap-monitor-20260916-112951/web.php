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
use App\Http\Controllers\GrafikKotaController;
use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use SebastianBergmann\CodeCoverage\Report\Html\Dashboard;

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
    Route::get('dashboard', 'DashboardController@index')->name('backoffice.dashboard');
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
    Route::resource('masterfilm', 'MasterFilmController')->except(['show']);
    Route::resource('pelaporan', 'PelaporanController');
    Route::resource('laporan', 'LaporanController');
    Route::resource('kapasitas', 'KapasitasController');
    Route::get('vendor-export', 'VendorController@export')->name('vendor.export');
    Route::resource('vendor', 'VendorController');
    Route::resource('grafik_kota', 'GrafikKotaController');
    Route::get('finance-insight', [LaporanController::class, 'financeInsight'])->name('finance-insight.index');
    Route::get('finance-insight-data', [LaporanController::class, 'financeInsightData'])->name('finance-insight.data');
    Route::get('trend-analysis', [LaporanController::class, 'trendAnalysis'])->name('trend-analysis.index');
    Route::get('trend-analysis-data', [LaporanController::class, 'trendAnalysisData'])->name('trend-analysis.data');
    Route::get('get-cinema', [PelaporanController::class,'getCinemaByCategory'])->name('ref.cinema');
    Route::get('get-kota', [PelaporanController::class,'getCityByCinema'])->name('ref.kota');
    Route::get('get-pajak', [PelaporanController::class,'getTaxByCinema'])->name('ref.pajak');
    Route::get('get-provonsi', [PelaporanController::class,'getProvinsiByCinema'])->name('ref.provinsi');
    Route::get('get-city', [PelaporanController::class,'getCityByCategory'])->name('ref.city');
    Route::get('get-type', [PelaporanController::class,'getTypeByCategory'])->name('ref.type');
    Route::get('get-studio', [PelaporanController::class,'getStudio'])->name('ref.studio');
    Route::get('get-film-start-date', [PelaporanController::class, 'getFilmStartDate'])->name('ref.film-start-date');
    Route::get('get-data', [LaporanController::class,'listData'])->name('laporan.search');
    Route::get('get-summary', [LaporanController::class,'summaryListData'])->name('laporan.summary');
    Route::get('get-performance', [LaporanController::class,'performanceListData'])->name('laporan.performance');
    Route::get('get-province-performance', [LaporanController::class,'provinceListData'])->name('laporan.province');
    Route::get('get-audit-checks', [LaporanController::class,'auditCheckListData'])->name('laporan.audit');
    Route::get('detail-export', [LaporanController::class, 'detailExport'])->name('laporan.detailExport');
    Route::get('get-chart-city', [GrafikKotaController::class,'getTopCities'])->name('getTopCities');
    Route::post('/pelaporan/upload-xxi', [PelaporanController::class, 'uploadXXI'])
    ->name('pelaporan.upload.xxi');
    Route::get('/pelaporan/upload/xxi/errors/{token}', [PelaporanController::class, 'downloadXxiErrors'])
    ->name('pelaporan.upload.xxi.errors');
    Route::post('/pelaporan/upload-cgv', [PelaporanController::class, 'uploadCGV'])
    ->name('pelaporan.upload.cgv');
    Route::get('/pelaporan/upload/cgv/errors/{token}', [PelaporanController::class, 'downloadCgvErrors'])
    ->name('pelaporan.upload.cgv.errors');
    Route::post('/pelaporan/upload-sams', [PelaporanController::class, 'uploadSAMS'])
    ->name('pelaporan.upload.sams');
    Route::get('/pelaporan/upload/sams/errors/{token}', [PelaporanController::class, 'downloadSamsErrors'])
    ->name('pelaporan.upload.sams.errors');
    // web.php
    Route::get('get-chart-audience', [GrafikKotaController::class, 'getCharAudience'])
        ->name('getCharAudience');
    Route::get('get-chart-audience-dashboard', [DashboardController::class, 'getCharAudience'])
        ->name('getCharAudienceDashboard');

});
