<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Models\Kapasitas;
use App\Models\TypeTiket;
use App\Models\KategoriBioskop;
use App\Models\MasterBioskop;

use Auth;
use DataTables;
use URL;
use Helper;
use Image;
use Response;

class KapasitasController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $kapasitas = Kapasitas::all();
        if (request()->ajax()) {
            $data = Kapasitas::get();

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
                ->addColumn('action', function ($row) {
                    return '
                            <a class="btn btn-success btn-sm btn-icon waves-effect waves-themed" href="' . route('kapasitas.edit', $row->uuid) . '"><i class="fal fa-edit"></i></a>
                            <a class="btn btn-danger btn-sm btn-icon waves-effect waves-themed delete-btn" data-url="' . URL::route('kapasitas.destroy', $row->uuid) . '" data-id="' . $row->uuid . '" data-token="' . csrf_token() . '" data-toggle="modal" data-target="#modal-delete"><i class="fal fa-trash-alt"></i></a>';
                })
                ->removeColumn('id')
                ->removeColumn('uuid')
                ->rawColumns(['action','type'])
                ->make(true);
        }

        return view('kapasitas.index');
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
        return view('kapasitas.create', compact('bioskop_kategori', 'nama_bioskop', 'kota','type_tiket'));
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
            'type_tiket' => 'required',
            'studio' => 'required',
            'kapasitas' => 'required|numeric'
        ];

        $messages = [
            '*.required' => 'Field :attribute tidak boleh kosong !',
            '*.min' => 'Nama tidak boleh kurang dari 2 karakter !',
            '*.image' => 'Field Harus Berupa Foto !',
            '*.mimes' => 'Foto Harus Berformat JPEG/PNG/JPG',
            '*.numeric' => 'Field :attribute harus berisi angka !'
        ];

        $this->validate($request, $rules, $messages);
        // dd($request->photo);

        $kapasitas = new Kapasitas();
        $kapasitas->kategori = $request->kategori;
        $kapasitas->kota = $request->kota;
        $kapasitas->nama_bioskop = $request->nama_bioskop;
        $kapasitas->type_tiket = $request->type_tiket;
        $kapasitas->studio = $request->studio;
        $kapasitas->kapasitas = $request->kapasitas;
        $kapasitas->save();

        toastr()->success('New Kapasitas Added', 'Success');
        return redirect()->route('kapasitas.index');
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
        $kapasitas = Kapasitas::uuid($id);
        $kota = MasterBioskop::selectRaw('Distinct kota')->pluck('kota', 'kota');
        $bioskop_kategori = KategoriBioskop::all()->pluck('name', 'uuid');
        $nama_bioskop = MasterBioskop::all()->pluck('nama_bioskop', 'uuid');
        $type_tiket = TypeTiket::all()->pluck('name', 'uuid');
        return view('kapasitas.edit', compact('kapasitas', 'kota','bioskop_kategori', 'nama_bioskop', 'type_tiket'));
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
            'type_tiket' => 'required',
            'studio' => 'required',
            'kapasitas' => 'required|numeric'
        ];

        $messages = [
            '*.required' => 'Field :attribute tidak boleh kosong !',
            '*.min' => 'Nama tidak boleh kurang dari 2 karakter !',
            '*.image' => 'Field Harus Berupa Foto !',
            '*.mimes' => 'Foto Harus Berformat JPEG/PNG/JPG',
            '*.numeric' => 'Field :attribute harus berisi angka !'
        ];

        $this->validate($request, $rules, $messages);
        // dd($request->photo);

        $kapasitas = Kapasitas::uuid($id);
        $kapasitas->kategori = $request->kategori;
        $kapasitas->kota = $request->kota;
        $kapasitas->nama_bioskop = $request->nama_bioskop;
        $kapasitas->type_tiket = $request->type_tiket;
        $kapasitas->studio = $request->studio;
        $kapasitas->kapasitas = $request->kapasitas;
        $kapasitas->save();

        toastr()->success('Kapasitas Edited', 'Success');
        return redirect()->route('kapasitas.index');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $kapasitas = Kapasitas::uuid($id);
        $kapasitas->delete();

        toastr()->success('Kapasitas Deleted', 'Success');
        return redirect()->route('kapasitas.index');
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
}
