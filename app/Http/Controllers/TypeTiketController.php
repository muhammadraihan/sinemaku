<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Models\TypeTiket;
use App\Models\KategoriBioskop;

use Auth;
use DataTables;
use URL;
use Helper;
use Image;
use Response;

class TypeTiketController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $type_tiket = TypeTiket::all();
        if (request()->ajax()) {
            $data = TypeTiket::get();

            return Datatables::of($data)
                ->addIndexColumn()
                ->editColumn('kategori', function ($row){
                    return $row->Categories->name;
                })
                ->addColumn('action', function ($row) {
                    return '
                            <a class="btn btn-success btn-sm btn-icon waves-effect waves-themed" href="' . route('typetiket.edit', $row->uuid) . '"><i class="fal fa-edit"></i></a>
                            <a class="btn btn-danger btn-sm btn-icon waves-effect waves-themed delete-btn" data-url="' . URL::route('typetiket.destroy', $row->uuid) . '" data-id="' . $row->uuid . '" data-token="' . csrf_token() . '" data-toggle="modal" data-target="#modal-delete"><i class="fal fa-trash-alt"></i></a>';
                })
                ->removeColumn('id')
                ->removeColumn('uuid')
                ->rawColumns(['action','type'])
                ->make(true);
        }

        return view('typetiket.index');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $bioskop_kategori = KategoriBioskop::all()->pluck('name', 'uuid');
        return view('typetiket.create', compact('bioskop_kategori'));
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
            'name' => 'required',
            'kategori' => 'required'
        ];

        $messages = [
            '*.required' => 'Field :attribute tidak boleh kosong !',
            '*.min' => 'Nama tidak boleh kurang dari 2 karakter !',
            '*.image' => 'Field Harus Berupa Foto !',
            '*.mimes' => 'Foto Harus Berformat JPEG/PNG/JPG'
        ];

        $this->validate($request, $rules, $messages);
        // dd($request->photo);

        $type_tiket = new TypeTiket();
        $type_tiket->name = strtoupper($request->name);
        $type_tiket->kategori = $request->kategori;
        $type_tiket->save();

        toastr()->success('New Type Ticket Added', 'Success');
        return redirect()->route('typetiket.index');
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
        $type_tiket = TypeTiket::uuid($id);
        $bioskop_kategori = KategoriBioskop::all()->pluck('name', 'uuid');

        return view('typetiket.edit', compact('type_tiket', 'bioskop_kategori'));
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
            'name' => 'required',
            'kategori' => 'required'
        ];

        $messages = [
            '*.required' => 'Field :attribute tidak boleh kosong !',
            '*.min' => 'Nama tidak boleh kurang dari 2 karakter !',
            '*.image' => 'Field Harus Berupa Foto !',
            '*.mimes' => 'Foto Harus Berformat JPEG/PNG/JPG'
        ];

        $this->validate($request, $rules, $messages);
        // dd($request->photo);

        $type_tiket = TypeTiket::uuid($id);
        $type_tiket->name = strtoupper($request->name);
        $type_tiket->kategori = $request->kategori;
        $type_tiket->save();

        toastr()->success('Type Ticket Edited', 'Success');
        return redirect()->route('typetiket.index');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $type_tiket = TypeTiket::uuid($id);
        $type_tiket->delete();

        toastr()->success('Type Ticket Deleted', 'Success');
        return redirect()->route('typetiket.index');
    }
}
