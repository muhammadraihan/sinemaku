<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Models\Pelaporan;
use App\Models\TypeTiket;
use App\Models\KategoriBioskop;
use App\Models\MasterBioskop;
use App\Models\Kapasitas;
use App\Models\Kota;
use App\Models\Province;
use Auth;
use DataTables;
use URL;
use Helper;
use Image;
use Response;

class PelaporanController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $pelaporan = Pelaporan::all();
        if (request()->ajax()) {
            $data = Pelaporan::get();

            return Datatables::of($data)
                ->addIndexColumn()
                ->editColumn('kategori', function ($row){
                    return $row->Categories->name;
                })
                ->editColumn('nama_bioskop', function ($row){
                    return $row->Cinemas->nama_bioskop;
                })
                ->editColumn('type_tiket', function ($row){
                    return $row->TypeTiket->name;
                })
                ->editColumn('studio', function ($row){
                    return $row->Studio->studio ?? null;
                })
                ->editColumn('jam_tayang', function ($row) {
                    return \Carbon\Carbon::parse($row->jam_tayang)->format('H:i'); // Format hh:mm
                })
                ->editColumn('created_by', function($row){
                    return $row->userCreate->name;
                })
                ->editColumn('created_at', function($row){
                    return \Carbon\Carbon::parse($row->created_at)->format('d-m-Y'); // Format hh:mm
                })
                ->editColumn('edited_by', function($row){
                    return $row->userEdit->name ?? null;
                })
                ->editColumn('updated_at', function($row){
                    return $row->updated_at ? \Carbon\Carbon::parse($row->updated_at)->format('d-m-Y') : '-';
                })
                ->addColumn('action', function ($row) {
                    if (Auth::user()->hasRole(['superadmin', 'superuser'])) {
                    return '
                            <a class="btn btn-success btn-sm btn-icon waves-effect waves-themed" href="' . route('pelaporan.edit', $row->uuid) . '"><i class="fal fa-edit"></i></a>
                            <a class="btn btn-danger btn-sm btn-icon waves-effect waves-themed delete-btn" data-url="' . URL::route('pelaporan.destroy', $row->uuid) . '" data-id="' . $row->uuid . '" data-token="' . csrf_token() . '" data-toggle="modal" data-target="#modal-delete"><i class="fal fa-trash-alt"></i></a>';
                    }else if(Auth::user()->hasRole(['admin1'])){
                        return '
                            <a class="btn btn-success btn-sm btn-icon waves-effect waves-themed" href="' . route('pelaporan.edit', $row->uuid) . '"><i class="fal fa-edit"></i></a>';
                    }
                })
                ->removeColumn('id')
                ->removeColumn('uuid')
                ->rawColumns(['action','type'])
                ->make(true);
        }

        return view('pelaporan.index');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $bioskop_kategori = KategoriBioskop::all()->pluck('name', 'uuid');
        $nama_bioskop = MasterBioskop::all()->pluck('nama_bioskop', 'uuid');
        $kota = MasterBioskop::selectRaw('Distinct kota')->pluck('kota', 'kota');
        $type_tiket = TypeTiket::all()->pluck('name', 'uuid');
        $studio = Kapasitas::all()->pluck('studio', 'uuid');
        return view('pelaporan.create', compact('bioskop_kategori', 'nama_bioskop', 'kota','type_tiket', 'studio'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $rules = [
            'kategori' => 'required',
            'kota' => 'required',
            'nama_bioskop' => 'required',
            'nama_film' => 'required',
            'tgl_tayang' => 'required',
            // 'jam_tayang' => 'required',
            'show' => 'required',
            'type_tiket' => 'required',
            'harga' => 'required',
            'jumlah' => 'required',
            'gross' => 'required',
            // 'tax' => 'required',
            // 'net' => 'required',
            'studio' => 'required'
            // 'provinsi' => 'required'
        ];

        $messages = [
            '*.required' => 'Field :attribute tidak boleh kosong !',
            '*.min' => 'Nama tidak boleh kurang dari 2 karakter !',
            '*.image' => 'Field Harus Berupa Foto !',
            '*.mimes' => 'Foto Harus Berformat JPEG/PNG/JPG'
        ];

        $this->validate($request, $rules, $messages);
        // dd($request->photo);

        $pelaporan = new Pelaporan();
        $pelaporan->kategori = $request->kategori;
        $pelaporan->provinsi = $request->provinsi;
        $pelaporan->kota = $request->kota;
        $pelaporan->nama_bioskop = $request->nama_bioskop;
        $pelaporan->nama_film = strtoupper($request->nama_film);
        $pelaporan->tgl_tayang = \Carbon\Carbon::parse($request->tgl_tayang)->format('Y-m-d');
        $pelaporan->studio = $request->studio;
        $pelaporan->jam_tayang = $request->jam_tayang;
        $pelaporan->show = $request->show;
        $pelaporan->type_tiket = $request->type_tiket;
        $pelaporan->harga = $request->harga;
        $pelaporan->jumlah = $request->jumlah;
        $pelaporan->gross = $request->gross;
        $pelaporan->tax = $request->tax;
        $pelaporan->net = $request->net;
        $pelaporan->created_by = Auth::user()->uuid;
        $pelaporan->updated_at = NULL;
        $pelaporan->save();

        toastr()->success('New Reporting Added', 'Success');
        return redirect()->route('pelaporan.index');
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $pelaporan = Pelaporan::uuid($id);
        $kota = MasterBioskop::selectRaw('Distinct kota')->pluck('kota', 'kota');
        $bioskop_kategori = KategoriBioskop::all()->pluck('name', 'uuid');
        $nama_bioskop = MasterBioskop::all()->pluck('nama_bioskop', 'uuid');
        $type_tiket = TypeTiket::all()->pluck('name', 'uuid');
        $studio = Kapasitas::all()->pluck('studio', 'uuid');
        return view('pelaporan.edit', compact('pelaporan', 'kota','bioskop_kategori', 'nama_bioskop', 'type_tiket', 'studio'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $rules = [
            'kategori' => 'required',
            'kota' => 'required',
            'nama_bioskop' => 'required',
            'nama_film' => 'required',
            'tgl_tayang' => 'required',
            // 'jam_tayang' => 'required',
            'show' => 'required',
            'type_tiket' => 'required',
            'harga' => 'required',
            'jumlah' => 'required',
            'gross' => 'required',
            // 'tax' => 'required',
            // 'net' => 'required',
            'studio' => 'required'
            // 'provinsi' => 'required'
        ];

        $messages = [
            '*.required' => 'Field :attribute tidak boleh kosong !',
            '*.min' => 'Nama tidak boleh kurang dari 2 karakter !',
            '*.image' => 'Field Harus Berupa Foto !',
            '*.mimes' => 'Foto Harus Berformat JPEG/PNG/JPG'
        ];

        $this->validate($request, $rules, $messages);
        // dd($request->photo);

        $pelaporan = Pelaporan::uuid($id);
        $pelaporan->kategori = $request->kategori;
        $pelaporan->provinsi = $request->provinsi;
        $pelaporan->kota = $request->kota;
        $pelaporan->nama_bioskop = $request->nama_bioskop;
        $pelaporan->nama_film = strtoupper($request->nama_film);
        $pelaporan->tgl_tayang = \Carbon\Carbon::parse($request->tgl_tayang)->format('Y-m-d');
        $pelaporan->studio = $request->studio;
        $pelaporan->jam_tayang = $request->jam_tayang;
        $pelaporan->show = $request->show;
        $pelaporan->type_tiket = $request->type_tiket;
        $pelaporan->harga = $request->harga;
        $pelaporan->jumlah = $request->jumlah;
        $pelaporan->gross = $request->gross;
        $pelaporan->tax = $request->tax;
        $pelaporan->net = $request->net;
        $pelaporan->edited_by = Auth::user()->uuid;
        $pelaporan->save();

        toastr()->success('Reporting Edited', 'Success');
        return redirect()->route('pelaporan.index');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $pelaporan = Pelaporan::uuid($id);
        $pelaporan->delete();

        toastr()->success('Reporting Deleted', 'Success');
        return redirect()->route('pelaporan.index');
    }

    public function getCityByCategory(Request $request){
        $kategori = $request->kategori;

        $kota  = MasterBioskop::selectRaw('distinct kota')
                    ->where('type', $kategori)
                    ->get()
                    ->pluck('kota', 'kota');

        return response()->json($kota);
    }

    public function getCityByCinema(Request $request){
        $kategori = $request->kategori;
        $bioskop = $request->bioskop;

        $kota  = MasterBioskop::selectRaw('distinct kota')
                    ->where('type', $kategori)
                    ->where('uuid', $bioskop)
                    ->get()
                    ->pluck('kota', 'kota');

        return response()->json($kota);
    }

    public function getProvinsiByCinema(Request $request){
        $kota = $request->kota;

        $data_kota  = Kota::select('nama', 'provinsi_id')
                    ->where('nama', 'like', 'Kota '.$kota.'')
                    ->get();

        if(count($data_kota) > 0){
            $provinsi = Province::selectRaw('nama')
                    ->where('uuid', $data_kota[0]->provinsi_id)
                    ->get();
        }else{
            $provinsi = '';
        }

        return response()->json($provinsi);
    }

    public function getCinemaByCategory(Request $request){
        $kategori = $request->kategori;

        $cinema = MasterBioskop::select('uuid', 'nama_bioskop')
                    ->where('type', $kategori)
                    ->get()
                    ->pluck('nama_bioskop', 'uuid');

        return response()->json($cinema);
    }

    public function getTypeByCategory(Request $request){
        $kategori = $request->kategori;

        $type = TypeTiket::select('uuid', 'name')
                    ->where('kategori', $kategori)
                    ->get()
                    ->pluck('name', 'uuid');

        return response()->json($type);
    }

    public function getStudio(Request $request){
        $kategori = $request->kategori;
        $bioskop = $request->nama_bioskop;
        $kota = $request->kota;
        $type_tiket = $request->type_tiket;

        $type = Kapasitas::select('uuid', 'studio')
                    ->where('kategori', $kategori)
                    ->where('nama_bioskop', $bioskop)
                    ->where('kota', $kota)
                    ->where('type_tiket', $type_tiket)
                    ->get()
                    ->pluck('studio', 'uuid');

        return response()->json($type);
    }
}
