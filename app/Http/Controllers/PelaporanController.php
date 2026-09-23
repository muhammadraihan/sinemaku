<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Models\Pelaporan;
use App\Models\TypeTiket;
use App\Models\KategoriBioskop;
use App\Models\MasterBioskop;
use App\Models\MasterFilm;
use App\Models\Kapasitas;
use App\Models\Kota;
use App\Models\Province;
use Auth;
use DataTables;
use URL;
use Helper;
use Image;
use Response;
use Uuid;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use App\Services\Reports\CinepolisPdfParser;

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
            // $data = Pelaporan::get();
            $data = Pelaporan::limit(1000)
                ->orderByDesc('created_at')
                 ->get();

            return Datatables::of($data)
                ->addIndexColumn()
                ->editColumn('kategori', function ($row){
                    return $row->Categories->name ?? null;
                })
                ->editColumn('nama_bioskop', function ($row){
                    return $row->Cinemas->nama_bioskop ?? null;
                })
                ->editColumn('type_tiket', function ($row){
                    return $row->TypeTiket->name ?? null;
                })
                ->editColumn('studio', function ($row){
                    return $row->Studio->studio ?? null;
                })
                ->editColumn('jam_tayang', function ($row) {
                    return \Carbon\Carbon::parse($row->jam_tayang)->format('H:i'); // Format hh:mm
                })
                ->editColumn('created_by', function($row){
                    return $row->userCreate->name ?? null;
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
                            <a class="btn btn-success btn-sm btn-icon waves-effect waves-themed" href="' . route('pelaporan.edit', $row->uuid) . '"><i class="fal fa-edit"></i></a>
                            <a class="btn btn-danger btn-sm btn-icon waves-effect waves-themed delete-btn" data-url="' . URL::route('pelaporan.destroy', $row->uuid) . '" data-id="' . $row->uuid . '" data-token="' . csrf_token() . '" data-toggle="modal" data-target="#modal-delete"><i class="fal fa-trash-alt"></i></a>';
                    }
                })
                ->removeColumn('id')
                ->removeColumn('uuid')
                ->rawColumns(['action','type'])
                ->make(true);
        }

        $bioskop_kategori = KategoriBioskop::all()->pluck('name', 'uuid');
        $nama_bioskop = MasterBioskop::all()->pluck('nama_bioskop', 'uuid');
        $kota = MasterBioskop::selectRaw('Distinct kota')->pluck('kota', 'kota');
        $type_tiket = TypeTiket::all()->pluck('name', 'uuid');
        $nama_film = MasterFilm::options();

        return view('pelaporan.index',compact('bioskop_kategori', 'nama_bioskop', 'kota','type_tiket', 'nama_film'));
    }

    /**
     * Return the Master Film screening date, its filter start date, and the
     * latest reporting date for the selected film as the filter end date.
     */
    public function getFilmStartDate(Request $request)
    {
        $request->validate([
            'nama_film' => 'required|string|max:255',
        ]);

        $filmName = $request->nama_film;

        $firstScreeningDate = MasterFilm::where('name', $filmName)
            ->value('tgl_tayang');

        $latestReportDate = Pelaporan::query()
            ->where('nama_film', $filmName)
            ->max('tgl_tayang');

        if (!$firstScreeningDate) {
            return response()->json([
                'tgl_tayang' => null,
                'start_date' => null,
                'end_date' => $latestReportDate
                    ? Carbon::parse($latestReportDate)->toDateString()
                    : null,
            ]);
        }

        $screeningDate = Carbon::parse($firstScreeningDate);

        return response()->json([
            'tgl_tayang' => $screeningDate->toDateString(),
            'start_date' => $screeningDate
                ->copy()
                ->subMonthNoOverflow()
                ->toDateString(),
            'end_date' => $latestReportDate
                ? Carbon::parse($latestReportDate)->toDateString()
                : null,
        ]);
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
        $nama_film = MasterFilm::options();
        return view('pelaporan.create', compact('bioskop_kategori', 'nama_bioskop', 'kota','type_tiket', 'studio', 'nama_film'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        // Validasi
        $rules = [
            'kategori' => 'required',
            'kota' => 'required',
            'nama_bioskop' => 'required',
            'nama_film' => 'required|exists:master_films,name',
            'tgl_tayang' => 'required',
            // 'jam_tayang' => 'required',
            'show.*' => 'required',
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
            '*.required' => 'Field :attribute tidak boleh kosong!',
            'nama_film.exists' => 'Nama film tidak terdaftar di Master Film. Silakan pilih film yang tersedia.',
            '*.numeric' => 'Field :attribute harus berupa angka!',
            '*.integer' => 'Field :attribute harus berupa bilangan bulat!',
        ];

        $this->validate($request, $rules, $messages);

        // Looping untuk menyimpan multiple data
        $data = [];
        foreach ($request->show as $index => $show) {
            $data[] = [
                'uuid'         => Uuid::generate(),
                'kategori'     => $request->kategori,
                'provinsi'     => $request->provinsi,
                'kota'         => $request->kota,
                'nama_bioskop' => $request->nama_bioskop,
                'nama_film'    => strtoupper($request->nama_film),
                'tgl_tayang'   => \Carbon\Carbon::parse($request->tgl_tayang)->format('Y-m-d'),
                'studio'       => $request->studio,
                'show'         => $show,
                'jam_tayang'   => $request->jam_tayang[$index],
                'type_tiket'   => $request->type_tiket,
                'harga'        => str_replace(',', '', $request->harga[$index]),
                'jumlah'       => $request->jumlah[$index],
                'gross'        => str_replace(',', '', $request->gross[$index]),
                'tax'          => isset($request->tax[$index]) ? str_replace(',', '', $request->tax[$index]) : 0,
                'net'          => isset($request->net[$index]) ? str_replace(',', '', $request->net[$index]) : 0,
                'created_by'   => Auth::user()->uuid,
                'created_at'   => now(),
                'updated_at'   => null
            ];
        }

        // Simpan data ke database dalam satu query (lebih cepat)
        Pelaporan::insert($data);

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
        $nama_film = MasterFilm::options();
        return view('pelaporan.edit', compact('pelaporan', 'kota','bioskop_kategori', 'nama_bioskop', 'type_tiket', 'studio', 'nama_film'));
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
            'nama_film' => 'required|exists:master_films,name',
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
            'nama_film.exists' => 'Nama film tidak terdaftar di Master Film. Silakan pilih film yang tersedia.',
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
        $pelaporan->harga = str_replace(',', '', $request->harga);
        $pelaporan->jumlah = $request->jumlah;
        $pelaporan->gross = str_replace(',', '', $request->gross);
        $pelaporan->tax = isset($request->tax) ? str_replace(',', '', $request->tax) : 0;
        $pelaporan->net = isset($request->net) ? str_replace(',', '', $request->net) : 0;
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

    public function getTaxByCinema(Request $request){
        $kategori = $request->kategori;
        $bioskop = $request->bioskop;

        $kota  = MasterBioskop::select('pajak')
                    ->where('type', $kategori)
                    ->where('uuid', $bioskop)
                    ->first();

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

    public function previewCinepolisPdf(Request $request, CinepolisPdfParser $parser)
    {
        $request->validate([
            'file' => 'required|file|mimes:pdf|max:20480',
        ], [
            'file.required' => 'File PDF wajib diunggah.',
            'file.mimes' => 'Format file harus PDF.',
            'file.max' => 'Ukuran file maksimal 20MB.',
        ]);

        try {
            $parsed = $parser->parse($request->file('file')->getPathname());
            $mapping = $this->mapCinepolisPreview($parsed);
            $token = (string) Str::uuid();
            Cache::put('cinepolis_pdf_preview:' . $token, [
                'parsed' => $parsed,
                'mapping' => $mapping,
                'created_by' => Auth::user()->uuid ?? null,
            ], now()->addMinutes(30));

            return response()->json([
                'status' => 'success',
                'token' => $token,
                'preview' => $mapping['preview'],
                'summary' => $mapping['summary'],
                'blocking_issues' => $mapping['blocking_issues'],
                'warnings' => $mapping['warnings'],
                'cinema_mapping' => $mapping['cinema_mapping'],
                'row_mappings' => $mapping['row_mappings'],
                'quick_master_context' => $mapping['quick_master_context'],
            ]);
        } catch (\Throwable $e) {
            report($e);
            return response()->json([
                'status' => 'failed',
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function quickMasterCinepolis(Request $request)
    {
        $request->validate([
            'token' => 'required|string',
            'resource' => 'required|in:cinema,film,ticket_type,capacity',
        ]);
        $cacheKey = 'cinepolis_pdf_preview:' . $request->input('token');
        $cached = Cache::get($cacheKey);
        if (!$cached) {
            return response()->json(['status' => 'failed', 'message' => 'Preview sudah kedaluwarsa. Silakan upload ulang PDF.'], 422);
        }
        if (($cached['created_by'] ?? null) !== (Auth::user()->uuid ?? null)) {
            return response()->json(['status' => 'failed', 'message' => 'Preview ini bukan milik sesi pengguna aktif.'], 403);
        }

        $parsed = $cached['parsed'];
        $mapping = $cached['mapping'];
        $resource = $request->input('resource');
        $category = KategoriBioskop::whereRaw('UPPER(name) = ?', ['CINEPOLIS'])->first();
        if (!$category) {
            return response()->json(['status' => 'failed', 'message' => 'Kategori CINEPOLIS belum tersedia.'], 422);
        }

        if ($resource === 'cinema') {
            $request->validate(['name' => 'required|string|max:255', 'city' => 'required|string|max:255']);
            if ($this->normalizeCinepolisCinemaName($parsed['cinema_name']) !== $this->normalizeCinepolisCinemaName($request->input('name'))) {
                return response()->json(['status' => 'failed', 'message' => 'Nama bioskop harus berasal dari PDF preview.'], 422);
            }
            $cinema = new MasterBioskop();
            $cinema->type = $category->uuid;
            $cinema->nama_bioskop = $request->input('name');
            $cinema->kota = $request->input('city');
            $cinema->created_by = Auth::user()->uuid;
            $cinema->save();
        } elseif ($resource === 'film') {
            $request->validate(['name' => 'required|string|max:255']);
            if (!$this->cinepolisPreviewContains($parsed, 'film', $request->input('name'))) {
                return response()->json(['status' => 'failed', 'message' => 'Nama film harus berasal dari PDF preview.'], 422);
            }
            $film = new MasterFilm();
            $film->name = MasterFilm::normalizeName($request->input('name'));
            $film->created_by = Auth::user()->uuid;
            $film->save();
        } elseif ($resource === 'ticket_type') {
            $request->validate(['name' => 'required|string|max:255']);
            $ticketName = $this->normalizeCinepolisTicketName($request->input('name'));
            $allowed = collect($parsed['rows'])->pluck('type_tiket')->map(fn ($name) => $this->normalizeCinepolisTicketName($name));
            if (!$allowed->contains($ticketName)) {
                return response()->json(['status' => 'failed', 'message' => 'Tipe tiket harus berasal dari PDF preview.'], 422);
            }
            $ticket = new TypeTiket();
            $ticket->name = $ticketName;
            $ticket->kategori = $category->uuid;
            $ticket->save();
        } else {
            $request->validate([
                'cinema_uuid' => 'required|string',
                'ticket_uuid' => 'required|string',
                'studio' => 'required|string|max:30',
                'kapasitas' => 'required|numeric|min:0',
            ]);
            $cinema = MasterBioskop::where('uuid', $request->input('cinema_uuid'))->where('type', $category->uuid)->first();
            $ticket = TypeTiket::where('uuid', $request->input('ticket_uuid'))->where('kategori', $category->uuid)->first();
            $studio = $this->normalizeStudioNumber((string) $request->input('studio'));
            $allowedStudios = collect($parsed['rows'])->pluck('studio')->map(fn ($value) => $this->normalizeStudioNumber((string) $value));
            if (!$cinema || !$ticket || !$allowedStudios->contains($studio)) {
                return response()->json(['status' => 'failed', 'message' => 'Bioskop, tipe tiket, atau studio tidak sesuai dengan PDF preview.'], 422);
            }
            $capacity = new Kapasitas();
            $capacity->kategori = $category->uuid;
            $capacity->kota = $cinema->kota;
            $capacity->nama_bioskop = $cinema->uuid;
            $capacity->type_tiket = $ticket->uuid;
            $capacity->studio = $studio;
            $capacity->kapasitas = $request->input('kapasitas');
            $capacity->save();
        }

        $freshMapping = $this->mapCinepolisPreview($parsed, $request->input('cinema_uuid'));
        $cached['mapping'] = $freshMapping;
        Cache::put($cacheKey, $cached, now()->addMinutes(15));
        return response()->json(array_merge([
            'status' => 'success',
            'message' => 'Master berhasil ditambahkan.',
            'token' => $request->input('token'),
        ], $freshMapping, [
            'row_mappings' => $freshMapping['row_mappings'],
            'quick_master_context' => $freshMapping['quick_master_context'],
        ]));
    }

    private function normalizeStudioNumber(string $value): string
    {
        $digits = preg_replace('/[^0-9]/', '', $value);
        return $digits === '' ? '' : (string) ((int) $digits);
    }

    private function cinepolisPreviewContains(array $parsed, string $type, string $value): bool
    {
        if ($type === 'film') {
            return $this->normalizeCinepolisCinemaName($parsed['film_name']) === $this->normalizeCinepolisCinemaName($value);
        }
        return false;
    }

    private function normalizeCinepolisTicketName(string $value): string
    {
        return mb_strtoupper(trim(preg_replace('/\\s+/', ' ', $value)), 'UTF-8');
    }

    public function confirmCinepolisPdf(Request $request)
    {
        $request->validate(['token' => 'required|string']);
        $cacheKey = 'cinepolis_pdf_preview:' . $request->input('token');
        $cached = Cache::get($cacheKey);
        if (!$cached) {
            return response()->json(['status' => 'failed', 'message' => 'Preview sudah kedaluwarsa. Silakan upload ulang PDF.'], 422);
        }
        if (($cached['created_by'] ?? null) !== (Auth::user()->uuid ?? null)) {
            return response()->json(['status' => 'failed', 'message' => 'Preview ini bukan milik sesi pengguna aktif.'], 403);
        }
        $mapping = $cached['mapping'];
        if (!empty($mapping['cinema_mapping']['ambiguous'])) {
            $selectedCinemaUuid = (string) $request->input('cinema_uuid');
            $allowedCinemaUuids = array_column($mapping['cinema_mapping']['candidates'], 'uuid');
            if (!$selectedCinemaUuid || !in_array($selectedCinemaUuid, $allowedCinemaUuids, true)) {
                return response()->json(['status' => 'failed', 'message' => 'Pilih salah satu Master Bioskop yang sesuai sebelum import.'], 422);
            }
            $mapping = $this->mapCinepolisPreview($cached['parsed'], $selectedCinemaUuid);
        }
        if (!empty($mapping['cinema_mapping']['requires_confirmation']) && !$request->boolean('confirm_cinema_mapping')) {
            return response()->json(['status' => 'failed', 'message' => 'Konfirmasi nama bioskop diperlukan sebelum import.'], 422);
        }
        if (!empty($mapping['blocking_issues'])) {
            return response()->json(['status' => 'failed', 'message' => 'Import diblokir karena mapping belum lengkap.', 'issues' => $mapping['blocking_issues']], 422);
        }

        $parsed = $cached['parsed'];
        $rows = [];
        $duplicateRows = [];
        foreach ($parsed['rows'] as $row) {
            $resolved = $mapping['row_mappings'][$this->cinepolisRowKey($row)];
            $duplicate = Pelaporan::where('kategori', $mapping['category_uuid'])
                ->where('nama_bioskop', $mapping['cinema_uuid'])
                ->where('nama_film', $mapping['film_name'])
                ->whereDate('tgl_tayang', $row['tanggal'])
                ->where('jam_tayang', $row['jam_tayang'])
                ->where('show', $row['show'])
                ->where('type_tiket', $resolved['ticket_uuid'])
                ->where('harga', $row['harga'])
                ->where('jumlah', $row['jumlah'])
                ->where('gross', $row['gross'])
                ->where('net', $row['net'])
                ->exists();
            if ($duplicate) {
                $duplicateRows[] = $row['type_tiket'] . ' ' . $row['jam_tayang'] . ' show ' . $row['show'];
            }
            $rows[] = [
                'uuid' => Uuid::generate(),
                'kategori' => $mapping['category_uuid'],
                'provinsi' => $mapping['province'],
                'kota' => $mapping['city'],
                'nama_bioskop' => $mapping['cinema_uuid'],
                'nama_film' => $mapping['film_name'],
                'tgl_tayang' => $row['tanggal'],
                'jam_tayang' => $row['jam_tayang'],
                'show' => $row['show'],
                'type_tiket' => $resolved['ticket_uuid'],
                'harga' => $row['harga'],
                'jumlah' => $row['jumlah'],
                'gross' => $row['gross'],
                'tax' => $row['tax_rate'],
                'net' => $row['net'],
                'studio' => $resolved['studio_uuid'],
                'created_by' => Auth::user()->uuid ?? $cached['created_by'],
                'created_at' => now(),
            ];
        }

        if ($duplicateRows) {
            return response()->json([
                'status' => 'failed',
                'message' => 'Import diblokir karena terdapat data yang sudah pernah diimport.',
                'issues' => $duplicateRows,
            ], 422);
        }

        DB::transaction(function () use ($rows) {
            Pelaporan::insert($rows);
        });
        Cache::forget($cacheKey);

        return response()->json(['status' => 'success', 'message' => count($rows) . ' baris Cinepolis berhasil diimport.', 'inserted' => count($rows)]);
    }

    private function mapCinepolisPreview(array $parsed, ?string $selectedCinemaUuid = null): array
    {
        $category = KategoriBioskop::whereRaw('UPPER(name) = ?', ['CINEPOLIS'])->first();
        $cinemaMatch = $category ? $this->resolveCinepolisCinema($category->uuid, $parsed['cinema_name'], $selectedCinemaUuid) : [
            'cinema' => null,
            'candidates' => [],
            'requires_confirmation' => false,
            'ambiguous' => false,
        ];
        $cinema = $cinemaMatch['cinema'];
        $film = MasterFilm::whereRaw('UPPER(TRIM(name)) = ?', [$parsed['film_name']])->first();
        $city = $cinema ? $cinema->kota : null;
        $cityRecord = $city ? Kota::where(function ($query) use ($city) {
            $query->where('nama', $city)->orWhere('nama', 'Kota ' . $city);
        })->first() : null;
        $province = $cityRecord ? optional(Province::where('uuid', $cityRecord->provinsi_id)->first())->nama : null;
        $blocking = [];
        $warnings = [];
        if (!$category) $blocking[] = 'Kategori CINEPOLIS belum tersedia di Master Kategori Bioskop.';
        if (!$cinema && !$cinemaMatch['ambiguous']) {
            $blocking[] = 'Bioskop ' . $parsed['cinema_name'] . ' belum terdaftar sebagai bioskop kategori CINEPOLIS.';
        }
        if ($cinemaMatch['requires_confirmation']) {
            $warnings[] = 'Konfirmasi mapping nama bioskop: laporan “' . $parsed['cinema_name'] . '” akan dipetakan ke master “' . $cinema->nama_bioskop . '”.';
        }
        if (!$film) $blocking[] = 'Film ' . $parsed['film_name'] . ' belum terdaftar di Master Film.';
        if (!$city && !$cinemaMatch['ambiguous']) $blocking[] = 'Kota bioskop belum tersedia di Master Bioskop.';
        if (!$province && !$cinemaMatch['ambiguous']) $warnings[] = 'Provinsi belum dapat dipetakan dari master kota.';

        $rowMappings = [];
        foreach ($parsed['rows'] as $row) {
            $ticket = $category ? TypeTiket::where('kategori', $category->uuid)->whereRaw('UPPER(TRIM(name)) = ?', [$row['type_tiket']])->first() : null;
            $capacity = null;
            if ($ticket && $cinema) {
                $capacity = Kapasitas::where('kategori', $category->uuid)
                    ->where('nama_bioskop', $cinema->uuid)
                    ->where('type_tiket', $ticket->uuid)
                    ->get()
                    ->first(function ($item) use ($row) {
                        $masterDigits = preg_replace('/[^0-9]/', '', (string) $item->studio);
                        $reportDigits = preg_replace('/[^0-9]/', '', (string) ($row['studio'] ?? ''));
                        if ($masterDigits === '' || $reportDigits === '') {
                            return false;
                        }
                        return (int) $masterDigits === (int) $reportDigits;
                    });
            }
            if (!$ticket) $blocking[] = 'Tipe tiket ' . $row['type_tiket'] . ' belum tersedia untuk kategori CINEPOLIS.';
            if (!$capacity && !$cinemaMatch['ambiguous']) $blocking[] = 'Studio CINEMA ' . ($row['studio'] ?? '-') . ' belum memiliki mapping kapasitas untuk tipe tiket ' . $row['type_tiket'] . '.';
            $key = $this->cinepolisRowKey($row);
            $rowMappings[$key] = [
                'ticket_uuid' => optional($ticket)->uuid,
                'studio_uuid' => optional($capacity)->uuid,
                'status' => ($cinema && $ticket && $capacity) ? 'Siap' : 'Diblokir',
            ];
        }
        $blocking = array_values(array_unique($blocking));
        return [
            'category_uuid' => optional($category)->uuid,
            'cinema_uuid' => optional($cinema)->uuid,
            'film_name' => optional($film)->name ?: $parsed['film_name'],
            'city' => $city,
            'province' => $province,
            'cinema_mapping' => [
                'report_name' => $parsed['cinema_name'],
                'master_name' => optional($cinema)->nama_bioskop,
                'requires_confirmation' => $cinemaMatch['requires_confirmation'],
                'ambiguous' => $cinemaMatch['ambiguous'],
                'candidates' => $cinemaMatch['candidates'],
            ],
            'row_mappings' => $rowMappings,
            'quick_master_context' => [
                'cinema_name' => $parsed['cinema_name'],
                'film_name' => $parsed['film_name'],
                'category_uuid' => optional($category)->uuid,
                'cinema_uuid' => optional($cinema)->uuid,
                'city' => $city,
                'ticket_types' => array_values(array_unique(array_column($parsed['rows'], 'type_tiket'))),
                'studios' => array_values(array_unique(array_column($parsed['rows'], 'studio'))),
            ],
            'blocking_issues' => $blocking,
            'warnings' => array_values(array_unique($warnings)),
            'preview' => array_map(function ($row) use ($parsed, $city, $rowMappings) {
                $key = $this->cinepolisRowKey($row);
                return array_merge($row, [
                    'kategori' => 'CINEPOLIS',
                    'bioskop' => $parsed['cinema_name'],
                    'kota' => $city,
                    'mapping_status' => $rowMappings[$key]['status'] ?? 'Diblokir',
                ]);
            }, $parsed['rows']),
            'summary' => [
                'cinema' => $parsed['cinema_name'],
                'category' => 'CINEPOLIS',
                'film' => $parsed['film_name'],
                'studio' => $parsed['studio'],
                'date' => $parsed['report_date'],
                'admits' => $parsed['totals']['admits'],
                'gross' => $parsed['totals']['gross'],
                'tax_amount' => $parsed['totals']['tax_amount'],
                'net' => $parsed['totals']['net'],
                'source_admits' => $parsed['source_totals']['admits'],
                'source_gross' => $parsed['source_totals']['gross'],
                'source_tax_amount' => $parsed['source_totals']['tax_amount'],
                'source_net' => $parsed['source_totals']['net'],
            ],
        ];
    }

    private function cinepolisRowKey(array $row): string
    {
        return implode('|', [
            $row['studio'] ?? '',
            $row['type_tiket'],
            $row['jam_tayang'],
            $row['show'],
            $row['harga'],
        ]);
    }

    private function resolveCinepolisCinema(string $categoryUuid, string $reportCinemaName, ?string $selectedCinemaUuid = null): array
    {
        $reportDisplayName = $this->normalizeCinepolisCinemaDisplayName($reportCinemaName);
        $reportNormalized = $this->normalizeCinepolisCinemaName($reportCinemaName);
        $cinemas = MasterBioskop::where('type', $categoryUuid)->get();
        $candidateRows = function ($items) {
            return $items->map(function ($cinema) {
                return [
                    'uuid' => $cinema->uuid,
                    'name' => $cinema->nama_bioskop,
                    'city' => $cinema->kota,
                ];
            })->values()->all();
        };
        $exact = $cinemas->filter(function ($cinema) use ($reportDisplayName) {
            return $this->normalizeCinepolisCinemaDisplayName($cinema->nama_bioskop) === $reportDisplayName;
        })->values();
        if ($exact->count() === 1) {
            return ['cinema' => $exact->first(), 'candidates' => [], 'requires_confirmation' => false, 'ambiguous' => false];
        }

        $likeMatches = $cinemas->filter(function ($cinema) use ($reportNormalized) {
            $masterNormalized = $this->normalizeCinepolisCinemaName($cinema->nama_bioskop);
            return $reportNormalized !== '' && (str_contains($masterNormalized, $reportNormalized) || str_contains($reportNormalized, $masterNormalized));
        })->values();
        if ($likeMatches->count() === 1) {
            return ['cinema' => $likeMatches->first(), 'candidates' => [], 'requires_confirmation' => true, 'ambiguous' => false];
        }
        if ($likeMatches->count() > 1 && $selectedCinemaUuid) {
            $selected = $likeMatches->firstWhere('uuid', $selectedCinemaUuid);
            if ($selected) {
                return ['cinema' => $selected, 'candidates' => $candidateRows($likeMatches), 'requires_confirmation' => false, 'ambiguous' => false];
            }
        }

        return [
            'cinema' => null,
            'candidates' => $candidateRows($likeMatches),
            'requires_confirmation' => false,
            'ambiguous' => $likeMatches->count() > 1,
        ];
    }

    private function normalizeCinepolisCinemaName(?string $name): string
    {
        $value = $this->normalizeCinepolisCinemaDisplayName($name);
        $value = preg_replace('/\bCINEPOLIS\b/u', '', $value);
        $value = preg_replace('/\bMAXXBOX\b/u', 'MAXBOXX', $value);
        return trim(preg_replace('/\s+/', ' ', $value));
    }

    private function normalizeCinepolisCinemaDisplayName(?string $name): string
    {
        $value = mb_strtoupper((string) $name);
        $value = strtr($value, ['É' => 'E', 'È' => 'E', 'Ê' => 'E', 'Ë' => 'E']);
        return trim(preg_replace('/\s+/', ' ', $value));
    }

    public function uploadXXI(Request $request)
    {
        return $this->previewLegacyExcel($request, 'XXI');
    }

    public function uploadCGV(Request $request)
    {
        return $this->previewLegacyExcel($request, 'CGV');
    }

    public function uploadSAMS(Request $request)
    {
        return $this->previewLegacyExcel($request, 'SAMS STUDIOS');
    }

    public function previewLegacyExcel(Request $request, string $provider)
    {
        $request->validate(['file' => 'required|file|mimes:xlsx,xls|max:20480'], [
            'file.required' => 'File wajib diunggah.',
            'file.mimes' => 'Format file harus .xlsx atau .xls.',
            'file.max' => 'Ukuran file maksimal 20MB.',
        ]);

        try {
            @set_time_limit(0);
            @ini_set('memory_limit', '512M');
            $rows = $this->parseLegacyExcel($request->file('file')->getPathname(), $provider);
            $mapping = $this->mapLegacyPreview($rows, $provider);
            $token = (string) Str::uuid();
            Cache::put($this->legacyPreviewKey($token), [
                'provider' => $provider,
                'rows' => $rows,
                'mapping' => $mapping,
                'created_by' => Auth::user()->uuid ?? null,
            ], now()->addMinutes(30));
            return response()->json(array_merge(['status' => 'success', 'token' => $token], $mapping));
        } catch (\Throwable $e) {
            report($e);
            return response()->json(['status' => 'failed', 'message' => 'Preview gagal: '.$e->getMessage()], 422);
        }
    }

    public function confirmLegacyExcel(Request $request, string $provider)
    {
        $request->validate(['token' => 'required|string']);
        $cacheKey = $this->legacyPreviewKey($request->input('token'));
        $cached = Cache::get($cacheKey);
        if (!$cached || ($cached['provider'] ?? null) !== $provider) {
            return response()->json(['status' => 'failed', 'message' => 'Preview sudah kedaluwarsa. Silakan upload ulang file.'], 422);
        }
        if (($cached['created_by'] ?? null) !== (Auth::user()->uuid ?? null)) {
            return response()->json(['status' => 'failed', 'message' => 'Preview ini bukan milik sesi pengguna aktif.'], 403);
        }
        $mapping = $this->mapLegacyPreview($cached['rows'], $provider);
        if (!empty($mapping['blocking_issues'])) {
            return response()->json(['status' => 'failed', 'message' => 'Import diblokir karena mapping belum lengkap.', 'issues' => $mapping['blocking_issues']], 422);
        }
        $insertRows = [];
        foreach ($mapping['rows'] as $row) {
            $duplicate = Pelaporan::where('kategori', $row['kategori'])->where('nama_bioskop', $row['nama_bioskop'])
                ->where('nama_film', $row['nama_film'])->whereDate('tgl_tayang', $row['tgl_tayang'])
                ->where('jam_tayang', $row['jam_tayang'])->where('show', $row['show'])
                ->where('type_tiket', $row['type_tiket'])->where('harga', $row['harga'])->where('jumlah', $row['jumlah'])->exists();
            if ($duplicate) {
                return response()->json(['status' => 'failed', 'message' => 'Import diblokir karena terdapat data yang sudah pernah diimport.'], 422);
            }
            $row['uuid'] = Uuid::generate();
            $row['created_by'] = Auth::user()->uuid ?? $cached['created_by'];
            $row['created_at'] = now();
            $insertRows[] = $row;
        }
        DB::transaction(function () use ($insertRows) { Pelaporan::insert($insertRows); });
        Cache::forget($cacheKey);
        return response()->json(['status' => 'success', 'message' => count($insertRows).' baris '.$provider.' berhasil diimport.', 'inserted' => count($insertRows)]);
    }

    public function quickMasterLegacy(Request $request, string $provider)
    {
        $request->validate(['token' => 'required|string', 'resource' => 'required|in:cinema,film,ticket_type,capacity']);
        $cacheKey = $this->legacyPreviewKey($request->input('token'));
        $cached = Cache::get($cacheKey);
        if (!$cached || ($cached['provider'] ?? null) !== $provider) return response()->json(['status'=>'failed','message'=>'Preview sudah kedaluwarsa.'], 422);
        if (($cached['created_by'] ?? null) !== (Auth::user()->uuid ?? null)) return response()->json(['status'=>'failed','message'=>'Preview ini bukan milik sesi pengguna aktif.'], 403);
        $category = KategoriBioskop::whereRaw('UPPER(name) = ?', [$provider])->first();
        if (!$category) return response()->json(['status'=>'failed','message'=>'Kategori '.$provider.' belum tersedia.'], 422);
        $rows = $cached['rows'];
        $resource = $request->input('resource');
        if ($resource === 'cinema') {
            $request->validate(['name'=>'required|string|max:255','city'=>'required|string|max:255']);
            $allowed = collect($rows)->pluck('source_cinema')->unique()->map(fn($v)=>$this->legacyNormalize($v));
            if (!$allowed->contains($this->legacyNormalize($request->input('name')))) return response()->json(['status'=>'failed','message'=>'Nama bioskop harus berasal dari preview.'],422);
            $cinema = new MasterBioskop(); $cinema->type=$category->uuid; $cinema->nama_bioskop=$request->input('name'); $cinema->kota=$request->input('city'); $cinema->created_by=Auth::user()->uuid; $cinema->save();
        } elseif ($resource === 'film') {
            $request->validate(['name'=>'required|string|max:255']);
            if (!collect($rows)->pluck('nama_film')->map(fn($v)=>$this->legacyNormalize($v))->contains($this->legacyNormalize($request->input('name')))) return response()->json(['status'=>'failed','message'=>'Nama film harus berasal dari preview.'],422);
            $film = new MasterFilm(); $film->name=MasterFilm::normalizeName($request->input('name')); $film->created_by=Auth::user()->uuid; $film->save();
        } elseif ($resource === 'ticket_type') {
            $request->validate(['name'=>'required|string|max:255']);
            $allowed = collect($rows)->pluck('ticket_name')->unique()->map(fn($v)=>$this->legacyNormalize($v));
            if (!$allowed->contains($this->legacyNormalize($request->input('name')))) return response()->json(['status'=>'failed','message'=>'Tipe tiket harus berasal dari preview.'],422);
            $ticket = new TypeTiket(); $ticket->name=$request->input('name'); $ticket->kategori=$category->uuid; $ticket->save();
        } else {
            $request->validate(['source_row'=>'required|integer','studio'=>'required|string|max:50','kapasitas'=>'required|numeric|min:0']);
            $studio = $this->normalizeStudioNumber($request->input('studio'));
            $source = collect($rows)->first(function ($row) use ($request, $studio) {
                return (int) $row['source_row'] === (int) $request->input('source_row')
                    && $this->normalizeStudioNumber((string) $row['studio']) === $studio;
            });
            if (!$source) return response()->json(['status'=>'failed','message'=>'Baris kapasitas tidak sesuai preview.'],422);

            // Derive master context from the signed, user-bound preview row. Never trust UUIDs sent by the browser.
            $cinema = MasterBioskop::where('type', $category->uuid)
                ->whereRaw('UPPER(TRIM(nama_bioskop)) = ?', [$this->legacyNormalize($source['source_cinema'])])
                ->first();
            $ticket = TypeTiket::where('kategori', $category->uuid)
                ->whereRaw('UPPER(TRIM(name)) = ?', [$this->legacyNormalize($source['ticket_name'])])
                ->first();
            if (!$cinema || !$ticket) return response()->json(['status'=>'failed','message'=>'Bioskop atau tipe tiket pada baris preview belum memiliki mapping master.'],422);
            $capacity = Kapasitas::where('kategori', $category->uuid)
                ->where('nama_bioskop', $cinema->uuid)
                ->where('type_tiket', $ticket->uuid)
                ->get()
                ->first(fn ($item) => $this->normalizeStudioNumber((string) $item->studio) === $studio);

            if (!$capacity) {
                $capacity = new Kapasitas();
                $capacity->kategori = $category->uuid;
                $capacity->kota = $cinema->kota;
                $capacity->nama_bioskop = $cinema->uuid;
                $capacity->type_tiket = $ticket->uuid;
                $capacity->studio = $studio;
            }

            $capacity->kapasitas = $request->input('kapasitas');
            $capacity->save();
        }
        $fresh=$this->mapLegacyPreview($rows,$provider); $cached['mapping']=$fresh; Cache::put($cacheKey,$cached,now()->addMinutes(30));
        return response()->json(array_merge(['status'=>'success','message'=>'Master berhasil ditambahkan.','token'=>$request->input('token')],$fresh));
    }

    private function legacyPreviewKey(string $token): string { return 'legacy_excel_preview:'.$token; }

    private function legacyNormalize($value): string { return mb_strtoupper(trim(preg_replace('/\s+/', ' ', (string)$value)), 'UTF-8'); }

    private function parseLegacyExcel(string $path, string $provider): array
    {
        $rows=IOFactory::load($path)->getActiveSheet()->toArray(null,true,true,true); $out=[]; $today=now()->format('Y-m-d');
        foreach ($rows as $number=>$cols) { if ((int)$number===1 || !collect($cols)->contains(fn($v)=>trim((string)$v)!=='')) continue;
            $date=$provider==='XXI'?$cols['A']??'':($provider==='CGV'?$cols['A']??'':$cols['D']??'');
            try { $date=is_numeric($date)?Carbon::create(1899,12,30)->addDays((int)$date)->format('Y-m-d'):Carbon::parse($date?:$today)->format('Y-m-d'); } catch (\Throwable $e) { $date=$today; }
            if ($provider==='XXI') {
                $film=trim((string)($cols['B']??'')); $cinema=trim((string)($cols['C']??'')); $city=trim((string)($cols['D']??'')); $studio=trim((string)($cols['E']??'')); $price=(float)str_replace(',','',(string)($cols['M']??''));
                foreach (['F'=>'11:00','G'=>'13:00','H'=>'15:00','I'=>'17:00','J'=>'19:00','K'=>'21:00'] as $col=>$time) if (trim((string)($cols[$col]??''))!=='' && trim((string)$cols[$col])!=='-') { $show = ['F'=>1,'G'=>2,'H'=>3,'I'=>4,'J'=>5,'K'=>6][$col]; $out[]=$this->legacySourceRow($number,$date,$film,$cinema,$city,$studio,'REGULAR',$time,$show,trim((string)$cols[$col]),$price,0); }
            } elseif ($provider==='CGV') {
                $film=trim((string)($cols['D']??'')); $cinema=trim((string)($cols['B']??'')); $studio=trim((string)($cols['C']??'')); $ticket=trim((string)($cols['F']??'')); $price=(float)str_replace(',','',(string)($cols['G']??''));
                foreach ([['H','I',1],['K','L',2],['N','O',3],['Q','R',4],['T','U',5],['W','X',6]] as [$timeCol,$countCol,$show]) if (trim((string)($cols[$countCol]??''))!=='' && trim((string)$cols[$countCol])!=='-') $out[]=$this->legacySourceRow($number,$date,$film,$cinema,'',$studio,$ticket,trim((string)($cols[$timeCol]??'')),$show,trim((string)$cols[$countCol]),$price,0);
            } else {
                $film=trim((string)($cols['A']??'')); $cinema=trim((string)($cols['B']??'')); $studio=trim((string)($cols['C']??'')); $time=trim((string)($cols['E']??'')); $price=(float)preg_replace('/[^0-9\-]/','',(string)($cols['F']??'0'));
                if (trim((string)($cols['K']??''))!=='' && trim((string)$cols['K'])!=='-') $out[]=$this->legacySourceRow($number,$date,$film,$cinema,'',$studio,'REGULAR',$time,1,trim((string)$cols['K']),$price,0);
                if (trim((string)($cols['L']??''))!=='' && trim((string)$cols['L'])!=='-') $out[]=$this->legacySourceRow($number,$date,$film,$cinema,'',$studio,'BOGOF',$time,1,trim((string)$cols['L']),0,0);
            }
        }
        if (!$out) throw new \RuntimeException('Sheet kosong / header tidak ditemukan.'); return $out;
    }

    private function legacySourceRow($sourceRow,$date,$film,$cinema,$city,$studio,$ticket,$time,$show,$count,$price,$tax): array { return ['source_row'=>(int)$sourceRow,'tgl_tayang'=>$date,'nama_film'=>mb_strtoupper($film),'source_cinema'=>$cinema,'source_city'=>$city,'studio'=>$studio,'ticket_name'=>mb_strtoupper($ticket),'jam_tayang'=>$time,'show'=>(string)$show,'jumlah'=>(float)str_replace(',','',$count),'harga'=>$price,'tax'=>$tax,'net'=>0]; }

    private function mapLegacyPreview(array $sourceRows, string $provider): array
    {
        $category=KategoriBioskop::whereRaw('UPPER(name) = ?',[$provider])->first(); $issues=[]; $warnings=[]; $filmNames=collect($sourceRows)->pluck('nama_film')->unique();
        $filmMap=MasterFilm::get()->filter(fn($f)=>in_array($this->legacyNormalize($f->name),$filmNames->map(fn($v)=>$this->legacyNormalize($v))->all(),true))->keyBy(fn($f)=>$this->legacyNormalize($f->name));
        $cinemas=$category?MasterBioskop::where('type',$category->uuid)->get():collect(); $cinemaMap=[];
        foreach (collect($sourceRows)->unique(fn($row)=>$this->legacyCinemaKey($row['source_cinema'],$row['source_city'])) as $row) {
            $key=$this->legacyCinemaKey($row['source_cinema'],$row['source_city']);
            // Cinema name is normalized, but the source city must match the master city exactly.
            $matches=$cinemas->filter(fn($cinema)=>$this->legacyNormalize($cinema->nama_bioskop)===$this->legacyNormalize($row['source_cinema']) && (string)$cinema->kota === (string)$row['source_city']);
            if($matches->count()===1) $cinemaMap[$key]=$matches->first();
            elseif($matches->count()===0) $issues[]='Bioskop '.$row['source_cinema'].' di kota '.$row['source_city'].' belum terdaftar sebagai bioskop kategori '.$provider.'.';
            else { $warnings[]='Bioskop '.$row['source_cinema'].' di kota '.$row['source_city'].' memiliki lebih dari satu mapping master dan memerlukan pemilihan.'; $issues[]='Bioskop '.$row['source_cinema'].' di kota '.$row['source_city'].' memiliki mapping ambigu; perbaiki master bioskop sebelum import.'; }
        }
        if(!$category)$issues[]='Kategori '.$provider.' belum tersedia di Master Kategori Bioskop.';
        foreach($filmNames as $name)if(!$filmMap->has($this->legacyNormalize($name)))$issues[]='Film '.$name.' belum terdaftar di Master Film.';
        $ticketMap=$category?TypeTiket::where('kategori',$category->uuid)->get():collect(); $canonical=[]; $preview=[];
        foreach($sourceRows as $row){ $cinema=$cinemaMap[$this->legacyCinemaKey($row['source_cinema'],$row['source_city'])]??null; $ticket=$ticketMap->first(fn($t)=>$this->legacyNormalize($t->name)===$this->legacyNormalize($row['ticket_name'])); $capacity=$cinema&&$ticket?$this->findLegacyCapacity($category->uuid,$cinema->uuid,$ticket->uuid,$row['studio']):null; if(!$ticket)$issues[]='Tipe tiket '.$row['ticket_name'].' belum tersedia untuk kategori '.$provider.'.'; if($cinema&&$ticket&&!$capacity)$issues[]='Studio '.$row['studio'].' belum memiliki mapping kapasitas untuk tipe tiket '.$row['ticket_name'].' di bioskop '.$row['source_cinema'].' (baris '.$row['source_row'].').'; $ready=$cinema&&isset($filmMap[$this->legacyNormalize($row['nama_film'])])&&$ticket&&$capacity; $preview[]=array_merge($row,['kategori'=>$provider,'bioskop'=>$row['source_cinema'],'kota'=>$cinema->kota??$row['source_city'],'cinema_uuid'=>$cinema->uuid??null,'film_uuid'=>$filmMap[$this->legacyNormalize($row['nama_film'])]->uuid??null,'ticket_uuid'=>$ticket->uuid??null,'capacity_uuid'=>$capacity->uuid??null,'mapping_status'=>$ready?'Siap':'Diblokir']); if($ready)$canonical[]=['kategori'=>$category->uuid,'provinsi'=>$this->legacyProvinceForCity($cinema->kota),'kota'=>$cinema->kota,'nama_bioskop'=>$cinema->uuid,'nama_film'=>$filmMap[$this->legacyNormalize($row['nama_film'])]->name,'tgl_tayang'=>$row['tgl_tayang'],'jam_tayang'=>$row['jam_tayang']?:'00:00','show'=>$row['show'],'type_tiket'=>$ticket->uuid,'harga'=>$row['harga'],'jumlah'=>$row['jumlah'],'gross'=>$row['harga']*$row['jumlah'],'tax'=>$cinema->pajak??0,'net'=>$row['net']?:($row['harga']*$row['jumlah'])-(($row['harga']*$row['jumlah'])*($cinema->pajak??0)/100),'studio'=>$capacity->uuid]; }
        $cinemaNames=collect($sourceRows)->pluck('source_cinema')->unique();
        $issues=array_values(array_unique($issues)); return ['preview'=>$preview,'rows'=>$canonical,'blocking_issues'=>$issues,'warnings'=>$warnings,'summary'=>['provider'=>$provider,'rows'=>count($sourceRows),'ready'=>count($canonical),'blocked'=>count($sourceRows)-count($canonical)],'quick_master_context'=>['cinema_name'=>$cinemaNames->first(),'film_name'=>$filmNames->first(),'category_uuid'=>optional($category)->uuid,'ticket_types'=>$sourceRows?array_values(array_unique(array_column($sourceRows,'ticket_name'))):[],'studios'=>$sourceRows?array_values(array_unique(array_column($sourceRows,'studio'))):[]]];
    }

    private function legacyCinemaKey($name, $city): string { return $this->legacyNormalize($name).'|'.(string)$city; }

    private function findLegacyCapacity($category,$cinema,$ticket,$studio){ $needle=$this->normalizeStudioNumber((string)$studio); return Kapasitas::where('kategori',$category)->where('nama_bioskop',$cinema)->where('type_tiket',$ticket)->get()->first(fn($c)=>$this->normalizeStudioNumber((string)$c->studio)===$needle); }

    private function legacyProvinceForCity(?string $city): string
    {
        if (!$city || !Schema::hasTable((new Kota)->getTable()) || !Schema::hasTable((new Province)->getTable())) return '';
        $cityRecord = Kota::where(function ($query) use ($city) {
            $query->where('nama', $city)
                ->orWhere('nama', 'Kota '.$city)
                ->orWhere('nama', 'Kabupaten '.$city);
        })->first();
        return $cityRecord ? (string) optional(Province::where('uuid', $cityRecord->provinsi_id)->first())->nama : '';
    }

    /**
     * Pastikan setiap nama film dalam file upload sudah terdaftar di Master Film.
     * Validasi dilakukan sebelum tabel staging dikosongkan agar import yang tidak
     * valid sama sekali tidak mengubah data.
     */
    private function validateImportedFilms(array $rows, string $filmColumn, string $source)
    {
        $filmRows = [];
        $emptyFilmRows = [];

        foreach ($rows as $rowNumber => $columns) {
            if ((int) $rowNumber === 1) {
                continue;
            }

            $hasContent = collect($columns)->contains(function ($value) {
                return trim((string) $value) !== '';
            });

            if (!$hasContent) {
                continue;
            }

            $filmName = MasterFilm::normalizeName($columns[$filmColumn] ?? '');

            if ($filmName === '') {
                $emptyFilmRows[] = (int) $rowNumber;
                continue;
            }

            $filmRows[$filmName][] = (int) $rowNumber;
        }

        $knownFilms = collect();
        foreach (array_chunk(array_keys($filmRows), 500) as $filmNames) {
            $knownFilms = $knownFilms->merge(
                MasterFilm::whereIn('name', $filmNames)->pluck('name')
            );
        }

        $knownLookup = $knownFilms
            ->mapWithKeys(function ($name) {
                return [MasterFilm::normalizeName($name) => true];
            })
            ->all();

        $unknownFilms = [];
        foreach ($filmRows as $filmName => $rowNumbers) {
            if (!isset($knownLookup[$filmName])) {
                $unknownFilms[] = [
                    'name' => $filmName,
                    'rows' => $rowNumbers,
                ];
            }
        }

        if (!$emptyFilmRows && !$unknownFilms) {
            return null;
        }

        $problems = [];
        if ($emptyFilmRows) {
            $problems[] = 'nama film kosong pada baris ' . implode(', ', $emptyFilmRows);
        }

        foreach ($unknownFilms as $film) {
            $problems[] = '"' . $film['name'] . '" pada baris ' . implode(', ', $film['rows']);
        }

        return response()->json([
            'status' => 'failed',
            'message' => 'Import ' . $source . ' dibatalkan. ' . implode('; ', $problems)
                . '. Tambahkan film yang belum terdaftar melalui menu Master > Master Film, kemudian upload ulang file.',
            'errors' => [
                'empty_film_rows' => $emptyFilmRows,
                'unknown_films' => $unknownFilms,
            ],
        ], 422);
    }

}
