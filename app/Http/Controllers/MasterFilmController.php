<?php

namespace App\Http\Controllers;

use App\Models\MasterFilm;
use App\Models\Pelaporan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use DataTables;
use URL;

class MasterFilmController extends Controller
{
    public function index()
    {
        if (request()->ajax()) {
            return DataTables::of(MasterFilm::query()->orderByDesc('tgl_tayang')->orderBy('name'))
                ->addIndexColumn()
                ->editColumn('tgl_tayang', function ($row) {
                    return optional($row->tgl_tayang)->format('d-m-Y');
                })
                ->addColumn('action', function ($row) {
                    return '
                        <a class="btn btn-success btn-sm btn-icon waves-effect waves-themed" href="' . route('masterfilm.edit', $row->uuid) . '" title="Edit"><i class="fal fa-edit"></i></a>
                        <a class="btn btn-danger btn-sm btn-icon waves-effect waves-themed delete-btn" data-url="' . URL::route('masterfilm.destroy', $row->uuid) . '" data-id="' . $row->uuid . '" data-token="' . csrf_token() . '" data-toggle="modal" data-target="#modal-delete" title="Hapus"><i class="fal fa-trash-alt"></i></a>';
                })
                ->removeColumn('id')
                ->removeColumn('uuid')
                ->rawColumns(['action'])
                ->make(true);
        }

        return view('masterfilm.index');
    }

    public function create()
    {
        return view('masterfilm.create');
    }

    public function store(Request $request)
    {
        $request->merge(['name' => MasterFilm::normalizeName($request->name)]);

        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:master_films,name',
            'tgl_tayang' => 'required|date',
        ], $this->validationMessages());

        MasterFilm::create([
            'name' => $validated['name'],
            'tgl_tayang' => $validated['tgl_tayang'],
            'created_by' => Auth::user()->uuid,
        ]);

        toastr()->success('Master Film berhasil ditambahkan.', 'Success');
        return redirect()->route('masterfilm.index');
    }

    public function edit($id)
    {
        $masterFilm = MasterFilm::uuid($id);
        return view('masterfilm.edit', compact('masterFilm'));
    }

    public function update(Request $request, $id)
    {
        $masterFilm = MasterFilm::uuid($id);
        $request->merge(['name' => MasterFilm::normalizeName($request->name)]);

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('master_films', 'name')->ignore($masterFilm->id),
            ],
            'tgl_tayang' => 'required|date',
        ], $this->validationMessages());

        DB::transaction(function () use ($masterFilm, $validated) {
            $oldName = $masterFilm->name;

            $masterFilm->name = $validated['name'];
            $masterFilm->tgl_tayang = $validated['tgl_tayang'];
            $masterFilm->edited_by = Auth::user()->uuid;
            $masterFilm->save();

            if ($oldName !== $masterFilm->name) {
                Pelaporan::where('nama_film', $oldName)
                    ->update(['nama_film' => $masterFilm->name]);
            }
        });

        toastr()->success('Master Film berhasil diperbarui.', 'Success');
        return redirect()->route('masterfilm.index');
    }

    public function destroy($id)
    {
        $masterFilm = MasterFilm::uuid($id);

        if (Pelaporan::where('nama_film', $masterFilm->name)->exists()) {
            toastr()->error(
                'Film tidak dapat dihapus karena sudah digunakan pada data pelaporan.',
                'Gagal Menghapus'
            );
            return redirect()->route('masterfilm.index');
        }

        $masterFilm->delete();
        toastr()->success('Master Film berhasil dihapus.', 'Success');
        return redirect()->route('masterfilm.index');
    }

    private function validationMessages()
    {
        return [
            'name.required' => 'Nama film wajib diisi.',
            'name.unique' => 'Nama film sudah terdaftar di Master Film.',
            'name.max' => 'Nama film maksimal 255 karakter.',
            'tgl_tayang.required' => 'Tanggal tayang wajib diisi.',
            'tgl_tayang.date' => 'Format tanggal tayang tidak valid.',
        ];
    }
}
