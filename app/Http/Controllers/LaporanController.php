<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Models\Pelaporan;
use App\Models\TypeTiket;
use App\Models\KategoriBioskop;
use App\Models\MasterBioskop;
use App\Models\Laporan;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

use Auth;
use DataTables;
use DB;
use URL;
use Helper;
use Image;
use Response;

class LaporanController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $bioskop_kategori = KategoriBioskop::all()->pluck('name', 'uuid')->toArray();
        $bioskop_kategori = ['ALL' => 'Semua ...'] + $bioskop_kategori;
        $nama_bioskop = MasterBioskop::all()->pluck('nama_bioskop', 'uuid');
        $kota = MasterBioskop::selectRaw('Distinct kota')->pluck('kota', 'kota');
        $type_tiket = TypeTiket::all()->pluck('name', 'uuid');
        $nama_film = Pelaporan::selectRaw('Distinct nama_film')->pluck('nama_film', 'nama_film');
        return view('laporan.index', compact('bioskop_kategori', 'nama_bioskop', 'kota','type_tiket', 'nama_film'));
    }

    // public function listData(Request $request){
    //     $where_conditions = [];
    //     $params = [];

    //     if ($request->nama_film != 'ALL') {
    //         $where_conditions[] = "nama_film = ?";
    //         $params[] = $request->nama_film;
    //     }

    //     if ($request->tgl_mulai != '' && $request->tgl_akhir != '') {
    //         $where_conditions[] = "tgl_tayang BETWEEN ? AND ?";
    //         $params[] = $request->tgl_mulai;
    //         $params[] = $request->tgl_akhir;
    //     }

    //     if ($request->bioskop_kategori != 'ALL') {
    //         $bioskop_kategori = $request->bioskop_kategori;
    //         if ($bioskop_kategori) {
    //             $where_conditions[] = "kategori = ?";
    //             $params[] = $bioskop_kategori;
    //         }
    //     }

    //     if ($request->kota != 'ALL') {
    //         $where_conditions[] = "kota = ?";
    //         $params[] = $request->kota;
    //     }

    //     if ($request->nama_bioskop != 'ALL') {
    //         $nama_bioskop = $request->nama_bioskop;
    //         if ($nama_bioskop) {
    //             $where_conditions[] = "nama_bioskop = ?";
    //             $params[] = $nama_bioskop;
    //         }
    //     }

    //     if ($request->type_tiket != 'ALL') {
    //         $type_tiket = $request->type_tiket;
    //         if ($type_tiket) {
    //             $where_conditions[] = "type_tiket = ?";
    //             $params[] = $type_tiket;
    //         }
    //     }

    //     // Gabungkan where conditions menjadi string dengan " AND "
    //     $where_query = implode(' AND ', $where_conditions);
    //     // dd($params);

    //     $result = DB::select("select * from pelaporans where $where_query", $params);

    //     return Datatables::of($result)
    //             ->addIndexColumn()
    //             ->editColumn('kategori', function ($row){
    //                 return $row->Categories->name;
    //             })
    //             ->editColumn('nama_bioskop', function ($row){
    //                 return $row->Cinemas->nama_bioskop;
    //             })
    //             ->editColumn('type_tiket', function ($row){
    //                 return $row->TypeTiket->name;
    //             })
    //             ->editColumn('jam_tayang', function ($row) {
    //                 return \Carbon\Carbon::parse($row->jam_tayang)->format('H:i'); // Format hh:mm
    //             })
    //             ->removeColumn('id')
    //             ->removeColumn('uuid')
    //             ->rawColumns(['action','type'])
    //             ->make(true);
    // }

    public function listData(Request $request) {
        // dd($request->all());

        // Ambil semua data dengan relasi yang dibutuhkan
        $query = Pelaporan::with(['categories', 'cinemas', 'typeTiket']);
    
        // Filter berdasarkan input user
        if ($request->nama_film != 'ALL') {
            $query->where('nama_film', $request->nama_film);
        }
    
        if (!empty($request->tgl_mulai) && !empty($request->tgl_akhir)) {
            $query->whereBetween('tgl_tayang', [$request->tgl_mulai, $request->tgl_akhir]);
        }
    
        if ($request->bioskop_kategori != 'ALL') {
            $query->where('kategori', $request->bioskop_kategori);
        }
    
        if ($request->kota != 'ALL') {
            $query->where('kota', $request->kota);
        }
    
        if ($request->nama_bioskop != 'ALL') {
            $query->where('nama_bioskop', $request->nama_bioskop);
        }
    
        if ($request->type_tiket != 'ALL') {
            $query->where('type_tiket', $request->type_tiket);
        }
    
        // Ambil data setelah filtering
        $data = $query->get();

        // dd($data);
    
        // Kirim data ke Datatables
        return Datatables::of($data)
            ->addIndexColumn()
            ->editColumn('kategori', function ($row) {
                return $row->categories ? $row->categories->name : '-';
            })
            ->editColumn('nama_bioskop', function ($row) {
                return $row->cinemas ? $row->cinemas->nama_bioskop : '-';
            })
            ->editColumn('type_tiket', function ($row) {
                return $row->typeTiket ? $row->typeTiket->name : '-';
            })
            ->editColumn('jam_tayang', function ($row) {
                return $row->jam_tayang ? \Carbon\Carbon::parse($row->jam_tayang)->format('H:i') : '-';
            })
            ->editColumn('studio', function ($row){
                return $row->Studio->studio ?? null;
            })
            ->editColumn('created_by', function($row){
                return $row->userCreate->name;
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
            ->make(true);
    }

    public function summaryListData(Request $request){
        // dd($request->all());
        $summary = Pelaporan::with(['categories', 'cinemas', 'typeTiket'])
                    ->select('kategori', DB::raw('SUM(jumlah) as jumlah, SUM(gross) as gross, SUM(tax) as tax, SUM(net) as net, SUM(net/2) as share'));
    
        // Filter berdasarkan input user
        if ($request->nama_film != 'ALL') {
            $summary->where('nama_film', $request->nama_film);
        }
    
        if (!empty($request->tgl_mulai) && !empty($request->tgl_akhir)) {
            $summary->whereBetween('tgl_tayang', [$request->tgl_mulai, $request->tgl_akhir]);
        }
    
        if ($request->bioskop_kategori != 'ALL') {
            $summary->where('kategori', $request->bioskop_kategori);
        }
    
        if ($request->kota != 'ALL') {
            $summary->where('kota', $request->kota);
        }
    
        if ($request->nama_bioskop != 'ALL') {
            $summary->where('nama_bioskop', $request->nama_bioskop);
        }
    
        if ($request->type_tiket != 'ALL') {
            $summary->where('type_tiket', $request->type_tiket);
        }

        $data_summary = $summary->groupBy('kategori')
                            ->orderBy('jumlah', 'DESC')
                            ->get();

        // dd($data_summary);

        // return response()->json($data_summary);
        return Datatables::of($data_summary)
        ->addIndexColumn()
        // ->editColumn('tgl_tayang', function($row){
        //     return \Carbon\Carbon::parse($row->tgl_tayang)->format('d-m-Y'); // Format hh:mm
        // })
        ->editColumn('kategori', function ($row){
            return $row->Categories->name ?? null;
        })
        ->editColumn('jumlah',function($row){
            return $row->jumlah ? number_format($row->jumlah) : '' ;
        })
        ->editColumn('gross',function($row){
            return $row->gross ? number_format($row->gross) : '' ;
        })
        ->editColumn('tax',function($row){
            return $row->tax ? number_format($row->tax) : '' ;
        })
        ->editColumn('net',function($row){
            return $row->net ? number_format($row->net) : '' ;
        })
        ->editColumn('share',function($row){
            return $row->share ? number_format($row->share) : '' ;
        })
        ->editColumn('royalty',function(){
            return '1.5%';
        })
        ->editColumn('total',function($row){
            $royalty = $row->share * 0.015;
            $total = $row->net - $row->share - $royalty;
            return $total ? number_format($total) : '' ;
        })
        ->make(true);
    }

    public function detailExport(Request $request)
    {
        $nama_film = $request->query('nama_film', '');
        $tgl_mulai = $request->query('tgl_mulai', '');
        $tgl_akhir = $request->query('tgl_akhir', '');
        $bioskop_kategori = $request->query('bioskop_kategori', '');
        $kota = $request->query('kota', '');
        $nama_bioskop = $request->query('nama_bioskop', '');
        $type_tiket = $request->query('type_tiket', '');

        // Build dynamic WHERE clause with bindings
        $where = [];
        $bindings = [];

        if ($nama_film !== '' && strtoupper($nama_film) !== 'ALL') {
            $where[] = 'p.nama_film = ?';
            $bindings[] = $nama_film;
        }

        if ($tgl_mulai !== '' && $tgl_akhir !== '') {
            $where[] = 'p.tgl_tayang BETWEEN ? AND ?';
            $bindings[] = $tgl_mulai;
            $bindings[] = $tgl_akhir;
        }

        if ($bioskop_kategori !== '' && strtoupper($bioskop_kategori) !== 'ALL') {
            $where[] = 'p.kategori = ?';
            $bindings[] = $bioskop_kategori;
        }

        if ($kota !== '' && strtoupper($kota) !== 'ALL') {
            $where[] = 'mb.kota = ?';
            $bindings[] = $kota;
        }

        if ($nama_bioskop !== '' && strtoupper($nama_bioskop) !== 'ALL') {
            $where[] = 'p.nama_bioskop = ?';
            $bindings[] = $nama_bioskop;
        }

        if ($type_tiket !== '' && strtoupper($type_tiket) !== 'ALL') {
            $where[] = 'p.type_tiket = ?';
            $bindings[] = $type_tiket;
        }

        $whereSql = '';
        if (!empty($where)) {
            $whereSql = 'WHERE ' . implode(' AND ', $where);
        }

        $sql = "
            SELECT
                p.tgl_tayang,
                kb.name,
                mb.kota,
                mb.nama_bioskop,
                k.studio,
                k.kapasitas,
                CAST(COALESCE(SUM(CASE WHEN p.show = 1 THEN p.jumlah END), 0) AS UNSIGNED) AS S1,
                CAST(COALESCE(SUM(CASE WHEN p.show = 2 THEN p.jumlah END), 0) AS UNSIGNED) AS S2,
                CAST(COALESCE(SUM(CASE WHEN p.show = 3 THEN p.jumlah END), 0) AS UNSIGNED) AS S3,
                CAST(COALESCE(SUM(CASE WHEN p.show = 4 THEN p.jumlah END), 0) AS UNSIGNED) AS S4,
                CAST(COALESCE(SUM(CASE WHEN p.show = 5 THEN p.jumlah END), 0) AS UNSIGNED) AS S5,
                CAST(COALESCE(SUM(CASE WHEN p.show = 6 THEN p.jumlah END), 0) AS UNSIGNED) AS S6,
                CAST(COALESCE(SUM(CASE WHEN p.show = 7 THEN p.jumlah END), 0) AS UNSIGNED) AS S7,
                CAST(
                    COALESCE(SUM(CASE WHEN p.show = 1 THEN p.jumlah END), 0) +
                    COALESCE(SUM(CASE WHEN p.show = 2 THEN p.jumlah END), 0) +
                    COALESCE(SUM(CASE WHEN p.show = 3 THEN p.jumlah END), 0) +
                    COALESCE(SUM(CASE WHEN p.show = 4 THEN p.jumlah END), 0) +
                    COALESCE(SUM(CASE WHEN p.show = 5 THEN p.jumlah END), 0) +
                    COALESCE(SUM(CASE WHEN p.show = 6 THEN p.jumlah END), 0) +
                    COALESCE(SUM(CASE WHEN p.show = 7 THEN p.jumlah END), 0)
                AS UNSIGNED) AS Total,
                FORMAT(p.harga, 2) AS harga,
                FORMAT(
                    (
                        COALESCE(SUM(CASE WHEN p.show = 1 THEN p.jumlah END), 0) +
                        COALESCE(SUM(CASE WHEN p.show = 2 THEN p.jumlah END), 0) +
                        COALESCE(SUM(CASE WHEN p.show = 3 THEN p.jumlah END), 0) +
                        COALESCE(SUM(CASE WHEN p.show = 4 THEN p.jumlah END), 0) +
                        COALESCE(SUM(CASE WHEN p.show = 5 THEN p.jumlah END), 0) +
                        COALESCE(SUM(CASE WHEN p.show = 6 THEN p.jumlah END), 0) +
                        COALESCE(SUM(CASE WHEN p.show = 7 THEN p.jumlah END), 0)
                    ) * p.harga, 2
                ) AS gross,
                mb.pajak as pajak_persen,
                FORMAT((COALESCE(SUM(p.gross), 0) * mb.pajak / 100), 2) AS pajak,
                FORMAT(COALESCE(SUM(p.gross), 0) - (COALESCE(SUM(p.gross), 0) * mb.pajak / 100), 2) AS net,
                FORMAT((COALESCE(SUM(p.gross), 0) - (COALESCE(SUM(p.gross), 0) * mb.pajak / 100)) / 2, 2) AS share_ph,
                FORMAT(((COALESCE(SUM(p.gross), 0) - (COALESCE(SUM(p.gross), 0) * mb.pajak / 100)) / 2) * 0.015, 2) AS royalty,
                FORMAT(
                    (COALESCE(SUM(p.gross), 0) - (COALESCE(SUM(p.gross), 0) * mb.pajak / 100)) -
                    ((COALESCE(SUM(p.gross), 0) - (COALESCE(SUM(p.gross), 0) * mb.pajak / 100)) / 2) -
                    (((COALESCE(SUM(p.gross), 0) - (COALESCE(SUM(p.gross), 0) * mb.pajak / 100)) / 2) * 0.015),
                    2
                ) AS total_akhir
            FROM pelaporans p
            LEFT JOIN kategori_bioskops kb ON kb.uuid = p.kategori
            LEFT JOIN kapasitas k ON k.uuid = p.studio AND k.type_tiket = p.type_tiket
            LEFT JOIN type_tikets tt ON tt.uuid = p.type_tiket
            LEFT JOIN master_bioskops mb ON mb.uuid = p.nama_bioskop
            {$whereSql}
            GROUP BY
                p.tgl_tayang,
                kb.name,
                mb.kota,
                mb.nama_bioskop,
                k.studio,
                k.kapasitas,
                p.type_tiket,
                p.harga,
                mb.pajak
            ORDER BY
                kb.name,
                mb.kota,
                mb.nama_bioskop,
                p.tgl_tayang
            ";



        $rows = DB::select($sql, $bindings);

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $headers = [
            'Tanggal', 'Kategori', 'Kota', 'Nama Bioskop', 'Studio', 'Kapasitas',
            'S1','S2','S3','S4','S5','S6','S7','Total','Harga','Gross','Pajak %','Pajak','Net','Share','Share PH','Royalty (1.5%)','Total Akhir'
        ];

        $sheet->fromArray($headers, null, 'A1');

        $rowNum = 2;
        foreach ($rows as $r) {
            $sheet->setCellValue('A' . $rowNum, $r->tgl_tayang);
            $sheet->setCellValue('B' . $rowNum, $r->name);
            $sheet->setCellValue('C' . $rowNum, $r->kota);
            $sheet->setCellValue('D' . $rowNum, strtoupper($r->nama_bioskop));
            $sheet->setCellValue('E' . $rowNum, $r->studio);
            $sheet->setCellValue('F' . $rowNum, $r->kapasitas);
            $sheet->setCellValue('G' . $rowNum, $r->S1);
            $sheet->setCellValue('H' . $rowNum, $r->S2);
            $sheet->setCellValue('I' . $rowNum, $r->S3);
            $sheet->setCellValue('J' . $rowNum, $r->S4);
            $sheet->setCellValue('K' . $rowNum, $r->S5);
            $sheet->setCellValue('L' . $rowNum, $r->S6);
            $sheet->setCellValue('M' . $rowNum, $r->S7);
            $sheet->setCellValue('N' . $rowNum, $r->Total);
            $sheet->setCellValue('O' . $rowNum, $r->harga);
            $sheet->setCellValue('P' . $rowNum, $r->gross);
            $sheet->setCellValue('Q' . $rowNum, $r->pajak_persen . '%');
            $sheet->setCellValue('R' . $rowNum, $r->pajak);
            $sheet->setCellValue('S' . $rowNum, $r->net);
            $sheet->setCellValue('T' . $rowNum, '50%');
            $sheet->setCellValue('U' . $rowNum, $r->share_ph);
            $sheet->setCellValue('V' . $rowNum, $r->royalty);
            $sheet->setCellValue('W' . $rowNum, $r->total_akhir);
            $rowNum++;
        }

        $filename = 'laporan-detail-' . date('YmdHis') . '.xlsx';

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
