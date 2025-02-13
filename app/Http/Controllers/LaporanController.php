<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Models\Pelaporan;
use App\Models\TypeTiket;
use App\Models\KategoriBioskop;
use App\Models\MasterBioskop;
use App\Models\Laporan;


use Auth;
use DataTables;
use DB;
use URL;
use Helper;
use Image;
use Response;

class LaporanController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $bioskop_kategori = KategoriBioskop::all()->pluck('name', 'uuid');
        $nama_bioskop = MasterBioskop::all()->pluck('nama_bioskop', 'uuid');
        $kota = MasterBioskop::selectRaw('Distinct kota')->pluck('kota', 'kota');
        $type_tiket = TypeTiket::all()->pluck('name', 'uuid');
        $nama_film = Pelaporan::selectRaw('Distinct nama_film')->pluck('nama_film', 'nama_film');
        return view('laporan.index', compact('bioskop_kategori', 'nama_bioskop', 'kota','type_tiket', 'nama_film'));
    }

    // public function listData(Request $request){
    //     $where_conditions = [];
    //     $params = [];

    //     if ($request->nama_film != 'ALL') {
    //         $where_conditions[] = "nama_film = ?";
    //         $params[] = $request->nama_film;
    //     }

    //     if ($request->tgl_mulai != '' && $request->tgl_akhir != '') {
    //         $where_conditions[] = "tgl_tayang BETWEEN ? AND ?";
    //         $params[] = $request->tgl_mulai;
    //         $params[] = $request->tgl_akhir;
    //     }

    //     if ($request->bioskop_kategori != 'ALL') {
    //         $bioskop_kategori = $request->bioskop_kategori;
    //         if ($bioskop_kategori) {
    //             $where_conditions[] = "kategori = ?";
    //             $params[] = $bioskop_kategori;
    //         }
    //     }

    //     if ($request->kota != 'ALL') {
    //         $where_conditions[] = "kota = ?";
    //         $params[] = $request->kota;
    //     }

    //     if ($request->nama_bioskop != 'ALL') {
    //         $nama_bioskop = $request->nama_bioskop;
    //         if ($nama_bioskop) {
    //             $where_conditions[] = "nama_bioskop = ?";
    //             $params[] = $nama_bioskop;
    //         }
    //     }

    //     if ($request->type_tiket != 'ALL') {
    //         $type_tiket = $request->type_tiket;
    //         if ($type_tiket) {
    //             $where_conditions[] = "type_tiket = ?";
    //             $params[] = $type_tiket;
    //         }
    //     }

    //     // Gabungkan where conditions menjadi string dengan " AND "
    //     $where_query = implode(' AND ', $where_conditions);
    //     // dd($params);

    //     $result = DB::select("select * from pelaporans where $where_query", $params);

    //     return Datatables::of($result)
    //             ->addIndexColumn()
    //             ->editColumn('kategori', function ($row){
    //                 return $row->Categories->name;
    //             })
    //             ->editColumn('nama_bioskop', function ($row){
    //                 return $row->Cinemas->nama_bioskop;
    //             })
    //             ->editColumn('type_tiket', function ($row){
    //                 return $row->TypeTiket->name;
    //             })
    //             ->editColumn('jam_tayang', function ($row) {
    //                 return \Carbon\Carbon::parse($row->jam_tayang)->format('H:i'); // Format hh:mm
    //             })
    //             ->removeColumn('id')
    //             ->removeColumn('uuid')
    //             ->rawColumns(['action','type'])
    //             ->make(true);
    // }

    public function listData(Request $request) {
        // Ambil semua data dengan relasi yang dibutuhkan
        $query = Pelaporan::with(['categories', 'cinemas', 'typeTiket']);
    
        // Filter berdasarkan input user
        if ($request->nama_film != 'ALL') {
            $query->where('nama_film', $request->nama_film);
        }
    
        if (!empty($request->tgl_mulai) && !empty($request->tgl_akhir)) {
            $query->whereBetween('tgl_tayang', [$request->tgl_mulai, $request->tgl_akhir]);
        }
    
        if ($request->bioskop_kategori != 'ALL') {
            $query->where('kategori', $request->bioskop_kategori);
        }
    
        if ($request->kota != 'ALL') {
            $query->where('kota', $request->kota);
        }
    
        if ($request->nama_bioskop != 'ALL') {
            $query->where('nama_bioskop', $request->nama_bioskop);
        }
    
        if ($request->type_tiket != 'ALL') {
            $query->where('type_tiket', $request->type_tiket);
        }
    
        // Ambil data setelah filtering
        $data = $query->get();
    
        // Kirim data ke Datatables
        return Datatables::of($data)
            ->addIndexColumn()
            ->editColumn('kategori', function ($row) {
                return $row->categories ? $row->categories->name : '-';
            })
            ->editColumn('nama_bioskop', function ($row) {
                return $row->cinemas ? $row->cinemas->nama_bioskop : '-';
            })
            ->editColumn('type_tiket', function ($row) {
                return $row->typeTiket ? $row->typeTiket->name : '-';
            })
            ->editColumn('jam_tayang', function ($row) {
                return $row->jam_tayang ? \Carbon\Carbon::parse($row->jam_tayang)->format('H:i') : '-';
            })
            ->removeColumn('id')
            ->removeColumn('uuid')
            ->make(true);
    }
    

}
