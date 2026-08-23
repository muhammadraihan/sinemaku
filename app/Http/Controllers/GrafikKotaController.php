<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Models\Pelaporan;
use App\Models\TypeTiket;
use App\Models\KategoriBioskop;
use App\Models\MasterBioskop;
use App\Models\MasterFilm;
use App\Models\Laporan;


use Auth;
use DataTables;
use DB;
use URL;
use Helper;
use Image;
use Response;

class GrafikKotaController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $bioskop_kategori = ['ALL' => 'Semua ...']
            + KategoriBioskop::all()->pluck('name', 'uuid')->toArray();
        // $nama_bioskop = MasterBioskop::all()->pluck('nama_bioskop', 'uuid');
        $kota = MasterBioskop::selectRaw('Distinct kota')->pluck('kota', 'kota');
        // $type_tiket = TypeTiket::all()->pluck('name', 'uuid');
        $nama_film = MasterFilm::options();
        return view('grafik_kota.index', compact('bioskop_kategori', 'kota', 'nama_film'));
    }

    public function getTopCities(Request $request)
    {
        $nama_film = $request->nama_film;
        $start_date = $request->tgl_mulai;
        $end_date = $request->tgl_akhir;
        $bioskop_kategori = $request->bioskop_kategori;

        $query = DB::table('pelaporans')
            ->select('kota', DB::raw('SUM(jumlah) as jumlah'))
            ->whereBetween('tgl_tayang', [$start_date, $end_date])
            ->where('nama_film', $nama_film);

        if ($bioskop_kategori && $bioskop_kategori !== 'ALL') {
            $query->where('kategori', $bioskop_kategori);
        }

        $data = $query->groupBy('kota')
            ->orderByDesc('jumlah')
            ->limit(10)
            ->get();

        return response()->json($data);
    }
    

}
