<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Models\Vendor;

use Auth;
use DataTables;
use URL;
use Helper;
use Image;
use Response;

class VendorController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $vendor = Vendor::all();
        if (request()->ajax()) {
            $data = Vendor::get();

            return Datatables::of($data)
                ->addIndexColumn()
                ->addColumn('action', function ($row) {
                    return '
                            <a class="btn btn-success btn-sm btn-icon waves-effect waves-themed" href="' . route('vendor.edit', $row->uuid) . '"><i class="fal fa-edit"></i></a>
                            <a class="btn btn-danger btn-sm btn-icon waves-effect waves-themed delete-btn" data-url="' . URL::route('vendor.destroy', $row->uuid) . '" data-id="' . $row->uuid . '" data-token="' . csrf_token() . '" data-toggle="modal" data-target="#modal-delete"><i class="fal fa-trash-alt"></i></a>';
                })
                ->removeColumn('id')
                ->removeColumn('uuid')
                ->rawColumns(['action','type'])
                ->make(true);
        }

        return view('vendor.index');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('vendor.create');
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
            'nama_perusahaan' => 'required',
            'email' => 'required|email',
        ];

        $messages = [
            '*.required' => 'Field :attribute tidak boleh kosong !',
            '*.email' => 'Field :attribute harus berupa email yang valid !',
            '*.min' => 'Nama tidak boleh kurang dari 2 karakter !',
            '*.image' => 'Field Harus Berupa Foto !',
            '*.mimes' => 'Foto Harus Berformat JPEG/PNG/JPG'
        ];

        $this->validate($request, $rules, $messages);
        // dd($request->photo);

        $vendor = new Vendor();
        $vendor->name = strtoupper($request->name);
        $vendor->nama_perusahaan = strtoupper($request->nama_perusahaan);
        $vendor->email = $request->email;
        $vendor->alamat = $request->alamat;
        $vendor->no_handphone = $request->no_handphone;
        $vendor->pic = strtoupper($request->pic);
        $vendor->save();

        toastr()->success('Vendor baru telah ditambahkan', 'Success');
        return redirect()->route('vendor.index');
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
        $vendor = Vendor::uuid($id);
        return view('vendor.edit', compact('vendor'));
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
            'nama_perusahaan' => 'required',
            'email' => 'required|email',
        ];

        $messages = [
            '*.required' => 'Field :attribute tidak boleh kosong !',
            '*.email' => 'Field :attribute harus berupa email yang valid !',
            '*.min' => 'Nama tidak boleh kurang dari 2 karakter !',
            '*.image' => 'Field Harus Berupa Foto !',
            '*.mimes' => 'Foto Harus Berformat JPEG/PNG/JPG'
        ];

        $this->validate($request, $rules, $messages);
        // dd($request->photo);

        $vendor = Vendor::uuid($id);
        $vendor->name = strtoupper($request->name);
        $vendor->nama_perusahaan = strtoupper($request->nama_perusahaan);
        $vendor->email = $request->email;
        $vendor->alamat = $request->alamat;
        $vendor->no_handphone = $request->no_handphone;
        $vendor->pic = strtoupper($request->pic);
        $vendor->save();

        toastr()->success('Vendor telah di edit', 'Success');
        return redirect()->route('vendor.index');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $vendor = Vendor::uuid($id);
        $vendor->delete();

        toastr()->success('Vendor telah di hapus', 'Success');
        return redirect()->route('vendor.index');
    }
}
