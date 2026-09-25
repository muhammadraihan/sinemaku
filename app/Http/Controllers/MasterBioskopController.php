<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Models\MasterBioskop;
use App\Models\KategoriBioskop;
use App\Models\TypeTiket;
use App\Models\Kapasitas;

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
                ->editColumn('nama_bioskop', function ($row) {
                    return mb_strtoupper($row->nama_bioskop ?? '', 'UTF-8');
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
        $request->validate([
            'type' => 'required|exists:kategori_bioskops,uuid',
            'nama_bioskop' => 'required|string|max:255',
            'kota' => 'required|string|max:255',
            'pajak' => 'nullable|numeric|min:0|max:100',
            'no_telephone' => 'nullable|string|max:50',
            'ticket_types' => 'nullable|array',
            'ticket_types.*.name' => 'nullable|string|max:255',
            'capacities' => 'nullable|array',
            'capacities.*.ticket_type_index' => 'required_with:capacities|integer|min:0',
            'capacities.*.studio' => 'required_with:capacities|string|max:50',
            'capacities.*.kapasitas' => 'required_with:capacities|numeric|min:0',
        ], [
            '*.required' => 'Field :attribute tidak boleh kosong.',
            '*.numeric' => 'Field :attribute harus berisi angka.',
        ]);

        $ticketTypes = collect($request->input('ticket_types', []))
            ->map(fn ($item) => ['name' => mb_strtoupper(trim((string) ($item['name'] ?? '')), 'UTF-8')])
            ->filter(fn ($item) => $item['name'] !== '')
            ->values();
        $capacities = collect($request->input('capacities', []))->values();
        foreach ($capacities as $index => $capacity) {
            if (!$ticketTypes->has((int) $capacity['ticket_type_index'])) {
                return back()->withInput()->withErrors(["capacities.$index.ticket_type_index" => 'Pilih tipe tiket yang tersedia pada tab Tipe Tiket.']);
            }
        }

        DB::transaction(function () use ($request, $ticketTypes, $capacities) {
            $bioskop = new MasterBioskop();
            $bioskop->type = $request->type;
            $bioskop->nama_bioskop = mb_strtoupper(trim($request->nama_bioskop), 'UTF-8');
            $bioskop->kota = $request->kota;
            $bioskop->no_telephone = $request->no_telephone;
            $bioskop->pajak = $request->pajak;
            $bioskop->created_by = Auth::user()->uuid;
            $bioskop->save();

            $createdTickets = $ticketTypes->map(function ($ticket) use ($bioskop) {
                $model = new TypeTiket();
                $model->name = $ticket['name'];
                $model->kategori = $bioskop->type;
                $model->save();
                return $model;
            });

            foreach ($capacities as $capacity) {
                $ticket = $createdTickets->get((int) $capacity['ticket_type_index']);
                $model = new Kapasitas();
                $model->kategori = $bioskop->type;
                $model->kota = $bioskop->kota;
                $model->nama_bioskop = $bioskop->uuid;
                $model->type_tiket = $ticket->uuid;
                $model->studio = trim((string) $capacity['studio']);
                $model->kapasitas = $capacity['kapasitas'];
                $model->save();
            }
        });

        toastr()->success('Bioskop, tipe tiket, dan kapasitas berhasil disimpan.', 'Berhasil');
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
