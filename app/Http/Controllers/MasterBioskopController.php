<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Models\MasterBioskop;
use App\Models\KategoriBioskop;

use Auth;
use DataTables;
use URL;
use Helper;
use Image;
use Response;

class MasterBioskopController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $bioskop = MasterBioskop::all();
        if (request()->ajax()) {
            $data = MasterBioskop::get();

            return Datatables::of($data)
                ->addIndexColumn()
                ->editColumn('type', function ($row){
                    return $row->Categories->name;
                })
                ->addColumn('action', function ($row) {
                    return '
                            <a class="btn btn-success btn-sm btn-icon waves-effect waves-themed" href="' . route('masterbioskop.edit', $row->uuid) . '"><i class="fal fa-edit"></i></a>
                            <a class="btn btn-danger btn-sm btn-icon waves-effect waves-themed delete-btn" data-url="' . URL::route('masterbioskop.destroy', $row->uuid) . '" data-id="' . $row->uuid . '" data-token="' . csrf_token() . '" data-toggle="modal" data-target="#modal-delete"><i class="fal fa-trash-alt"></i></a>';
                })
                ->removeColumn('id')
                ->removeColumn('uuid')
                ->rawColumns(['action','type'])
                ->make(true);
        }

        return view('masterbioskop.index');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $bioskop_kategori = KategoriBioskop::all()->pluck('name', 'uuid');
        return view('masterbioskop.create', compact('bioskop_kategori'));
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
                'type' => 'required',
                'nama_bioskop' => 'required',
                'kota' => 'required'
            ];

            $messages = [
                '*.required' => 'Field :attribute tidak boleh kosong !',
                '*.min' => 'Nama tidak boleh kurang dari 2 karakter !',
                '*.image' => 'Field Harus Berupa Foto !',
                '*.mimes' => 'Foto Harus Berformat JPEG/PNG/JPG'
            ];

            $this->validate($request, $rules, $messages);
            // dd($request->photo);

            $bioskop = new MasterBioskop();
            $bioskop->type = $request->type;
            $bioskop->nama_bioskop = $request->nama_bioskop;
            $bioskop->kota = $request->kota;
            $bioskop->no_telephone = $request->no_telephone;
            $bioskop->pajak = $request->pajak;
            $bioskop->created_by = Auth::user()->uuid;
            $bioskop->save();

            toastr()->success('New Bioskop Name Added', 'Success');
            return redirect()->route('masterbioskop.index');
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
        $bioskop = MasterBioskop::uuid($id);
        $bioskop_kategori = KategoriBioskop::all()->pluck('name', 'uuid');

        return view('masterbioskop.edit', compact('bioskop', 'bioskop_kategori'));
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
            'type' => 'required',
            'nama_bioskop' => 'required',
            'kota' => 'required'
        ];

        $messages = [
            '*.required' => 'Field :attribute tidak boleh kosong !',
            '*.min' => 'Nama tidak boleh kurang dari 2 karakter !',
            '*.image' => 'Field Harus Berupa Foto !',
            '*.mimes' => 'Foto Harus Berformat JPEG/PNG/JPG'
        ];

        $this->validate($request, $rules, $messages);
        // dd($request->photo);

        $bioskop = MasterBioskop::uuid($id);
        $bioskop->type = $request->type;
        $bioskop->nama_bioskop = $request->nama_bioskop;
        $bioskop->kota = $request->kota;
        $bioskop->no_telephone = $request->no_telephone;
        $bioskop->pajak = $request->pajak;
        $bioskop->edited_by = Auth::user()->uuid;
        $bioskop->save();

        toastr()->success('Bioskop Name Edited', 'Success');
        return redirect()->route('masterbioskop.index');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $bioskop = MasterBioskop::uuid($id);
        $bioskop->delete();

        toastr()->success('Bioskop Name Deleted', 'Success');
        return redirect()->route('masterbioskop.index');
    }
}
