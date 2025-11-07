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
use Uuid;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\Support\Facades\Cache;

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
                            <a class="btn btn-success btn-sm btn-icon waves-effect waves-themed" href="' . route('pelaporan.edit', $row->uuid) . '"><i class="fal fa-edit"></i></a>';
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
        $nama_film = Pelaporan::selectRaw('Distinct nama_film')->pluck('nama_film', 'nama_film');

        return view('pelaporan.index',compact('bioskop_kategori', 'nama_bioskop', 'kota','type_tiket', 'nama_film'));
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
        // Validasi
        $rules = [
            'kategori' => 'required',
            'kota' => 'required',
            'nama_bioskop' => 'required',
            'nama_film' => 'required',
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

    public function uploadXXI(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls|max:20480',
        ], [
            'file.required' => 'File wajib diunggah.',
            'file.mimes'    => 'Format file harus .xlsx atau .xls.',
            'file.max'      => 'Ukuran file maksimal 20MB.',
        ]);

        try {
            // optional: pastikan tidak timeout/kehabisan memori untuk file besar
            @set_time_limit(0);
            @ini_set('memory_limit', '512M');

            $file = $request->file('file');

            // Kamu boleh simpan dulu, tapi untuk sinkron cukup load dari tmp path
            $spreadsheet = IOFactory::load($file->getPathname());
            $sheet       = $spreadsheet->getActiveSheet();
            // toArray(null, true, true, true) => key kolom: 'A','B',dst
            $rows        = $sheet->toArray(null, true, true, true);

            if (count($rows) <= 1) {
                return response()->json([
                    'status'  => 'failed',
                    'message' => 'Sheet kosong / header tidak ditemukan.',
                ], 422);
            }

            DB::table('xxi_template')->truncate();

            $batch     = [];
            $batchSize = 1000;
            $inserted  = 0;
            $today     = now()->format('Y-m-d');

            DB::beginTransaction();

            foreach ($rows as $idx => $cols) {
                if ($idx === 1) continue; // skip header baris 1

                $reportDate = trim((string)($cols['A'] ?? ''));
                $namaFilm   = trim((string)($cols['B'] ?? ''));
                $namaBios   = trim((string)($cols['C'] ?? ''));
                $kota       = trim((string)($cols['D'] ?? ''));
                $studio     = trim((string)($cols['E'] ?? '')); // tidak dipakai
                $satu       = trim((string)($cols['F'] ?? ''));
                $dua        = trim((string)($cols['G'] ?? ''));
                $tiga       = trim((string)($cols['H'] ?? ''));
                $empat      = trim((string)($cols['I'] ?? ''));
                $lima       = trim((string)($cols['J'] ?? ''));
                $enam       = trim((string)($cols['K'] ?? ''));
                // $total      = trim((string)($cols['L'] ?? ''));
                $harga      = trim((string)($cols['M'] ?? ''));
                $freePass   = trim((string)($cols['N'] ?? ''));

                // Normalisasi tanggal
                try {
                    if ($reportDate === '') {
                        $reportDate = $today;
                    } elseif (is_numeric($reportDate)) {
                        // Excel serial date → 1899-12-30
                        $reportDate = \Carbon\Carbon::create(1899,12,30)->addDays((int)$reportDate)->format('Y-m-d');
                    } else {
                        $reportDate = \Carbon\Carbon::parse($reportDate)->format('Y-m-d');
                    }
                } catch (\Throwable $e) {
                    $reportDate = $today;
                }

                // Row minimal: nama_film & nama_bioskop ada
                if ($namaFilm === '' && $namaBios === '') {
                    continue;
                }

                $batch[] = [
                    'uuid'        => Uuid::generate(),
                    'report_date' => $reportDate,
                    'nama_film'   => $namaFilm !== '' ? mb_strtoupper($namaFilm) : null,
                    'nama_bioskop'=> $namaBios !== '' ? mb_strtoupper($namaBios) : null,
                    'studio'      => $studio !== '' ? $studio : null,
                    'satu'        => $satu !== '' ? $satu : null,
                    'dua'         => $dua !== '' ? $dua : null,
                    'tiga'        => $tiga !== '' ? $tiga : null,
                    'empat'       => $empat !== '' ? $empat : null,
                    'lima'        => $lima !== '' ? $lima : null,
                    'enam'        => $enam !== '' ? $enam : null,
                    'total'   => $freePass !== '' ? $freePass : null,
                    'harga'       => $harga !== '' ? $harga : null,
                    'created_by'  => \Auth::user()->uuid ?? null,
                    'edited_by'   => null,
                    'created_at'  => now(),
                    'updated_at'  => null,
                    'kota'        => $kota !== '' ? mb_strtoupper($kota) : null,
                ];

                if (count($batch) >= $batchSize) {
                    DB::table('xxi_template')->insert($batch);
                    $inserted += count($batch);
                    $batch = [];
                }
            }

            if (!empty($batch)) {
                DB::table('xxi_template')->insert($batch);
                $inserted += count($batch);
            }

            $COLLATE = 'utf8mb4_unicode_ci';

            $cek_bioskop = <<<SQL
                    SELECT DISTINCT p.kota, p.nama_bioskop
                    FROM (
                        SELECT 'XXI' AS kategori, kota, nama_bioskop, nama_film, report_date AS tgl_tayang, '' AS jam_tayang, '1' AS `show`, 'REGULAR-XXI' AS type_tiket, harga, satu AS jumlah, '' AS gross, NULL AS tax, NULL AS net, CONCAT(nama_bioskop, '-REGULAR-', CAST(studio as DECIMAL)) AS studio
                        FROM xxi_template
                        WHERE satu != '-'

                        UNION ALL
                        SELECT 'XXI' AS kategori, kota, nama_bioskop, nama_film, report_date AS tgl_tayang, '' AS jam_tayang, '2' AS `show`, 'REGULAR-XXI' AS type_tiket, harga, dua AS jumlah, '' AS gross, NULL AS tax, NULL AS net, CONCAT(nama_bioskop, '-REGULAR-', CAST(studio as DECIMAL)) AS studio
                        FROM xxi_template
                        WHERE dua != '-'

                        UNION ALL
                        SELECT 'XXI' AS kategori, kota, nama_bioskop, nama_film, report_date AS tgl_tayang, '' AS jam_tayang, '3' AS `show`, 'REGULAR-XXI' AS type_tiket, harga, tiga AS jumlah, '' AS gross, NULL AS tax, NULL AS net, CONCAT(nama_bioskop, '-REGULAR-', CAST(studio as DECIMAL)) AS studio
                        FROM xxi_template
                        WHERE tiga != '-'

                        UNION ALL
                        SELECT 'XXI' AS kategori, kota, nama_bioskop, nama_film, report_date AS tgl_tayang, '' AS jam_tayang, '4' AS `show`, 'REGULAR-XXI' AS type_tiket, harga, empat AS jumlah, '' AS gross, NULL AS tax, NULL AS net, CONCAT(nama_bioskop, '-REGULAR-', CAST(studio as DECIMAL)) AS studio
                        FROM xxi_template
                        WHERE empat != '-'

                        UNION ALL
                        SELECT 'XXI' AS kategori, kota, nama_bioskop, nama_film, report_date AS tgl_tayang, '' AS jam_tayang, '5' AS `show`, 'REGULAR-XXI' AS type_tiket, harga, lima AS jumlah, '' AS gross, NULL AS tax, NULL AS net, CONCAT(nama_bioskop, '-REGULAR-', CAST(studio as DECIMAL)) AS studio
                        FROM xxi_template
                        WHERE TRIM(lima) != '-' AND TRIM(lima) != '' AND lima IS NOT NULL

                        UNION ALL
                        SELECT 'XXI' AS kategori, kota, nama_bioskop, nama_film, report_date AS tgl_tayang, '' AS jam_tayang, '6' AS `show`, 'REGULAR-XXI' AS type_tiket, harga, enam AS jumlah, '' AS gross, NULL AS tax, NULL AS net, CONCAT(nama_bioskop, '-REGULAR-', CAST(studio as DECIMAL)) AS studio
                        FROM xxi_template
                        WHERE enam != '-'
                    ) p
                    LEFT JOIN kategori_bioskops kb
                        ON kb.name COLLATE {$COLLATE} = p.kategori COLLATE {$COLLATE}
                    LEFT JOIN master_bioskops mb
                        ON mb.nama_bioskop COLLATE {$COLLATE} = p.nama_bioskop COLLATE {$COLLATE}
                        AND mb.type COLLATE {$COLLATE} = kb.uuid COLLATE {$COLLATE}
                        AND mb.kota COLLATE {$COLLATE} = p.kota COLLATE {$COLLATE}
                    WHERE mb.uuid IS NULL
                    SQL;

            $notMapped_bioskop = DB::select(DB::raw($cek_bioskop));

            $cek_studio = <<<SQL
                    SELECT DISTINCT distinct p.studio, kb.uuid AS kategori,
                    mb.kota AS kota,
                    mb.uuid AS nama_bioskop,tt.uuid AS type_tiket
                    FROM (
                        SELECT 'XXI' AS kategori, kota, nama_bioskop, nama_film, report_date AS tgl_tayang, '' AS jam_tayang, '1' AS `show`, 'REGULAR-XXI' AS type_tiket, harga, satu AS jumlah, '' AS gross, NULL AS tax, NULL AS net, CONCAT(nama_bioskop, '-REGULAR-', CAST(studio as DECIMAL)) AS studio
                        FROM xxi_template
                        WHERE satu != '-'

                        UNION ALL
                        SELECT 'XXI' AS kategori, kota, nama_bioskop, nama_film, report_date AS tgl_tayang, '' AS jam_tayang, '2' AS `show`, 'REGULAR-XXI' AS type_tiket, harga, dua AS jumlah, '' AS gross, NULL AS tax, NULL AS net, CONCAT(nama_bioskop, '-REGULAR-', CAST(studio as DECIMAL)) AS studio
                        FROM xxi_template
                        WHERE dua != '-'

                        UNION ALL
                        SELECT 'XXI' AS kategori, kota, nama_bioskop, nama_film, report_date AS tgl_tayang, '' AS jam_tayang, '3' AS `show`, 'REGULAR-XXI' AS type_tiket, harga, tiga AS jumlah, '' AS gross, NULL AS tax, NULL AS net, CONCAT(nama_bioskop, '-REGULAR-', CAST(studio as DECIMAL)) AS studio
                        FROM xxi_template
                        WHERE tiga != '-'

                        UNION ALL
                        SELECT 'XXI' AS kategori, kota, nama_bioskop, nama_film, report_date AS tgl_tayang, '' AS jam_tayang, '4' AS `show`, 'REGULAR-XXI' AS type_tiket, harga, empat AS jumlah, '' AS gross, NULL AS tax, NULL AS net, CONCAT(nama_bioskop, '-REGULAR-', CAST(studio as DECIMAL)) AS studio
                        FROM xxi_template
                        WHERE empat != '-'

                        UNION ALL
                        SELECT 'XXI' AS kategori, kota, nama_bioskop, nama_film, report_date AS tgl_tayang, '' AS jam_tayang, '5' AS `show`, 'REGULAR-XXI' AS type_tiket, harga, lima AS jumlah, '' AS gross, NULL AS tax, NULL AS net, CONCAT(nama_bioskop, '-REGULAR-', CAST(studio as DECIMAL)) AS studio
                        FROM xxi_template
                        WHERE TRIM(lima) != '-' AND TRIM(lima) != '' AND lima IS NOT NULL

                        UNION ALL
                        SELECT 'XXI' AS kategori, kota, nama_bioskop, nama_film, report_date AS tgl_tayang, '' AS jam_tayang, '6' AS `show`, 'REGULAR-XXI' AS type_tiket, harga, enam AS jumlah, '' AS gross, NULL AS tax, NULL AS net, CONCAT(nama_bioskop, '-REGULAR-', CAST(studio as DECIMAL)) AS studio
                        FROM xxi_template
                        WHERE enam != '-'
                    ) p
                    LEFT JOIN kategori_bioskops kb
                        ON kb.name COLLATE {$COLLATE} = p.kategori COLLATE {$COLLATE}
                    LEFT JOIN master_bioskops mb
                        ON mb.nama_bioskop COLLATE {$COLLATE} = p.nama_bioskop COLLATE {$COLLATE}
                        AND mb.type COLLATE {$COLLATE} = kb.uuid COLLATE {$COLLATE}
                        AND mb.kota COLLATE {$COLLATE} = p.kota COLLATE {$COLLATE}
                    LEFT JOIN type_tikets tt
                        ON tt.name COLLATE {$COLLATE} = REPLACE(p.type_tiket, '-XXI', '')
                        AND tt.kategori COLLATE {$COLLATE} = (SELECT kbb.uuid FROM kategori_bioskops kbb WHERE kbb.name = 'XXI')
                    LEFT JOIN kapasitas k
                        ON k.studio COLLATE {$COLLATE} = SUBSTRING_INDEX(p.studio, '-', -1)
                        AND k.nama_bioskop COLLATE {$COLLATE} = mb.uuid COLLATE {$COLLATE}
                        AND k.type_tiket COLLATE {$COLLATE} = tt.uuid COLLATE {$COLLATE}
                        AND k.kategori COLLATE {$COLLATE} = kb.uuid COLLATE {$COLLATE}
                    WHERE k.studio is null;
                    SQL;

            $notMapped_studio = DB::select(DB::raw($cek_studio));

            DB::commit();

            if (count($notMapped_bioskop) > 0) {
                // 3) Siapkan token & cache data untuk diunduh
                // $token = (string) Str::uuid();
                // Cache::put("xxi_err:$token", $notMapped, now()->addMinutes(30));

                // return response()->json([
                //     'status'       => 'failed',
                //     'message'      => 'Validasi gagal: ada kota/nama_bioskop yang belum terdaftar di master.',
                //     'download_url' => route('pelaporan.upload.xxi.errors', ['token' => $token]),
                //     'count'        => count($notMapped),
                // ], 200);

                $msgList = [];
                foreach ($notMapped_bioskop as $row) {
                    $msgList[] = "{$row->kota} - {$row->nama_bioskop}";
                }

                // Lempar exception dengan daftar data error
                throw new \Exception(
                    "Validasi gagal, berikut bioskop yang belum terdaftar di master:\r\n" . implode("\n", $msgList)
                );
            }

            if (count($notMapped_studio) > 0) {
                // 3) Siapkan token & cache data untuk diunduh
                // $token = (string) Str::uuid();
                // Cache::put("xxi_err:$token", $notMapped_studio, now()->addMinutes(30));

                // return response()->json([
                //     'status'       => 'failed',
                //     'message'      => 'Validasi gagal: ada studio yang belum terdaftar di master.',
                //     'download_url' => route('pelaporan.upload.xxi.errors', ['token' => $token]),
                //     'count'        => count($notMapped_studio),
                // ], 200);

                $msgList = [];
                foreach ($notMapped_studio as $row) {
                    $msgList[] = "{$row->studio} - {$row->kategori} - {$row->kota} - {$row->nama_bioskop} - {$row->type_tiket}";
                }

                // Lempar exception dengan daftar data error
                throw new \Exception(
                    "Validasi gagal, berikut studio yang belum terdaftar di master:\r\n" . implode("\n", $msgList)
                );
            }

            $pelaporanTable = (new \App\Models\Pelaporan)->getTable(); // biasanya 'pelaporans'
            $user = Auth::user()->uuid;
            $COLLATE = 'utf8mb4_unicode_ci'; // ganti ke 'utf8mb4_0900_ai_ci' jika itu standar DB kamu

            $sqlInsert = <<<SQL
            INSERT INTO {$pelaporanTable} 
            (uuid, kategori, kota, nama_bioskop, nama_film, tgl_tayang, jam_tayang, `show`, type_tiket, harga, jumlah, gross, tax, net, studio, created_by, created_at)
            SELECT
                UUID() AS uuid,
                kb.uuid AS kategori,
                p.kota AS kota,
                mb.uuid AS nama_bioskop,
                p.nama_film AS nama_film,
                p.tgl_tayang,
                p.jam_tayang,
                p.`show`,
                tt.uuid AS type_tiket,
                p.harga,
                p.jumlah,
                (p.harga * p.jumlah) AS gross,
                0 AS tax,
                (p.harga * p.jumlah - 0) AS net,
                k.uuid AS studio,
                '{$user}' AS created_by, -- ganti jika mau pakai Auth::user()->uuid
                NOW() AS created_at
            FROM (
                SELECT 'XXI' AS kategori, kota, nama_bioskop, nama_film, report_date AS tgl_tayang, '11:00' AS jam_tayang, '1' AS `show`, 'REGULAR-XXI' AS type_tiket, CAST(harga AS DECIMAL) AS harga, CAST(satu AS DECIMAL) AS jumlah, CONCAT(nama_bioskop, '-REGULAR-', CAST(studio as DECIMAL)) AS studio, created_by
                FROM xxi_template WHERE satu != '-'

                UNION ALL
                SELECT 'XXI', kota, nama_bioskop, nama_film, report_date, '13:00', '2', 'REGULAR-XXI', CAST(harga AS DECIMAL), CAST(dua AS DECIMAL), CONCAT(nama_bioskop, '-REGULAR-', CAST(studio as DECIMAL)), created_by
                FROM xxi_template WHERE dua != '-'

                UNION ALL
                SELECT 'XXI', kota, nama_bioskop, nama_film, report_date, '15:00', '3', 'REGULAR-XXI', CAST(harga AS DECIMAL), CAST(tiga AS DECIMAL), CONCAT(nama_bioskop, '-REGULAR-', CAST(studio as DECIMAL)), created_by
                FROM xxi_template WHERE tiga != '-'

                UNION ALL
                SELECT 'XXI', kota, nama_bioskop, nama_film, report_date, '17:00', '4', 'REGULAR-XXI', CAST(harga AS DECIMAL), CAST(empat AS DECIMAL), CONCAT(nama_bioskop, '-REGULAR-', CAST(studio as DECIMAL)), created_by
                FROM xxi_template WHERE empat != '-'

                UNION ALL
                SELECT 'XXI', kota, nama_bioskop, nama_film, report_date, '19:00', '5', 'REGULAR-XXI', CAST(harga AS DECIMAL), CAST(lima AS DECIMAL), CONCAT(nama_bioskop, '-REGULAR-', CAST(studio as DECIMAL)), created_by
                FROM xxi_template WHERE TRIM(lima) != '-' AND TRIM(lima) != '' AND lima IS NOT NULL

                UNION ALL
                SELECT 'XXI', kota, nama_bioskop, nama_film, report_date, '21:00', '6', 'REGULAR-XXI', CAST(harga AS DECIMAL), CAST(enam AS DECIMAL), CONCAT(nama_bioskop, '-REGULAR-', CAST(studio as DECIMAL)), created_by
                FROM xxi_template WHERE enam != '-'
            ) p
            LEFT JOIN kategori_bioskops kb
                ON kb.name COLLATE {$COLLATE} = p.kategori COLLATE {$COLLATE}
            LEFT JOIN master_bioskops mb
                ON mb.nama_bioskop COLLATE {$COLLATE} = p.nama_bioskop COLLATE {$COLLATE}
                AND mb.type COLLATE {$COLLATE} = kb.uuid COLLATE {$COLLATE}
                AND mb.kota COLLATE {$COLLATE} = p.kota COLLATE {$COLLATE}
            LEFT JOIN type_tikets tt
                ON tt.name COLLATE {$COLLATE} = SUBSTRING_INDEX(p.type_tiket, '-', 1) COLLATE {$COLLATE}
                AND tt.kategori COLLATE {$COLLATE} = (SELECT kbb.uuid FROM kategori_bioskops kbb WHERE kbb.name COLLATE {$COLLATE} = 'XXI' COLLATE {$COLLATE})
            LEFT JOIN kapasitas k
                ON CAST(k.studio AS CHAR) COLLATE {$COLLATE} = SUBSTRING_INDEX(p.studio, '-', -1) COLLATE {$COLLATE}
                AND k.nama_bioskop COLLATE {$COLLATE} = mb.uuid COLLATE {$COLLATE}
                AND k.type_tiket COLLATE {$COLLATE} = tt.uuid COLLATE {$COLLATE}
                AND k.kategori COLLATE {$COLLATE} = kb.uuid COLLATE {$COLLATE};
            SQL;

            // jalankan dan dapatkan jumlah baris yang masuk
            $affected = DB::affectingStatement(DB::raw($sqlInsert));

            // jika lolos validasi
            return response()->json([
                'status'   => 'success',
                'message'  => "Selesai. {$inserted} baris tersimpan. Validasi mapping master OK.",
                'inserted' => $inserted,
            ]);

        } catch (\Throwable $e) {
            DB::rollBack();
            report($e);
            return response()->json([
                'status'  => 'failed',
                'message' => 'Import gagal: '.$e->getMessage(),
            ], 500);
        }
    }

    public function downloadXxiErrors(string $token)
    {
        $rows = Cache::get("xxi_err:$token");
        if (!$rows) {
            abort(404, 'Data error tidak ditemukan atau sudah kedaluwarsa.');
        }

        // Buat XLSX sederhana: kolom kota, nama_bioskop
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Mapping Error');

        // Header
        $sheet->setCellValue('A1', 'kota');
        $sheet->setCellValue('B1', 'nama_bioskop');

        // Data
        $r = 2;
        foreach ($rows as $obj) {
            // $obj adalah stdClass dari DB::select
            $sheet->setCellValue("A{$r}", $obj->kota ?? '');
            $sheet->setCellValue("B{$r}", $obj->nama_bioskop ?? '');
            $r++;
        }

        // Stream download
        $filename = 'mapping_error_xxi_'.now()->format('Ymd_His').'.xlsx';
        $writer = new Xlsx($spreadsheet);

        return new StreamedResponse(function () use ($writer) {
            $writer->save('php://output');
        }, 200, [
            'Content-Type'        => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
            'Cache-Control'       => 'max-age=0, no-cache, no-store, must-revalidate',
            'Pragma'              => 'public',
        ]);
    }

    public function uploadCGV(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls|max:20480',
        ], [
            'file.required' => 'File wajib diunggah.',
            'file.mimes'    => 'Format file harus .xlsx atau .xls.',
            'file.max'      => 'Ukuran file maksimal 20MB.',
        ]);

        try {
            // optional: pastikan tidak timeout/kehabisan memori untuk file besar
            @set_time_limit(0);
            @ini_set('memory_limit', '512M');

            $file = $request->file('file');

            // Kamu boleh simpan dulu, tapi untuk sinkron cukup load dari tmp path
            $spreadsheet = IOFactory::load($file->getPathname());
            $sheet       = $spreadsheet->getActiveSheet();
            // toArray(null, true, true, true) => key kolom: 'A','B',dst
            $rows        = $sheet->toArray(null, true, true, true);

            if (count($rows) <= 1) {
                return response()->json([
                    'status'  => 'failed',
                    'message' => 'Sheet kosong / header tidak ditemukan.',
                ], 422);
            }

            DB::table('cgv_template')->truncate();

            $batch     = [];
            $batchSize = 1000;
            $inserted  = 0;
            $today     = now()->format('Y-m-d');

            DB::beginTransaction();

            foreach ($rows as $idx => $cols) {
                if ($idx === 1) continue; // skip header baris 1

                $reportDate = trim((string)($cols['A'] ?? ''));
                $namaBios   = trim((string)($cols['B'] ?? ''));
                $studio     = trim((string)($cols['C'] ?? ''));
                $namaFilm   = trim((string)($cols['D'] ?? ''));
                // $format     = trim((string)($cols['E'] ?? ''));
                $type_tiket = trim((string)($cols['F'] ?? ''));
                $harga      = trim((string)($cols['G'] ?? ''));
                $jam1       = trim((string)($cols['H'] ?? ''));
                $satu       = trim((string)($cols['I'] ?? ''));
                $free1      = trim((string)($cols['J'] ?? ''));
                $jam2       = trim((string)($cols['K'] ?? ''));
                $dua        = trim((string)($cols['L'] ?? ''));
                $free2      = trim((string)($cols['M'] ?? ''));
                $jam3       = trim((string)($cols['N'] ?? ''));
                $tiga       = trim((string)($cols['O'] ?? ''));
                $free3      = trim((string)($cols['P'] ?? ''));
                $jam4       = trim((string)($cols['Q'] ?? ''));
                $empat      = trim((string)($cols['R'] ?? ''));
                $free4      = trim((string)($cols['S'] ?? ''));
                $jam5       = trim((string)($cols['T'] ?? ''));
                $lima       = trim((string)($cols['U'] ?? ''));
                $free5      = trim((string)($cols['V'] ?? ''));
                $jam6       = trim((string)($cols['W'] ?? ''));
                $enam       = trim((string)($cols['X'] ?? ''));
                $free6      = trim((string)($cols['Y'] ?? ''));
                $total      = trim((string)($cols['Z'] ?? ''));
                $total_free = trim((string)($cols['AA'] ?? ''));
                $net        = trim((string)($cols['AB'] ?? ''));

                // Normalisasi tanggal
                try {
                    if ($reportDate === '') {
                        $reportDate = $today;
                    } elseif (is_numeric($reportDate)) {
                        // Excel serial date → 1899-12-30
                        $reportDate = \Carbon\Carbon::create(1899,12,30)->addDays((int)$reportDate)->format('Y-m-d');
                    } else {
                        $reportDate = \Carbon\Carbon::parse($reportDate)->format('Y-m-d');
                    }
                } catch (\Throwable $e) {
                    $reportDate = $today;
                }

                // Row minimal: nama_film & nama_bioskop ada
                if ($namaFilm === '' && $namaBios === '') {
                    continue;
                }

                $batch[] = [
                    'uuid'        => Uuid::generate(),
                    'report_date' => $reportDate,
                    'nama_bioskop'=> $namaBios !== '' ? mb_strtoupper($namaBios) : null,
                    'studio'      => $studio !== '' ? $studio : null,
                    'nama_film'   => $namaFilm !== '' ? mb_strtoupper($namaFilm) : null,
                    // 'format'      => $format !== '' ? mb_strtoupper($format) : null,
                    'tipe_tiket'  => $type_tiket !== '' ? mb_strtoupper($type_tiket) : null,
                    'harga'       => $harga !== '' ? $harga : null,
                    'jam1'        => $jam1 !== '' ? $jam1 : null,
                    'satu'        => $satu !== '' ? $satu : null,
                    'free1'       => $free1 !== '' ? $free1 : null,
                    'jam2'        => $jam2 !== '' ? $jam2 : null,
                    'dua'         => $dua !== '' ? $dua : null,
                    'free2'       => $free2 !== '' ? $free2 : null,
                    'jam3'        => $jam3 !== '' ? $jam3 : null,
                    'tiga'        => $tiga !== '' ? $tiga : null,
                    'free3'       => $free3 !== '' ? $free3 : null,
                    'jam4'        => $jam4 !== '' ? $jam4 : null,
                    'empat'       => $empat !== '' ? $empat : null,
                    'free4'       => $free4 !== '' ? $free4 : null,
                    'jam5'        => $jam5 !== '' ? $jam5 : null,
                    'lima'        => $lima !== '' ? $lima : null,
                    'free5'       => $free5 !== '' ? $free5 : null,
                    'jam6'        => $jam6 !== '' ? $jam6 : null,
                    'enam'        => $enam !== '' ? $enam : null,
                    'free6'       => $free6 !== '' ? $free6 : null,
                    'total'       => $total !== '' ? $total : null,
                    'total_free'  => $total_free !== '' ? $total_free : null,
                    'net'         => $net !== '' ? $net : null,
                    'created_by'  => \Auth::user()->uuid ?? null,
                    'edited_by'   => null,
                    'created_at'  => now(),
                    'updated_at'  => null,
                ];

                if (count($batch) >= $batchSize) {
                    DB::table('cgv_template')->insert($batch);
                    $inserted += count($batch);
                    $batch = [];
                }
            }

            if (!empty($batch)) {
                DB::table('cgv_template')->insert($batch);
                $inserted += count($batch);
            }

            $COLLATE = 'utf8mb4_unicode_ci';

            $cek_bioskop = <<<SQL
                    SELECT p.nama_bioskop
                    FROM (
                        SELECT 'CGV' AS kategori, nama_bioskop, nama_film, report_date AS tgl_tayang, jam1 AS jam_tayang, '1' AS `show`, concat(tipe_tiket,'-CGV') AS type_tiket, CAST(harga AS DECIMAL) AS harga, CAST(satu AS DECIMAL) AS jumlah, CONCAT(nama_bioskop, '-', tipe_tiket, '-', CAST(studio as DECIMAL)) AS studio, created_by
                        FROM cgv_template WHERE satu != '-'

                        UNION ALL
                        SELECT 'CGV', nama_bioskop, nama_film, report_date, jam2, '2', concat(tipe_tiket,'-CGV'), CAST(harga AS DECIMAL), CAST(dua AS DECIMAL), CONCAT(nama_bioskop, '-', tipe_tiket, '-', CAST(studio as DECIMAL)), created_by
                        FROM cgv_template WHERE dua != '-'

                        UNION ALL
                        SELECT 'CGV', nama_bioskop, nama_film, report_date, jam3, '3', concat(tipe_tiket,'-CGV'), CAST(harga AS DECIMAL), CAST(tiga AS DECIMAL), CONCAT(nama_bioskop, '-', tipe_tiket, '-', CAST(studio as DECIMAL)), created_by
                        FROM cgv_template WHERE tiga != '-'

                        UNION ALL
                        SELECT 'CGV', nama_bioskop, nama_film, report_date, jam4, '4', concat(tipe_tiket,'-CGV'), CAST(harga AS DECIMAL), CAST(empat AS DECIMAL), CONCAT(nama_bioskop, '-', tipe_tiket, '-', CAST(studio as DECIMAL)), created_by
                        FROM cgv_template WHERE empat != '-'

                        UNION ALL
                        SELECT 'CGV', nama_bioskop, nama_film, report_date, jam5, '5', concat(tipe_tiket,'-CGV'), CAST(harga AS DECIMAL), CAST(lima AS DECIMAL), CONCAT(nama_bioskop, '-', tipe_tiket, '-', CAST(studio as DECIMAL)), created_by
                        FROM cgv_template WHERE TRIM(lima) != '-' AND TRIM(lima) != '' AND lima IS NOT NULL

                        UNION ALL
                        SELECT 'CGV', nama_bioskop, nama_film, report_date, jam6, '6', concat(tipe_tiket,'-CGV'), CAST(harga AS DECIMAL), CAST(enam AS DECIMAL), CONCAT(nama_bioskop, '-', tipe_tiket, '-', CAST(studio as DECIMAL)), created_by
                        FROM cgv_template WHERE enam != '-'
                    ) p
                    LEFT JOIN kategori_bioskops kb
                        ON kb.name COLLATE {$COLLATE} = p.kategori COLLATE {$COLLATE}
                    LEFT JOIN master_bioskops mb
                        ON mb.nama_bioskop COLLATE {$COLLATE} = p.nama_bioskop COLLATE {$COLLATE}
                        AND mb.type COLLATE {$COLLATE} = kb.uuid COLLATE {$COLLATE}
                        -- AND p.kota = mb.kota
                    WHERE mb.uuid IS NULL
                    SQL;

            $notMapped_bioskop = DB::select(DB::raw($cek_bioskop));

            $cek_studio = <<<SQL
                        SELECT
                        distinct p.studio, kb.uuid AS kategori,
                        mb.kota AS kota,
                        mb.uuid AS nama_bioskop,tt.uuid AS type_tiket
                        FROM (
                            SELECT 'CGV' AS kategori, nama_bioskop, nama_film, report_date AS tgl_tayang, jam1 AS jam_tayang, '1' AS `show`, concat(tipe_tiket,'-CGV') AS type_tiket, CAST(harga AS DECIMAL) AS harga, CAST(satu AS DECIMAL) AS jumlah, CONCAT(nama_bioskop, '-', tipe_tiket, '-', CAST(studio as DECIMAL)) AS studio, created_by
                            FROM cgv_template WHERE satu != '-'

                            UNION ALL
                            SELECT 'CGV', nama_bioskop, nama_film, report_date, jam2, '2', concat(tipe_tiket,'-CGV'), CAST(harga AS DECIMAL), CAST(dua AS DECIMAL), CONCAT(nama_bioskop, '-', tipe_tiket, '-', CAST(studio as DECIMAL)), created_by
                            FROM cgv_template WHERE dua != '-'

                            UNION ALL
                            SELECT 'CGV', nama_bioskop, nama_film, report_date, jam3, '3', concat(tipe_tiket,'-CGV'), CAST(harga AS DECIMAL), CAST(tiga AS DECIMAL), CONCAT(nama_bioskop, '-', tipe_tiket, '-', CAST(studio as DECIMAL)), created_by
                            FROM cgv_template WHERE tiga != '-'

                            UNION ALL
                            SELECT 'CGV', nama_bioskop, nama_film, report_date, jam4, '4', concat(tipe_tiket,'-CGV'), CAST(harga AS DECIMAL), CAST(empat AS DECIMAL), CONCAT(nama_bioskop, '-', tipe_tiket, '-', CAST(studio as DECIMAL)), created_by
                            FROM cgv_template WHERE empat != '-'

                            UNION ALL
                            SELECT 'CGV', nama_bioskop, nama_film, report_date, jam5, '5', concat(tipe_tiket,'-CGV'), CAST(harga AS DECIMAL), CAST(lima AS DECIMAL), CONCAT(nama_bioskop, '-', tipe_tiket, '-', CAST(studio as DECIMAL)), created_by
                            FROM cgv_template WHERE TRIM(lima) != '-' AND TRIM(lima) != '' AND lima IS NOT NULL

                            UNION ALL
                            SELECT 'CGV', nama_bioskop, nama_film, report_date, jam6, '6', concat(tipe_tiket,'-CGV'), CAST(harga AS DECIMAL), CAST(enam AS DECIMAL), CONCAT(nama_bioskop, '-', tipe_tiket, '-', CAST(studio as DECIMAL)), created_by
                            FROM cgv_template WHERE enam != '-'
                        ) p
                        LEFT JOIN kategori_bioskops kb
                            ON kb.name COLLATE {$COLLATE} = p.kategori COLLATE {$COLLATE}
                        LEFT JOIN master_bioskops mb
                            ON mb.nama_bioskop COLLATE {$COLLATE} = p.nama_bioskop COLLATE {$COLLATE}
                            AND mb.type COLLATE {$COLLATE} = kb.uuid COLLATE {$COLLATE}
                            -- AND p.kota = mb.kota
                        LEFT JOIN type_tikets tt
                            ON tt.name COLLATE {$COLLATE} = REPLACE(p.type_tiket, '-CGV', '')
                            AND tt.kategori COLLATE {$COLLATE} = (SELECT kbb.uuid FROM kategori_bioskops kbb WHERE kbb.name = 'CGV')
                        LEFT JOIN kapasitas k
                            ON k.studio COLLATE {$COLLATE} = SUBSTRING_INDEX(p.studio, '-', -1)
                            AND k.nama_bioskop COLLATE {$COLLATE} = mb.uuid COLLATE {$COLLATE}
                            AND k.type_tiket COLLATE {$COLLATE} = tt.uuid COLLATE {$COLLATE} 
                            AND k.kategori COLLATE {$COLLATE} = kb.uuid COLLATE {$COLLATE}
                        WHERE k.studio is null
                    SQL;

            $notMapped_studio = DB::select(DB::raw($cek_studio));

            DB::commit();

            if (count($notMapped_bioskop) > 0) {
                // 3) Siapkan token & cache data untuk diunduh
                // $token = (string) Str::uuid();
                // Cache::put("xxi_err:$token", $notMapped, now()->addMinutes(30));

                // return response()->json([
                //     'status'       => 'failed',
                //     'message'      => 'Validasi gagal: ada kota/nama_bioskop yang belum terdaftar di master.',
                //     'download_url' => route('pelaporan.upload.xxi.errors', ['token' => $token]),
                //     'count'        => count($notMapped),
                // ], 200);

                $msgList = [];
                foreach ($notMapped_bioskop as $row) {
                    $msgList[] = "{$row->kota} - {$row->nama_bioskop}";
                }

                // Lempar exception dengan daftar data error
                throw new \Exception(
                    "Validasi gagal, berikut bioskop yang belum terdaftar di master:\r\n" . implode("\n", $msgList)
                );
            }

            if (count($notMapped_studio) > 0) {
                // 3) Siapkan token & cache data untuk diunduh
                // $token = (string) Str::uuid();
                // Cache::put("xxi_err:$token", $notMapped_studio, now()->addMinutes(30));

                // return response()->json([
                //     'status'       => 'failed',
                //     'message'      => 'Validasi gagal: ada studio yang belum terdaftar di master.',
                //     'download_url' => route('pelaporan.upload.xxi.errors', ['token' => $token]),
                //     'count'        => count($notMapped_studio),
                // ], 200);

                $msgList = [];
                foreach ($notMapped_studio as $row) {
                    $msgList[] = "{$row->studio} - {$row->kategori} - {$row->kota} - {$row->nama_bioskop} - {$row->type_tiket}";
                }

                // Lempar exception dengan daftar data error
                throw new \Exception(
                    "Validasi gagal, berikut studio yang belum terdaftar di master:\r\n" . implode("\n", $msgList)
                );
            }

            $pelaporanTable = (new \App\Models\Pelaporan)->getTable(); // biasanya 'pelaporans'
            $user = Auth::user()->uuid;
            $COLLATE = 'utf8mb4_unicode_ci'; // ganti ke 'utf8mb4_0900_ai_ci' jika itu standar DB kamu

            $sqlInsert = <<<SQL
            INSERT INTO {$pelaporanTable} 
            (uuid, kategori, kota, nama_bioskop, nama_film, tgl_tayang, jam_tayang, `show`, type_tiket, harga, jumlah, gross, tax, net, studio, created_by, created_at)
            SELECT
                UUID() AS uuid,
                kb.uuid AS kategori,
                mb.kota AS kota,
                mb.uuid AS nama_bioskop,
                p.nama_film AS nama_film,
                p.tgl_tayang,
                p.jam_tayang,
                p.`show`,
                tt.uuid AS type_tiket,
                p.harga,
                p.jumlah,
                (p.harga * p.jumlah) AS gross,
                0 AS tax,  -- Kalau belum ada tax, bisa isi 0 atau ambil dari sumber lain
                (p.harga * p.jumlah - 0) AS net,  -- Kurangi dengan tax kalau ada
                k.uuid AS studio,
                '{$user}' AS created_by, -- ganti jika mau pakai Auth::user()->uuid
                NOW() AS created_at
            FROM (
                SELECT 'CGV' AS kategori, nama_bioskop, nama_film, report_date AS tgl_tayang, jam1 AS jam_tayang, '1' AS `show`, concat(tipe_tiket,'-CGV') AS type_tiket, CAST(harga AS DECIMAL) AS harga, CAST(satu AS DECIMAL) AS jumlah, CONCAT(nama_bioskop, '-', tipe_tiket, '-', CAST(studio as DECIMAL)) AS studio, created_by
                FROM cgv_template WHERE satu != '-'

                UNION ALL
                SELECT 'CGV', nama_bioskop, nama_film, report_date, jam2, '2', concat(tipe_tiket,'-CGV'), CAST(harga AS DECIMAL), CAST(dua AS DECIMAL), CONCAT(nama_bioskop, '-', tipe_tiket, '-', CAST(studio as DECIMAL)), created_by
                FROM cgv_template WHERE dua != '-'

                UNION ALL
                SELECT 'CGV', nama_bioskop, nama_film, report_date, jam3, '3', concat(tipe_tiket,'-CGV'), CAST(harga AS DECIMAL), CAST(tiga AS DECIMAL), CONCAT(nama_bioskop, '-', tipe_tiket, '-', CAST(studio as DECIMAL)), created_by
                FROM cgv_template WHERE tiga != '-'

                UNION ALL
                SELECT 'CGV', nama_bioskop, nama_film, report_date, jam4, '4', concat(tipe_tiket,'-CGV'), CAST(harga AS DECIMAL), CAST(empat AS DECIMAL), CONCAT(nama_bioskop, '-', tipe_tiket, '-', CAST(studio as DECIMAL)), created_by
                FROM cgv_template WHERE empat != '-'

                UNION ALL
                SELECT 'CGV', nama_bioskop, nama_film, report_date, jam5, '5', concat(tipe_tiket,'-CGV'), CAST(harga AS DECIMAL), CAST(lima AS DECIMAL), CONCAT(nama_bioskop, '-', tipe_tiket, '-', CAST(studio as DECIMAL)), created_by
                FROM cgv_template WHERE TRIM(lima) != '-' AND TRIM(lima) != '' AND lima IS NOT NULL

                UNION ALL
                SELECT 'CGV', nama_bioskop, nama_film, report_date, jam6, '6', concat(tipe_tiket,'-CGV'), CAST(harga AS DECIMAL), CAST(enam AS DECIMAL), CONCAT(nama_bioskop, '-', tipe_tiket, '-', CAST(studio as DECIMAL)), created_by
                FROM cgv_template WHERE enam != '-'
            ) p
            LEFT JOIN kategori_bioskops kb
                ON kb.name COLLATE {$COLLATE} = p.kategori COLLATE {$COLLATE}
            LEFT JOIN master_bioskops mb
                ON mb.nama_bioskop COLLATE {$COLLATE} = p.nama_bioskop COLLATE {$COLLATE}
                AND mb.type COLLATE {$COLLATE} = kb.uuid COLLATE {$COLLATE}
                -- AND mb.kota COLLATE {$COLLATE} = p.kota COLLATE {$COLLATE}
            LEFT JOIN type_tikets tt
                ON tt.name COLLATE {$COLLATE} = REPLACE(p.type_tiket, '-CGV', '')
                AND tt.kategori COLLATE {$COLLATE} = (SELECT kbb.uuid FROM kategori_bioskops kbb WHERE kbb.name COLLATE {$COLLATE} = 'CGV' COLLATE {$COLLATE})
            LEFT JOIN kapasitas k
                ON CAST(k.studio AS CHAR) COLLATE {$COLLATE} = SUBSTRING_INDEX(p.studio, '-', -1) COLLATE {$COLLATE}
                AND k.nama_bioskop COLLATE {$COLLATE} = mb.uuid COLLATE {$COLLATE}
                AND k.type_tiket COLLATE {$COLLATE} = tt.uuid COLLATE {$COLLATE}
                AND k.kategori COLLATE {$COLLATE} = kb.uuid COLLATE {$COLLATE}
            order by tgl_tayang
            SQL;

            // jalankan dan dapatkan jumlah baris yang masuk
            $affected = DB::affectingStatement(DB::raw($sqlInsert));

            // jika lolos validasi
            return response()->json([
                'status'   => 'success',
                'message'  => "Selesai. {$inserted} baris tersimpan. Validasi mapping master OK.",
                'inserted' => $inserted,
            ]);

        } catch (\Throwable $e) {
            DB::rollBack();
            report($e);
            return response()->json([
                'status'  => 'failed',
                'message' => 'Import gagal: '.$e->getMessage(),
            ], 500);
        }
    }

    public function downloadCgvErrors(string $token)
    {
        $rows = Cache::get("cgv_err:$token");
        if (!$rows) {
            abort(404, 'Data error tidak ditemukan atau sudah kedaluwarsa.');
        }

        // Buat XLSX sederhana: kolom kota, nama_bioskop
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Mapping Error');

        // Header
        $sheet->setCellValue('A1', 'kota');
        $sheet->setCellValue('B1', 'nama_bioskop');

        // Data
        $r = 2;
        foreach ($rows as $obj) {
            // $obj adalah stdClass dari DB::select
            $sheet->setCellValue("A{$r}", $obj->kota ?? '');
            $sheet->setCellValue("B{$r}", $obj->nama_bioskop ?? '');
            $r++;
        }

        // Stream download
        $filename = 'mapping_error_cgv_'.now()->format('Ymd_His').'.xlsx';
        $writer = new Xlsx($spreadsheet);

        return new StreamedResponse(function () use ($writer) {
            $writer->save('php://output');
        }, 200, [
            'Content-Type'        => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
            'Cache-Control'       => 'max-age=0, no-cache, no-store, must-revalidate',
            'Pragma'              => 'public',
        ]);
    }

    public function uploadSAMS(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls|max:20480',
        ], [
            'file.required' => 'File wajib diunggah.',
            'file.mimes'    => 'Format file harus .xlsx atau .xls.',
            'file.max'      => 'Ukuran file maksimal 20MB.',
        ]);

        try {
            // optional: pastikan tidak timeout/kehabisan memori untuk file besar
            @set_time_limit(0);
            @ini_set('memory_limit', '512M');

            $file = $request->file('file');

            // Kamu boleh simpan dulu, tapi untuk sinkron cukup load dari tmp path
            $spreadsheet = IOFactory::load($file->getPathname());
            $sheet       = $spreadsheet->getActiveSheet();
            // toArray(null, true, true, true) => key kolom: 'A','B',dst
            $rows        = $sheet->toArray(null, true, true, true);

            if (count($rows) <= 1) {
                return response()->json([
                    'status'  => 'failed',
                    'message' => 'Sheet kosong / header tidak ditemukan.',
                ], 422);
            }

            DB::table('sams_template')->truncate();

            $batch     = [];
            $batchSize = 1000;
            $inserted  = 0;
            $today     = now()->format('Y-m-d');

            DB::beginTransaction();

            foreach ($rows as $idx => $cols) {
                if ($idx === 1) continue; // skip header baris 1

                $namaFilm       = trim((string)($cols['A'] ?? ''));
                $namaBios       = trim((string)($cols['B'] ?? ''));
                $studio         = trim((string)($cols['C'] ?? ''));
                $reportDate     = trim((string)($cols['D'] ?? ''));
                $jam            = trim((string)($cols['E'] ?? ''));
                $harga          = trim((string)($cols['F'] ?? ''));
                $status         = trim((string)($cols['G'] ?? ''));
                $approval       = trim((string)($cols['H'] ?? ''));
                $net            = trim((string)($cols['I'] ?? ''));
                $total          = trim((string)($cols['J'] ?? ''));
                $total_paid     = trim((string)($cols['K'] ?? ''));
                $total_voucher  = trim((string)($cols['L'] ?? ''));
                $total_free     = trim((string)($cols['M'] ?? ''));

                // Normalisasi tanggal
                try {
                    if ($reportDate === '') {
                        $reportDate = $today;
                    } elseif (is_numeric($reportDate)) {
                        // Excel serial date → 1899-12-30
                        $reportDate = \Carbon\Carbon::create(1899,12,30)->addDays((int)$reportDate)->format('Y-m-d');
                    } else {
                        $reportDate = \Carbon\Carbon::parse($reportDate)->format('Y-m-d');
                    }
                } catch (\Throwable $e) {
                    $reportDate = $today;
                }

                // Row minimal: nama_film & nama_bioskop ada
                if ($namaFilm === '' && $namaBios === '') {
                    continue;
                }
                
                $batch[] = [
                    'uuid'          => Uuid::generate(),
                    'report_date'   => $reportDate,
                    'nama_film'     => $namaFilm !== '' ? mb_strtoupper($namaFilm) : null,
                    'nama_bioskop'  => $namaBios !== '' ? mb_strtoupper($namaBios) : null,
                    'studio'        => $studio !== '' ? $studio : null,
                    'jam'           => $jam !== '' ? $jam : null,
                    'total'         => $total !== '' ? $total : null,
                    'total_paid'    => $total_paid !== '' ? $total_paid : null,
                    'total_voucher' => $total_voucher !== '' ? $total_voucher : null,
                    'total_free'    => $total_free !== '' ? $total_free : null,
                    'harga'         => $harga !== '' ? $harga : null,
                    'net'           => $net !== '' ? $net : null,
                    'status'        => $status !== '' ? $status : null,
                    'approval'      => $approval !== '' ? $approval : null,
                    'created_by'    => \Auth::user()->uuid ?? null,
                    'edited_by'     => null,
                    'created_at'    => now(),
                    'updated_at'    => null,
                ];

                if (count($batch) >= $batchSize) {
                    DB::table('sams_template')->insert($batch);
                    $inserted += count($batch);
                    $batch = [];
                }
            }

            if (!empty($batch)) {
                DB::table('sams_template')->insert($batch);
                $inserted += count($batch);
            }

            $COLLATE = 'utf8mb4_unicode_ci';

            $cek_bioskop = <<<SQL
                    SELECT p.nama_bioskop
                    FROM (
                        SELECT 'SAMS STUDIOS' AS kategori, nama_bioskop, nama_film, report_date AS tgl_tayang, jam AS jam_tayang, '1' AS `show`, 'REGULAR' AS type_tiket, replace(replace(harga, 'Rp. ', ''), '.', '') AS harga, CAST(total_paid - total_voucher - total_free AS DECIMAL) AS jumlah, CONCAT(nama_bioskop, '-REGULAR-', replace(studio,'Studio ', '')) AS studio, created_by
                        FROM sams_template WHERE total_paid != '-'

                        UNION ALL
                        SELECT 'SAMS STUDIOS', nama_bioskop, nama_film, report_date, jam, '1', 'BOGOF', replace(replace(harga, 'Rp. ', ''), '.', '') as harga, CAST(total_voucher * 2 AS DECIMAL) AS jumlah, CONCAT(nama_bioskop, '-BOGOF-', replace(studio,'Studio ', '')) AS studio, created_by
                        FROM sams_template WHERE total_voucher != '-'
                    ) p
                    LEFT JOIN kategori_bioskops kb
                        ON kb.name COLLATE {$COLLATE} = p.kategori COLLATE {$COLLATE}
                    LEFT JOIN master_bioskops mb
                        ON mb.nama_bioskop COLLATE {$COLLATE} = p.nama_bioskop COLLATE {$COLLATE}
                        AND mb.type COLLATE {$COLLATE} = kb.uuid COLLATE {$COLLATE}
                        -- AND p.kota = mb.kota
                    WHERE mb.uuid IS NULL
                    SQL;

            $notMapped_bioskop = DB::select(DB::raw($cek_bioskop));

            $cek_studio = <<<SQL
                        SELECT
                        distinct p.studio, kb.uuid AS kategori,
                        mb.kota AS kota,
                        mb.uuid AS nama_bioskop,tt.uuid AS type_tiket
                        FROM (
                            SELECT 'SAMS STUDIOS' AS kategori, nama_bioskop, nama_film, report_date AS tgl_tayang, jam AS jam_tayang, '1' AS `show`, 'REGULAR' AS type_tiket, replace(replace(harga, 'Rp. ', ''), '.', '') AS harga, CAST(total_paid - total_voucher - total_free AS DECIMAL) AS jumlah, CONCAT(nama_bioskop, '-REGULAR-', replace(studio,'Studio ', '')) AS studio, created_by
                            FROM sams_template WHERE total_paid != '-'

                            UNION ALL
                            SELECT 'SAMS STUDIOS', nama_bioskop, nama_film, report_date, jam, '1', 'BOGOF', replace(replace(harga, 'Rp. ', ''), '.', '') as harga, CAST(total_voucher * 2 AS DECIMAL) AS jumlah, CONCAT(nama_bioskop, '-BOGOF-', replace(studio,'Studio ', '')) AS studio, created_by
                            FROM sams_template WHERE total_voucher != '-'
                        ) p
                        LEFT JOIN kategori_bioskops kb
                            ON kb.name COLLATE {$COLLATE} = p.kategori COLLATE {$COLLATE}
                        LEFT JOIN master_bioskops mb
                            ON mb.nama_bioskop COLLATE {$COLLATE} = p.nama_bioskop COLLATE {$COLLATE}
                            AND mb.type COLLATE {$COLLATE} = kb.uuid COLLATE {$COLLATE}
                            -- AND p.kota = mb.kota
                        LEFT JOIN type_tikets tt
                            ON tt.name COLLATE {$COLLATE} = p.type_tiket COLLATE {$COLLATE}
                            AND tt.kategori COLLATE {$COLLATE} = (SELECT kbb.uuid FROM kategori_bioskops kbb WHERE kbb.name = 'SAMS STUDIOS')
                        LEFT JOIN kapasitas k
                            ON k.studio COLLATE {$COLLATE} = SUBSTRING_INDEX(p.studio, '-', -1)
                            AND k.nama_bioskop COLLATE {$COLLATE} = mb.uuid COLLATE {$COLLATE}
                            AND k.type_tiket COLLATE {$COLLATE} = tt.uuid COLLATE {$COLLATE} 
                            AND k.kategori COLLATE {$COLLATE} = kb.uuid COLLATE {$COLLATE}
                        WHERE k.studio is null
                    SQL;

            $notMapped_studio = DB::select(DB::raw($cek_studio));

            DB::commit();

            if (count($notMapped_bioskop) > 0) {
                // 3) Siapkan token & cache data untuk diunduh
                // $token = (string) Str::uuid();
                // Cache::put("xxi_err:$token", $notMapped, now()->addMinutes(30));

                // return response()->json([
                //     'status'       => 'failed',
                //     'message'      => 'Validasi gagal: ada kota/nama_bioskop yang belum terdaftar di master.',
                //     'download_url' => route('pelaporan.upload.xxi.errors', ['token' => $token]),
                //     'count'        => count($notMapped),
                // ], 200);

                $msgList = [];
                foreach ($notMapped_bioskop as $row) {
                    $msgList[] = "{$row->kota} - {$row->nama_bioskop}";
                }

                // Lempar exception dengan daftar data error
                throw new \Exception(
                    "Validasi gagal, berikut bioskop yang belum terdaftar di master:\r\n" . implode("\n", $msgList)
                );
            }

            if (count($notMapped_studio) > 0) {
                // 3) Siapkan token & cache data untuk diunduh
                // $token = (string) Str::uuid();
                // Cache::put("xxi_err:$token", $notMapped_studio, now()->addMinutes(30));

                // return response()->json([
                //     'status'       => 'failed',
                //     'message'      => 'Validasi gagal: ada studio yang belum terdaftar di master.',
                //     'download_url' => route('pelaporan.upload.xxi.errors', ['token' => $token]),
                //     'count'        => count($notMapped_studio),
                // ], 200);

                $msgList = [];
                foreach ($notMapped_studio as $row) {
                    $msgList[] = "{$row->studio} - {$row->kategori} - {$row->kota} - {$row->nama_bioskop} - {$row->type_tiket}";
                }

                // Lempar exception dengan daftar data error
                throw new \Exception(
                    "Validasi gagal, berikut studio yang belum terdaftar di master:\r\n" . implode("\n", $msgList)
                );
            }

            $pelaporanTable = (new \App\Models\Pelaporan)->getTable(); // biasanya 'pelaporans'
            $user = Auth::user()->uuid;
            $COLLATE = 'utf8mb4_unicode_ci'; // ganti ke 'utf8mb4_0900_ai_ci' jika itu standar DB kamu

            $sqlInsert = <<<SQL
            INSERT INTO {$pelaporanTable} 
            (uuid, kategori, kota, nama_bioskop, nama_film, tgl_tayang, jam_tayang, `show`, type_tiket, harga, jumlah, gross, tax, net, studio, created_by, created_at)
            SELECT
                UUID() AS uuid,
                kb.uuid AS kategori,
                mb.kota AS kota,
                mb.uuid AS nama_bioskop,
                p.nama_film AS nama_film,
                p.tgl_tayang,
                p.jam_tayang,
                p.`show`,
                tt.uuid AS type_tiket,
                p.harga,
                p.jumlah,
                (p.harga * p.jumlah) AS gross,
                0 AS tax,  -- Kalau belum ada tax, bisa isi 0 atau ambil dari sumber lain
                (p.harga * p.jumlah - 0) AS net,  -- Kurangi dengan tax kalau ada
                k.uuid AS studio,
                '{$user}' AS created_by, -- ganti jika mau pakai Auth::user()->uuid
                NOW() AS created_at
            FROM (
                SELECT 'SAMS STUDIOS' AS kategori, nama_bioskop, nama_film, report_date AS tgl_tayang, jam AS jam_tayang, '1' AS `show`, 'REGULAR' AS type_tiket, replace(replace(harga, 'Rp. ', ''), '.', '') AS harga, CAST(COALESCE(NULLIF(total_paid,    '-'),'0') - COALESCE(NULLIF(total_voucher, '-'),'0') - COALESCE(NULLIF(total_free,    '-'),'0') AS DECIMAL(18,2)) AS jumlah, CONCAT(nama_bioskop, '-REGULAR-', replace(studio,'Studio ', '')) AS studio, created_by
                FROM sams_template WHERE total_paid != '-'

                UNION ALL
                SELECT 'SAMS STUDIOS', nama_bioskop, nama_film, report_date, jam, '1', 'BOGOF', replace(replace(harga, 'Rp. ', ''), '.', '') as harga, CAST(COALESCE(NULLIF(total_voucher,    '-'),'0') * 2 AS DECIMAL) AS jumlah, CONCAT(nama_bioskop, '-BOGOF-', replace(studio,'Studio ', '')) AS studio, created_by
                FROM sams_template WHERE total_voucher != '-'
            ) p
            LEFT JOIN kategori_bioskops kb
                ON kb.name COLLATE {$COLLATE} = p.kategori COLLATE {$COLLATE}
            LEFT JOIN master_bioskops mb
                ON mb.nama_bioskop COLLATE {$COLLATE} = p.nama_bioskop COLLATE {$COLLATE}
                AND mb.type COLLATE {$COLLATE} = kb.uuid COLLATE {$COLLATE}
                -- AND mb.kota COLLATE {$COLLATE} = p.kota COLLATE {$COLLATE}
            LEFT JOIN type_tikets tt
                ON tt.name COLLATE {$COLLATE} = p.type_tiket COLLATE {$COLLATE}
                AND tt.kategori COLLATE {$COLLATE} = (SELECT kbb.uuid FROM kategori_bioskops kbb WHERE kbb.name COLLATE {$COLLATE} = 'SAMS STUDIOS' COLLATE {$COLLATE})
            LEFT JOIN kapasitas k
                ON CAST(k.studio AS CHAR) COLLATE {$COLLATE} = SUBSTRING_INDEX(p.studio, '-', -1) COLLATE {$COLLATE}
                AND k.nama_bioskop COLLATE {$COLLATE} = mb.uuid COLLATE {$COLLATE}
                AND k.type_tiket COLLATE {$COLLATE} = tt.uuid COLLATE {$COLLATE}
                AND k.kategori COLLATE {$COLLATE} = kb.uuid COLLATE {$COLLATE}
            order by tgl_tayang
            SQL;

            // jalankan dan dapatkan jumlah baris yang masuk
            $affected = DB::affectingStatement(DB::raw($sqlInsert));

            // jika lolos validasi
            return response()->json([
                'status'   => 'success',
                'message'  => "Selesai. {$inserted} baris tersimpan. Validasi mapping master OK.",
                'inserted' => $inserted,
            ]);

        } catch (\Throwable $e) {
            DB::rollBack();
            report($e);
            return response()->json([
                'status'  => 'failed',
                'message' => 'Import gagal: '.$e->getMessage(),
            ], 500);
        }
    }

    public function downloadSamsErrors(string $token)
    {
        $rows = Cache::get("sams_err:$token");
        if (!$rows) {
            abort(404, 'Data error tidak ditemukan atau sudah kedaluwarsa.');
        }

        // Buat XLSX sederhana: kolom kota, nama_bioskop
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Mapping Error');

        // Header
        $sheet->setCellValue('A1', 'kota');
        $sheet->setCellValue('B1', 'nama_bioskop');

        // Data
        $r = 2;
        foreach ($rows as $obj) {
            // $obj adalah stdClass dari DB::select
            $sheet->setCellValue("A{$r}", $obj->kota ?? '');
            $sheet->setCellValue("B{$r}", $obj->nama_bioskop ?? '');
            $r++;
        }

        // Stream download
        $filename = 'mapping_error_sams_'.now()->format('Ymd_His').'.xlsx';
        $writer = new Xlsx($spreadsheet);

        return new StreamedResponse(function () use ($writer) {
            $writer->save('php://output');
        }, 200, [
            'Content-Type'        => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
            'Cache-Control'       => 'max-age=0, no-cache, no-store, must-revalidate',
            'Pragma'              => 'public',
        ]);
    }

}
