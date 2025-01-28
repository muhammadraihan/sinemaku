<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Models\KategoriBioskop;

use Auth;
use DataTables;
use URL;
use Helper;
use Image;
use Response;

class KategoriBioskopController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $kategori_bioskop = KategoriBioskop::all();
        if (request()->ajax()) {
            $data = KategoriBioskop::get();

            return Datatables::of($data)
                ->addIndexColumn()
                ->addColumn('action', function ($row) {
                    return '
                            <a class="btn btn-success btn-sm btn-icon waves-effect waves-themed" href="' . route('kategoribioskop.edit', $row->uuid) . '"><i class="fal fa-edit"></i></a>
                            <a class="btn btn-danger btn-sm btn-icon waves-effect waves-themed delete-btn" data-url="' . URL::route('kategoribioskop.destroy', $row->uuid) . '" data-id="' . $row->uuid . '" data-token="' . csrf_token() . '" data-toggle="modal" data-target="#modal-delete"><i class="fal fa-trash-alt"></i></a>';
                })
                ->removeColumn('id')
                ->removeColumn('uuid')
                ->rawColumns(['action','type'])
                ->make(true);
        }

        return view('kategoribioskop.index');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('kategoribioskop.create');
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
        ];

        $messages = [
            '*.required' => 'Field :attribute tidak boleh kosong !',
            '*.min' => 'Nama tidak boleh kurang dari 2 karakter !',
            '*.image' => 'Field Harus Berupa Foto !',
            '*.mimes' => 'Foto Harus Berformat JPEG/PNG/JPG'
        ];

        $this->validate($request, $rules, $messages);
        // dd($request->photo);

        $kategori_bioskop = new KategoriBioskop();
        $kategori_bioskop->name = strtoupper($request->name);
        $kategori_bioskop->save();

        toastr()->success('New Category Cinema Name Added', 'Success');
        return redirect()->route('kategoribioskop.index');
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
        $kategori_bioskop = KategoriBioskop::uuid($id);

        return view('kategoribioskop.edit', compact('kategori_bioskop'));
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
        ];

        $messages = [
            '*.required' => 'Field :attribute tidak boleh kosong !',
            '*.min' => 'Nama tidak boleh kurang dari 2 karakter !',
            '*.image' => 'Field Harus Berupa Foto !',
            '*.mimes' => 'Foto Harus Berformat JPEG/PNG/JPG'
        ];

        $this->validate($request, $rules, $messages);
        // dd($request->photo);

        $kategori_bioskop = KategoriBioskop::uuid($id);
        $kategori_bioskop->name = strtoupper($request->name);
        $kategori_bioskop->save();

        toastr()->success('Category Cinema Name Edited', 'Success');
        return redirect()->route('kategoribioskop.index');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $kategori_bioskop = KategoriBioskop::uuid($id);
        $kategori_bioskop->delete();

        toastr()->success('Category Cinema Name Deleted', 'Success');
        return redirect()->route('kategoribioskop.index');
    }
}
