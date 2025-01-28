<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Models\Pelaporan;
use App\Models\TypeTiket;
use App\Models\KategoriBioskop;
use App\Models\MasterBioskop;


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
                ->editColumn('jam_tayang', function ($row) {
                    return \Carbon\Carbon::parse($row->jam_tayang)->format('H:i'); // Format hh:mm
                })
                ->addColumn('action', function ($row) {
                    return '
                            <a class="btn btn-success btn-sm btn-icon waves-effect waves-themed" href="' . route('pelaporan.edit', $row->uuid) . '"><i class="fal fa-edit"></i></a>
                            <a class="btn btn-danger btn-sm btn-icon waves-effect waves-themed delete-btn" data-url="' . URL::route('pelaporan.destroy', $row->uuid) . '" data-id="' . $row->uuid . '" data-token="' . csrf_token() . '" data-toggle="modal" data-target="#modal-delete"><i class="fal fa-trash-alt"></i></a>';
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
        return view('pelaporan.create', compact('bioskop_kategori', 'nama_bioskop', 'kota','type_tiket'));
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
            'jam_tayang' => 'required',
            'show' => 'required',
            'type_tiket' => 'required',
            'harga' => 'required',
            'jumlah' => 'required',
            'gross' => 'required',
            // 'tax' => 'required',
            // 'net' => 'required',
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
        $pelaporan->kota = $request->kota;
        $pelaporan->nama_bioskop = $request->nama_bioskop;
        $pelaporan->nama_film = $request->nama_film;
        $pelaporan->tgl_tayang = $request->tgl_tayang;
        $pelaporan->jam_tayang = $request->jam_tayang;
        $pelaporan->show = $request->show;
        $pelaporan->type_tiket = $request->type_tiket;
        $pelaporan->harga = $request->harga;
        $pelaporan->jumlah = $request->jumlah;
        $pelaporan->gross = $request->gross;
        $pelaporan->tax = $request->tax;
        $pelaporan->net = $request->net;
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
        return view('pelaporan.edit', compact('pelaporan', 'kota','bioskop_kategori', 'nama_bioskop', 'type_tiket'));
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
            'jam_tayang' => 'required',
            'show' => 'required',
            'type_tiket' => 'required',
            'harga' => 'required',
            'jumlah' => 'required',
            'gross' => 'required',
            // 'tax' => 'required',
            // 'net' => 'required',
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
        $pelaporan->kota = $request->kota;
        $pelaporan->nama_bioskop = $request->nama_bioskop;
        $pelaporan->nama_film = $request->nama_film;
        $pelaporan->tgl_tayang = $request->tgl_tayang;
        $pelaporan->jam_tayang = $request->jam_tayang;
        $pelaporan->show = $request->show;
        $pelaporan->type_tiket = $request->type_tiket;
        $pelaporan->harga = $request->harga;
        $pelaporan->jumlah = $request->jumlah;
        $pelaporan->gross = $request->gross;
        $pelaporan->tax = $request->tax;
        $pelaporan->net = $request->net;
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

    public function getCinemaByCategory(Request $request){
        $kategori = $request->kategori;
        $kota = $request->kota;

        $cinema = MasterBioskop::select('uuid', 'nama_bioskop')
                    ->where('type', $kategori)
                    ->where('kota', $kota)
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
}
