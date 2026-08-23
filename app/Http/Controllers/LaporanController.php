<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Models\Pelaporan;
use App\Models\TypeTiket;
use App\Models\KategoriBioskop;
use App\Models\MasterBioskop;
use App\Models\MasterFilm;
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
        $nama_bioskop = ['ALL' => 'Semua ...'] + MasterBioskop::all()->pluck('nama_bioskop', 'uuid')->toArray();
        $kota = ['ALL' => 'Semua ...'] + MasterBioskop::selectRaw('Distinct kota')->pluck('kota', 'kota')->toArray();
        $type_tiket = ['ALL' => 'Semua ...'] + TypeTiket::all()->pluck('name', 'uuid')->toArray();
        $nama_film = ['ALL' => 'Semua ...'] + MasterFilm::options()->toArray();
        return view('laporan.index', compact('bioskop_kategori', 'nama_bioskop', 'kota','type_tiket', 'nama_film'));
    }

    public function financeInsight()
    {
        $bioskop_kategori = KategoriBioskop::all()->pluck('name', 'uuid')->toArray();
        $bioskop_kategori = ['ALL' => 'Semua ...'] + $bioskop_kategori;
        $nama_bioskop = ['ALL' => 'Semua ...'] + MasterBioskop::all()->pluck('nama_bioskop', 'uuid')->toArray();
        $kota = ['ALL' => 'Semua ...'] + MasterBioskop::selectRaw('Distinct kota')->pluck('kota', 'kota')->toArray();
        $type_tiket = ['ALL' => 'Semua ...'] + TypeTiket::all()->pluck('name', 'uuid')->toArray();
        $nama_film = ['ALL' => 'Semua ...'] + MasterFilm::options()->toArray();

        return view('laporan.finance_insight', compact('bioskop_kategori', 'nama_bioskop', 'kota','type_tiket', 'nama_film'));
    }

    public function financeInsightData(Request $request)
    {
        $jumlah = "CAST(REPLACE(COALESCE(NULLIF(pelaporans.jumlah, ''), '0'), ',', '') AS DECIMAL(20,2))";
        $harga = "CAST(REPLACE(COALESCE(NULLIF(pelaporans.harga, ''), '0'), ',', '') AS DECIMAL(20,2))";
        $gross = "CAST(REPLACE(COALESCE(NULLIF(pelaporans.gross, ''), '0'), ',', '') AS DECIMAL(20,2))";
        $pajak = "CAST(REPLACE(COALESCE(NULLIF(mb.pajak, ''), '0'), ',', '') AS DECIMAL(10,2))";
        $kapasitas = "CAST(REPLACE(COALESCE(NULLIF(k.kapasitas, ''), '0'), ',', '') AS DECIMAL(20,2))";
        $tax = "($gross * $pajak / 100)";
        $net = "($gross - $tax)";
        $share = "($net / 2)";
        $royalty = "($share * 0.015)";
        $totalPh = "($net - $share - $royalty)";
        $expectedGross = "($harga * $jumlah)";
        $grossVariance = "($gross - $expectedGross)";

        $base = DB::table('pelaporans')
            ->leftJoin('kategori_bioskops as kb', 'kb.uuid', '=', 'pelaporans.kategori')
            ->leftJoin('master_bioskops as mb', 'mb.uuid', '=', 'pelaporans.nama_bioskop')
            ->leftJoin('kapasitas as k', function ($join) {
                $join->on('k.uuid', '=', 'pelaporans.studio')
                     ->on('k.type_tiket', '=', 'pelaporans.type_tiket');
            });

        if ($request->filled('nama_film') && $request->nama_film != 'ALL') {
            $base->where('pelaporans.nama_film', $request->nama_film);
        }

        if (!empty($request->tgl_mulai) && !empty($request->tgl_akhir)) {
            $base->whereBetween('pelaporans.tgl_tayang', [$request->tgl_mulai, $request->tgl_akhir]);
        }

        if ($request->filled('bioskop_kategori') && $request->bioskop_kategori != 'ALL') {
            $base->where('pelaporans.kategori', $request->bioskop_kategori);
        }

        if ($request->filled('kota') && $request->kota != 'ALL') {
            $base->where('pelaporans.kota', $request->kota);
        }

        if ($request->filled('nama_bioskop') && $request->nama_bioskop != 'ALL') {
            $base->where('pelaporans.nama_bioskop', $request->nama_bioskop);
        }

        if ($request->filled('type_tiket') && $request->type_tiket != 'ALL') {
            $base->where('pelaporans.type_tiket', $request->type_tiket);
        }

        $summary = (clone $base)->selectRaw("
            COUNT(*) as row_count,
            COUNT(DISTINCT pelaporans.nama_bioskop) as cinema_count,
            COUNT(DISTINCT pelaporans.kota) as city_count,
            COUNT(DISTINCT NULLIF(TRIM(pelaporans.provinsi), '')) as province_count,
            SUM($jumlah) as audience,
            SUM($kapasitas) as seats_available,
            SUM($gross) as gross,
            SUM($tax) as tax,
            SUM($net) as net,
            SUM($share) as share,
            SUM($royalty) as royalty,
            SUM($totalPh) as total_ph
        ")->first();

        $topCategory = (clone $base)->selectRaw("
            COALESCE(kb.name, pelaporans.kategori) as label,
            SUM($jumlah) as audience,
            SUM($gross) as gross,
            SUM($totalPh) as total_ph
        ")
        ->groupBy('pelaporans.kategori', 'kb.name')
        ->orderByDesc('total_ph')
        ->first();

        $topCinema = (clone $base)->selectRaw("
            COALESCE(mb.nama_bioskop, pelaporans.nama_bioskop) as label,
            COALESCE(mb.kota, pelaporans.kota) as kota,
            SUM($jumlah) as audience,
            SUM($gross) as gross,
            SUM($totalPh) as total_ph
        ")
        ->groupBy('pelaporans.nama_bioskop', 'pelaporans.kota', 'mb.nama_bioskop', 'mb.kota')
        ->orderByDesc('total_ph')
        ->first();

        $topCity = (clone $base)->selectRaw("
            COALESCE(mb.kota, pelaporans.kota) as label,
            SUM($jumlah) as audience,
            SUM($gross) as gross,
            SUM($totalPh) as total_ph
        ")
        ->groupBy('pelaporans.kota', 'mb.kota')
        ->orderByDesc('total_ph')
        ->first();

        $topProvince = (clone $base)->selectRaw("
            COALESCE(NULLIF(TRIM(pelaporans.provinsi), ''), 'Belum Terpetakan') as label,
            COUNT(DISTINCT pelaporans.kota) as city_count,
            COUNT(DISTINCT pelaporans.nama_bioskop) as cinema_count,
            SUM($jumlah) as audience,
            SUM($gross) as gross,
            SUM($totalPh) as total_ph
        ")
        ->groupBy('pelaporans.provinsi')
        ->orderByDesc('total_ph')
        ->first();

        $topOccupancy = (clone $base)->selectRaw("
            COALESCE(mb.nama_bioskop, pelaporans.nama_bioskop) as label,
            COALESCE(mb.kota, pelaporans.kota) as kota,
            SUM($jumlah) as audience,
            SUM($kapasitas) as seats_available,
            CASE WHEN SUM($kapasitas) > 0 THEN (SUM($jumlah) / SUM($kapasitas)) * 100 ELSE 0 END as occupancy_rate
        ")
        ->groupBy('pelaporans.nama_bioskop', 'pelaporans.kota', 'mb.nama_bioskop', 'mb.kota')
        ->havingRaw("SUM($kapasitas) > 0")
        ->orderByDesc('occupancy_rate')
        ->first();

        $leaderboard = (clone $base)->selectRaw("
            COALESCE(mb.nama_bioskop, pelaporans.nama_bioskop) as nama_bioskop,
            COALESCE(mb.kota, pelaporans.kota) as kota,
            SUM($jumlah) as audience,
            SUM($gross) as gross,
            CASE WHEN SUM($jumlah) > 0 THEN SUM($gross) / SUM($jumlah) ELSE 0 END as atp,
            CASE WHEN SUM($kapasitas) > 0 THEN (SUM($jumlah) / SUM($kapasitas)) * 100 ELSE 0 END as occupancy_rate,
            SUM($totalPh) as total_ph
        ")
        ->groupBy('pelaporans.nama_bioskop', 'pelaporans.kota', 'mb.nama_bioskop', 'mb.kota')
        ->orderByDesc('total_ph')
        ->limit(5)
        ->get();

        $provinceLeaderboard = (clone $base)->selectRaw("
            COALESCE(NULLIF(TRIM(pelaporans.provinsi), ''), 'Belum Terpetakan') as provinsi,
            COUNT(DISTINCT pelaporans.kota) as city_count,
            COUNT(DISTINCT pelaporans.nama_bioskop) as cinema_count,
            SUM($jumlah) as audience,
            SUM($gross) as gross,
            CASE WHEN SUM($jumlah) > 0 THEN SUM($gross) / SUM($jumlah) ELSE 0 END as atp,
            CASE WHEN SUM($kapasitas) > 0 THEN (SUM($jumlah) / SUM($kapasitas)) * 100 ELSE 0 END as occupancy_rate,
            SUM($totalPh) as total_ph
        ")
        ->groupBy('pelaporans.provinsi')
        ->orderByDesc('total_ph')
        ->limit(5)
        ->get();

        $audit = (clone $base)->selectRaw("
            SUM(CASE WHEN k.uuid IS NULL OR $kapasitas <= 0 THEN 1 ELSE 0 END) as capacity_issues,
            SUM(CASE WHEN mb.pajak IS NULL OR mb.pajak = '' THEN 1 ELSE 0 END) as tax_issues,
            SUM(CASE WHEN $kapasitas > 0 AND $jumlah > $kapasitas THEN 1 ELSE 0 END) as occupancy_issues,
            SUM(CASE WHEN ABS($grossVariance) > 1 THEN 1 ELSE 0 END) as gross_issues,
            SUM(CASE
                WHEN k.uuid IS NULL OR $kapasitas <= 0
                    OR mb.pajak IS NULL OR mb.pajak = ''
                    OR ($kapasitas > 0 AND $jumlah > $kapasitas)
                    OR ABS($grossVariance) > 1
                THEN 1 ELSE 0 END
            ) as total_issues
        ")->first();

        $audience = (float) ($summary->audience ?? 0);
        $grossTotal = (float) ($summary->gross ?? 0);
        $taxTotal = (float) ($summary->tax ?? 0);
        $seatsAvailable = (float) ($summary->seats_available ?? 0);
        $totalPhValue = (float) ($summary->total_ph ?? 0);
        $occupancyRate = $seatsAvailable > 0 ? ($audience / $seatsAvailable) * 100 : 0;
        $atp = $audience > 0 ? $grossTotal / $audience : 0;
        $effectiveTaxRate = $grossTotal > 0 ? ($taxTotal / $grossTotal) * 100 : 0;
        $auditIssueCount = (int) ($audit->total_issues ?? 0);

        $notes = [];
        if ($topCinema) {
            $notes[] = 'Kontributor Total PH terbesar adalah ' . $topCinema->label . ' di ' . $topCinema->kota . '.';
        }
        if ($topCategory) {
            $notes[] = 'Kategori terkuat pada filter ini adalah ' . $topCategory->label . '.';
        }
        if ($topProvince) {
            $notes[] = 'Provinsi dengan kontribusi Total PH terbesar adalah ' . $topProvince->label . '.';
        }
        if ($occupancyRate >= 75) {
            $notes[] = 'Occupancy agregat berada di level kuat, menandakan kapasitas show dimanfaatkan dengan baik.';
        } elseif ($occupancyRate > 0) {
            $notes[] = 'Occupancy agregat masih dapat ditingkatkan melalui optimasi jadwal, kota, atau tipe tiket.';
        }
        if ($auditIssueCount > 0) {
            $notes[] = 'Terdapat ' . number_format($auditIssueCount) . ' data yang perlu direview sebelum laporan final.';
        } else {
            $notes[] = 'Tidak ada audit issue utama pada filter ini.';
        }

        return response()->json([
            'summary' => [
                'row_count' => (int) ($summary->row_count ?? 0),
                'cinema_count' => (int) ($summary->cinema_count ?? 0),
                'city_count' => (int) ($summary->city_count ?? 0),
                'province_count' => (int) ($summary->province_count ?? 0),
                'audience' => $audience,
                'seats_available' => $seatsAvailable,
                'gross' => $grossTotal,
                'tax' => $taxTotal,
                'net' => (float) ($summary->net ?? 0),
                'share' => (float) ($summary->share ?? 0),
                'royalty' => (float) ($summary->royalty ?? 0),
                'total_ph' => $totalPhValue,
                'atp' => $atp,
                'occupancy_rate' => $occupancyRate,
                'effective_tax_rate' => $effectiveTaxRate,
                'audit_issues' => $auditIssueCount,
            ],
            'top_category' => $topCategory,
            'top_cinema' => $topCinema,
            'top_city' => $topCity,
            'top_province' => $topProvince,
            'top_occupancy' => $topOccupancy,
            'leaderboard' => $leaderboard,
            'province_leaderboard' => $provinceLeaderboard,
            'audit' => $audit,
            'notes' => $notes,
        ]);
    }

    public function trendAnalysis()
    {
        $bioskop_kategori = KategoriBioskop::all()->pluck('name', 'uuid')->toArray();
        $bioskop_kategori = ['ALL' => 'Semua ...'] + $bioskop_kategori;
        $nama_bioskop = ['ALL' => 'Semua ...'] + MasterBioskop::all()->pluck('nama_bioskop', 'uuid')->toArray();
        $kota = ['ALL' => 'Semua ...'] + MasterBioskop::selectRaw('Distinct kota')->pluck('kota', 'kota')->toArray();
        $type_tiket = ['ALL' => 'Semua ...'] + TypeTiket::all()->pluck('name', 'uuid')->toArray();
        $nama_film = ['ALL' => 'Semua ...'] + MasterFilm::options()->toArray();

        return view('laporan.trend_analysis', compact('bioskop_kategori', 'nama_bioskop', 'kota','type_tiket', 'nama_film'));
    }

    public function trendAnalysisData(Request $request)
    {
        $jumlah = "CAST(REPLACE(COALESCE(NULLIF(pelaporans.jumlah, ''), '0'), ',', '') AS DECIMAL(20,2))";
        $gross = "CAST(REPLACE(COALESCE(NULLIF(pelaporans.gross, ''), '0'), ',', '') AS DECIMAL(20,2))";
        $pajak = "CAST(REPLACE(COALESCE(NULLIF(mb.pajak, ''), '0'), ',', '') AS DECIMAL(10,2))";
        $kapasitas = "CAST(REPLACE(COALESCE(NULLIF(k.kapasitas, ''), '0'), ',', '') AS DECIMAL(20,2))";
        $tax = "($gross * $pajak / 100)";
        $net = "($gross - $tax)";
        $share = "($net / 2)";
        $royalty = "($share * 0.015)";
        $totalPh = "($net - $share - $royalty)";

        $base = DB::table('pelaporans')
            ->leftJoin('master_bioskops as mb', 'mb.uuid', '=', 'pelaporans.nama_bioskop')
            ->leftJoin('kapasitas as k', function ($join) {
                $join->on('k.uuid', '=', 'pelaporans.studio')
                     ->on('k.type_tiket', '=', 'pelaporans.type_tiket');
            });

        if ($request->filled('nama_film') && $request->nama_film != 'ALL') {
            $base->where('pelaporans.nama_film', $request->nama_film);
        }

        if (!empty($request->tgl_mulai) && !empty($request->tgl_akhir)) {
            $base->whereBetween('pelaporans.tgl_tayang', [$request->tgl_mulai, $request->tgl_akhir]);
        }

        if ($request->filled('bioskop_kategori') && $request->bioskop_kategori != 'ALL') {
            $base->where('pelaporans.kategori', $request->bioskop_kategori);
        }

        if ($request->filled('kota') && $request->kota != 'ALL') {
            $base->where('pelaporans.kota', $request->kota);
        }

        if ($request->filled('nama_bioskop') && $request->nama_bioskop != 'ALL') {
            $base->where('pelaporans.nama_bioskop', $request->nama_bioskop);
        }

        if ($request->filled('type_tiket') && $request->type_tiket != 'ALL') {
            $base->where('pelaporans.type_tiket', $request->type_tiket);
        }

        $daily = $base->selectRaw("
            pelaporans.tgl_tayang as tanggal,
            SUM($jumlah) as audience,
            SUM($kapasitas) as seats_available,
            SUM($gross) as gross,
            SUM($tax) as tax,
            SUM($net) as net,
            SUM($totalPh) as total_ph,
            CASE WHEN SUM($jumlah) > 0 THEN SUM($gross) / SUM($jumlah) ELSE 0 END as atp,
            CASE WHEN SUM($kapasitas) > 0 THEN (SUM($jumlah) / SUM($kapasitas)) * 100 ELSE 0 END as occupancy_rate,
            CASE WHEN SUM($gross) > 0 THEN (SUM($tax) / SUM($gross)) * 100 ELSE 0 END as effective_tax_rate
        ")
        ->groupBy('pelaporans.tgl_tayang')
        ->orderBy('pelaporans.tgl_tayang')
        ->get();

        $previousTotalPh = null;
        $previousGross = null;
        $previousAudience = null;
        $rows = [];

        foreach ($daily as $row) {
            $totalPhValue = (float) ($row->total_ph ?? 0);
            $grossValue = (float) ($row->gross ?? 0);
            $audienceValue = (float) ($row->audience ?? 0);

            $rows[] = [
                'tanggal' => $row->tanggal,
                'audience' => $audienceValue,
                'seats_available' => (float) ($row->seats_available ?? 0),
                'gross' => $grossValue,
                'tax' => (float) ($row->tax ?? 0),
                'net' => (float) ($row->net ?? 0),
                'total_ph' => $totalPhValue,
                'atp' => (float) ($row->atp ?? 0),
                'occupancy_rate' => (float) ($row->occupancy_rate ?? 0),
                'effective_tax_rate' => (float) ($row->effective_tax_rate ?? 0),
                'total_ph_change' => $this->percentChange($previousTotalPh, $totalPhValue),
                'gross_change' => $this->percentChange($previousGross, $grossValue),
                'audience_change' => $this->percentChange($previousAudience, $audienceValue),
            ];

            $previousTotalPh = $totalPhValue;
            $previousGross = $grossValue;
            $previousAudience = $audienceValue;
        }

        $dayCount = count($rows);
        $firstDay = $dayCount ? $rows[0] : null;
        $lastDay = $dayCount ? $rows[$dayCount - 1] : null;
        $bestDay = collect($rows)->sortByDesc('total_ph')->first();
        $bestGrowthDay = collect($rows)->filter(function ($row) {
            return $row['total_ph_change'] !== null;
        })->sortByDesc('total_ph_change')->first();

        $totalAudience = collect($rows)->sum('audience');
        $totalGross = collect($rows)->sum('gross');
        $totalPh = collect($rows)->sum('total_ph');
        $totalSeats = collect($rows)->sum('seats_available');

        $summary = [
            'day_count' => $dayCount,
            'audience' => $totalAudience,
            'gross' => $totalGross,
            'total_ph' => $totalPh,
            'avg_daily_ph' => $dayCount ? $totalPh / $dayCount : 0,
            'atp' => $totalAudience ? $totalGross / $totalAudience : 0,
            'occupancy_rate' => $totalSeats ? ($totalAudience / $totalSeats) * 100 : 0,
            'first_day_ph' => $firstDay ? $firstDay['total_ph'] : 0,
            'last_day_ph' => $lastDay ? $lastDay['total_ph'] : 0,
            'period_change' => $this->percentChange($firstDay['total_ph'] ?? null, $lastDay['total_ph'] ?? null),
            'best_day' => $bestDay,
            'best_growth_day' => $bestGrowthDay,
        ];

        $notes = [];
        if ($summary['period_change'] !== null) {
            $movement = $summary['period_change'] >= 0 ? 'naik' : 'turun';
            $notes[] = 'Total PH periode ini ' . $movement . ' ' . number_format(abs($summary['period_change']), 2) . '% dari hari pertama ke hari terakhir.';
        }
        if ($bestDay) {
            $notes[] = 'Hari terbaik berdasarkan Total PH adalah ' . $bestDay['tanggal'] . '.';
        }
        if ($bestGrowthDay && $bestGrowthDay['total_ph_change'] > 0) {
            $notes[] = 'Lonjakan harian terbaik terjadi pada ' . $bestGrowthDay['tanggal'] . ' sebesar ' . number_format($bestGrowthDay['total_ph_change'], 2) . '%.';
        }
        if (!$notes) {
            $notes[] = 'Belum cukup data harian untuk membaca movement.';
        }

        return response()->json([
            'summary' => $summary,
            'daily' => $rows,
            'notes' => $notes,
        ]);
    }

    private function percentChange($previous, $current)
    {
        if ($previous === null || (float) $previous == 0.0) {
            return null;
        }

        return (((float) $current - (float) $previous) / abs((float) $previous)) * 100;
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
        $jumlah = "CAST(REPLACE(COALESCE(NULLIF(pelaporans.jumlah, ''), '0'), ',', '') AS DECIMAL(20,2))";
        $gross = "CAST(REPLACE(COALESCE(NULLIF(pelaporans.gross, ''), '0'), ',', '') AS DECIMAL(20,2))";
        $pajak = "CAST(REPLACE(COALESCE(NULLIF(mb.pajak, ''), '0'), ',', '') AS DECIMAL(10,2))";
        $kapasitas = "CAST(REPLACE(COALESCE(NULLIF(k.kapasitas, ''), '0'), ',', '') AS DECIMAL(20,2))";
        $tax = "($gross * $pajak / 100)";
        $net = "($gross - $tax)";

        $summary = Pelaporan::with(['categories', 'cinemas', 'typeTiket'])
                    ->leftJoin('master_bioskops as mb', 'mb.uuid', '=', 'pelaporans.nama_bioskop')
                    ->leftJoin('kapasitas as k', function ($join) {
                        $join->on('k.uuid', '=', 'pelaporans.studio')
                             ->on('k.type_tiket', '=', 'pelaporans.type_tiket');
                    })
                    ->select('pelaporans.kategori', DB::raw("
                        SUM($jumlah) as jumlah,
                        SUM($kapasitas) as seats_available,
                        CASE WHEN SUM($kapasitas) > 0 THEN (SUM($jumlah) / SUM($kapasitas)) * 100 ELSE 0 END as occupancy_rate,
                        SUM($gross) as gross,
                        CASE WHEN SUM($jumlah) > 0 THEN SUM($gross) / SUM($jumlah) ELSE 0 END as atp,
                        SUM($tax) as tax,
                        CASE WHEN SUM($gross) > 0 THEN (SUM($tax) / SUM($gross)) * 100 ELSE 0 END as effective_tax_rate,
                        SUM($net) as net,
                        SUM($net / 2) as share
                    "));
    
        // Filter berdasarkan input user
        if ($request->nama_film != 'ALL') {
            $summary->where('pelaporans.nama_film', $request->nama_film);
        }
    
        if (!empty($request->tgl_mulai) && !empty($request->tgl_akhir)) {
            $summary->whereBetween('pelaporans.tgl_tayang', [$request->tgl_mulai, $request->tgl_akhir]);
        }
    
        if ($request->bioskop_kategori != 'ALL') {
            $summary->where('pelaporans.kategori', $request->bioskop_kategori);
        }
    
        if ($request->kota != 'ALL') {
            $summary->where('pelaporans.kota', $request->kota);
        }
    
        if ($request->nama_bioskop != 'ALL') {
            $summary->where('pelaporans.nama_bioskop', $request->nama_bioskop);
        }
    
        if ($request->type_tiket != 'ALL') {
            $summary->where('pelaporans.type_tiket', $request->type_tiket);
        }

        $data_summary = $summary->groupBy('pelaporans.kategori')
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
        ->editColumn('seats_available',function($row){
            return $row->seats_available ? number_format($row->seats_available) : '' ;
        })
        ->editColumn('occupancy_rate',function($row){
            return $row->occupancy_rate ? number_format($row->occupancy_rate, 2) . '%' : '0.00%' ;
        })
        ->editColumn('gross',function($row){
            return $row->gross ? number_format($row->gross) : '' ;
        })
        ->editColumn('atp',function($row){
            return $row->atp ? number_format($row->atp, 2) : '' ;
        })
        ->editColumn('tax',function($row){
            return $row->tax ? number_format($row->tax) : '' ;
        })
        ->editColumn('effective_tax_rate',function($row){
            return $row->effective_tax_rate ? number_format($row->effective_tax_rate, 2) . '%' : '0.00%' ;
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

    public function performanceListData(Request $request){
        $jumlah = "CAST(REPLACE(COALESCE(NULLIF(pelaporans.jumlah, ''), '0'), ',', '') AS DECIMAL(20,2))";
        $gross = "CAST(REPLACE(COALESCE(NULLIF(pelaporans.gross, ''), '0'), ',', '') AS DECIMAL(20,2))";
        $pajak = "CAST(REPLACE(COALESCE(NULLIF(mb.pajak, ''), '0'), ',', '') AS DECIMAL(10,2))";
        $kapasitas = "CAST(REPLACE(COALESCE(NULLIF(k.kapasitas, ''), '0'), ',', '') AS DECIMAL(20,2))";
        $tax = "($gross * $pajak / 100)";
        $net = "($gross - $tax)";
        $share = "($net / 2)";
        $royalty = "($share * 0.015)";
        $totalPh = "($net - $share - $royalty)";

        $performance = Pelaporan::query()
            ->leftJoin('master_bioskops as mb', 'mb.uuid', '=', 'pelaporans.nama_bioskop')
            ->leftJoin('kapasitas as k', function ($join) {
                $join->on('k.uuid', '=', 'pelaporans.studio')
                     ->on('k.type_tiket', '=', 'pelaporans.type_tiket');
            })
            ->selectRaw("
                COALESCE(mb.kota, pelaporans.kota) as kota,
                COALESCE(mb.nama_bioskop, pelaporans.nama_bioskop) as nama_bioskop,
                SUM($jumlah) as jumlah,
                SUM($kapasitas) as seats_available,
                CASE WHEN SUM($kapasitas) > 0 THEN (SUM($jumlah) / SUM($kapasitas)) * 100 ELSE 0 END as occupancy_rate,
                SUM($gross) as gross,
                CASE WHEN SUM($jumlah) > 0 THEN SUM($gross) / SUM($jumlah) ELSE 0 END as atp,
                SUM($tax) as tax,
                CASE WHEN SUM($gross) > 0 THEN (SUM($tax) / SUM($gross)) * 100 ELSE 0 END as effective_tax_rate,
                SUM($net) as net,
                SUM($share) as share,
                SUM($royalty) as royalty,
                SUM($totalPh) as total_ph
            ");

        if ($request->nama_film != 'ALL') {
            $performance->where('pelaporans.nama_film', $request->nama_film);
        }
    
        if (!empty($request->tgl_mulai) && !empty($request->tgl_akhir)) {
            $performance->whereBetween('pelaporans.tgl_tayang', [$request->tgl_mulai, $request->tgl_akhir]);
        }
    
        if ($request->bioskop_kategori != 'ALL') {
            $performance->where('pelaporans.kategori', $request->bioskop_kategori);
        }
    
        if ($request->kota != 'ALL') {
            $performance->where('pelaporans.kota', $request->kota);
        }
    
        if ($request->nama_bioskop != 'ALL') {
            $performance->where('pelaporans.nama_bioskop', $request->nama_bioskop);
        }
    
        if ($request->type_tiket != 'ALL') {
            $performance->where('pelaporans.type_tiket', $request->type_tiket);
        }

        $data_performance = $performance->groupBy('pelaporans.nama_bioskop', 'pelaporans.kota', 'mb.kota', 'mb.nama_bioskop')
                                    ->orderByDesc('total_ph')
                                    ->get();

        return Datatables::of($data_performance)
        ->addIndexColumn()
        ->editColumn('jumlah',function($row){
            return $row->jumlah ? number_format($row->jumlah) : '' ;
        })
        ->editColumn('seats_available',function($row){
            return $row->seats_available ? number_format($row->seats_available) : '' ;
        })
        ->editColumn('occupancy_rate',function($row){
            return $row->occupancy_rate ? number_format($row->occupancy_rate, 2) . '%' : '0.00%' ;
        })
        ->editColumn('gross',function($row){
            return $row->gross ? number_format($row->gross) : '' ;
        })
        ->editColumn('atp',function($row){
            return $row->atp ? number_format($row->atp, 2) : '' ;
        })
        ->editColumn('effective_tax_rate',function($row){
            return $row->effective_tax_rate ? number_format($row->effective_tax_rate, 2) . '%' : '0.00%' ;
        })
        ->editColumn('net',function($row){
            return $row->net ? number_format($row->net) : '' ;
        })
        ->editColumn('total_ph',function($row){
            return $row->total_ph ? number_format($row->total_ph) : '' ;
        })
        ->make(true);
    }

    public function provinceListData(Request $request){
        $jumlah = "CAST(REPLACE(COALESCE(NULLIF(pelaporans.jumlah, ''), '0'), ',', '') AS DECIMAL(20,2))";
        $gross = "CAST(REPLACE(COALESCE(NULLIF(pelaporans.gross, ''), '0'), ',', '') AS DECIMAL(20,2))";
        $pajak = "CAST(REPLACE(COALESCE(NULLIF(mb.pajak, ''), '0'), ',', '') AS DECIMAL(10,2))";
        $kapasitas = "CAST(REPLACE(COALESCE(NULLIF(k.kapasitas, ''), '0'), ',', '') AS DECIMAL(20,2))";
        $tax = "($gross * $pajak / 100)";
        $net = "($gross - $tax)";
        $share = "($net / 2)";
        $royalty = "($share * 0.015)";
        $totalPh = "($net - $share - $royalty)";
        $provinceLabel = "COALESCE(NULLIF(TRIM(pelaporans.provinsi), ''), 'Belum Terpetakan')";

        $province = Pelaporan::query()
            ->leftJoin('master_bioskops as mb', 'mb.uuid', '=', 'pelaporans.nama_bioskop')
            ->leftJoin('kapasitas as k', function ($join) {
                $join->on('k.uuid', '=', 'pelaporans.studio')
                     ->on('k.type_tiket', '=', 'pelaporans.type_tiket');
            })
            ->selectRaw("
                $provinceLabel as provinsi,
                COUNT(DISTINCT pelaporans.kota) as city_count,
                COUNT(DISTINCT pelaporans.nama_bioskop) as cinema_count,
                SUM($jumlah) as jumlah,
                SUM($kapasitas) as seats_available,
                CASE WHEN SUM($kapasitas) > 0 THEN (SUM($jumlah) / SUM($kapasitas)) * 100 ELSE 0 END as occupancy_rate,
                SUM($gross) as gross,
                CASE WHEN SUM($jumlah) > 0 THEN SUM($gross) / SUM($jumlah) ELSE 0 END as atp,
                SUM($tax) as tax,
                CASE WHEN SUM($gross) > 0 THEN (SUM($tax) / SUM($gross)) * 100 ELSE 0 END as effective_tax_rate,
                SUM($net) as net,
                SUM($totalPh) as total_ph
            ");

        if ($request->nama_film != 'ALL') {
            $province->where('pelaporans.nama_film', $request->nama_film);
        }
    
        if (!empty($request->tgl_mulai) && !empty($request->tgl_akhir)) {
            $province->whereBetween('pelaporans.tgl_tayang', [$request->tgl_mulai, $request->tgl_akhir]);
        }
    
        if ($request->bioskop_kategori != 'ALL') {
            $province->where('pelaporans.kategori', $request->bioskop_kategori);
        }
    
        if ($request->kota != 'ALL') {
            $province->where('pelaporans.kota', $request->kota);
        }
    
        if ($request->nama_bioskop != 'ALL') {
            $province->where('pelaporans.nama_bioskop', $request->nama_bioskop);
        }
    
        if ($request->type_tiket != 'ALL') {
            $province->where('pelaporans.type_tiket', $request->type_tiket);
        }

        $data_province = $province->groupBy('pelaporans.provinsi')
                                ->orderByDesc('total_ph')
                                ->get();

        return Datatables::of($data_province)
        ->addIndexColumn()
        ->editColumn('city_count',function($row){
            return $row->city_count ? number_format($row->city_count) : '0' ;
        })
        ->editColumn('cinema_count',function($row){
            return $row->cinema_count ? number_format($row->cinema_count) : '0' ;
        })
        ->editColumn('jumlah',function($row){
            return $row->jumlah ? number_format($row->jumlah) : '' ;
        })
        ->editColumn('seats_available',function($row){
            return $row->seats_available ? number_format($row->seats_available) : '' ;
        })
        ->editColumn('occupancy_rate',function($row){
            return $row->occupancy_rate ? number_format($row->occupancy_rate, 2) . '%' : '0.00%' ;
        })
        ->editColumn('gross',function($row){
            return $row->gross ? number_format($row->gross) : '' ;
        })
        ->editColumn('atp',function($row){
            return $row->atp ? number_format($row->atp, 2) : '' ;
        })
        ->editColumn('effective_tax_rate',function($row){
            return $row->effective_tax_rate ? number_format($row->effective_tax_rate, 2) . '%' : '0.00%' ;
        })
        ->editColumn('net',function($row){
            return $row->net ? number_format($row->net) : '' ;
        })
        ->editColumn('total_ph',function($row){
            return $row->total_ph ? number_format($row->total_ph) : '' ;
        })
        ->make(true);
    }

    public function auditCheckListData(Request $request){
        $jumlah = "CAST(REPLACE(COALESCE(NULLIF(pelaporans.jumlah, ''), '0'), ',', '') AS DECIMAL(20,2))";
        $harga = "CAST(REPLACE(COALESCE(NULLIF(pelaporans.harga, ''), '0'), ',', '') AS DECIMAL(20,2))";
        $gross = "CAST(REPLACE(COALESCE(NULLIF(pelaporans.gross, ''), '0'), ',', '') AS DECIMAL(20,2))";
        $kapasitas = "CAST(REPLACE(COALESCE(NULLIF(k.kapasitas, ''), '0'), ',', '') AS DECIMAL(20,2))";
        $expectedGross = "($harga * $jumlah)";
        $grossVariance = "($gross - $expectedGross)";

        $audit = Pelaporan::query()
            ->leftJoin('kategori_bioskops as kb', 'kb.uuid', '=', 'pelaporans.kategori')
            ->leftJoin('master_bioskops as mb', 'mb.uuid', '=', 'pelaporans.nama_bioskop')
            ->leftJoin('kapasitas as k', function ($join) {
                $join->on('k.uuid', '=', 'pelaporans.studio')
                     ->on('k.type_tiket', '=', 'pelaporans.type_tiket');
            })
            ->leftJoin('type_tikets as tt', 'tt.uuid', '=', 'pelaporans.type_tiket')
            ->selectRaw("
                CASE
                    WHEN k.uuid IS NULL THEN 'Kapasitas studio belum terdaftar'
                    WHEN $kapasitas <= 0 THEN 'Kapasitas studio kosong'
                    WHEN mb.pajak IS NULL OR mb.pajak = '' THEN 'Pajak bioskop belum terisi'
                    WHEN $kapasitas > 0 AND $jumlah > $kapasitas THEN 'Occupancy per show lebih dari 100%'
                    WHEN ABS($grossVariance) > 1 THEN 'Gross tidak sesuai harga x penonton'
                    ELSE 'Perlu review'
                END as issue,
                pelaporans.tgl_tayang,
                COALESCE(kb.name, pelaporans.kategori) as kategori,
                COALESCE(mb.kota, pelaporans.kota) as kota,
                COALESCE(mb.nama_bioskop, pelaporans.nama_bioskop) as nama_bioskop,
                COALESCE(k.studio, pelaporans.studio) as studio,
                pelaporans.`show` as `show`,
                COALESCE(tt.name, pelaporans.type_tiket) as type_tiket,
                $jumlah as jumlah,
                $kapasitas as kapasitas,
                $harga as harga,
                $gross as gross,
                $expectedGross as expected_gross,
                $grossVariance as selisih,
                mb.pajak as pajak
            ");

        if ($request->nama_film != 'ALL') {
            $audit->where('pelaporans.nama_film', $request->nama_film);
        }
    
        if (!empty($request->tgl_mulai) && !empty($request->tgl_akhir)) {
            $audit->whereBetween('pelaporans.tgl_tayang', [$request->tgl_mulai, $request->tgl_akhir]);
        }
    
        if ($request->bioskop_kategori != 'ALL') {
            $audit->where('pelaporans.kategori', $request->bioskop_kategori);
        }
    
        if ($request->kota != 'ALL') {
            $audit->where('pelaporans.kota', $request->kota);
        }
    
        if ($request->nama_bioskop != 'ALL') {
            $audit->where('pelaporans.nama_bioskop', $request->nama_bioskop);
        }
    
        if ($request->type_tiket != 'ALL') {
            $audit->where('pelaporans.type_tiket', $request->type_tiket);
        }

        $audit->where(function ($query) use ($jumlah, $gross, $kapasitas, $grossVariance) {
            $query->whereNull('k.uuid')
                ->orWhereNull('k.kapasitas')
                ->orWhere('k.kapasitas', '')
                ->orWhereRaw("$kapasitas <= 0")
                ->orWhereNull('mb.pajak')
                ->orWhere('mb.pajak', '')
                ->orWhereRaw("($kapasitas > 0 AND $jumlah > $kapasitas)")
                ->orWhereRaw("ABS($grossVariance) > 1");
        });

        $data_audit = $audit->orderBy('issue')
                            ->orderByDesc('pelaporans.tgl_tayang')
                            ->limit(500)
                            ->get();

        return Datatables::of($data_audit)
        ->addIndexColumn()
        ->editColumn('issue',function($row){
            return '<span class="badge badge-warning">' . e($row->issue) . '</span>';
        })
        ->editColumn('jumlah',function($row){
            return number_format($row->jumlah ?? 0);
        })
        ->editColumn('kapasitas',function($row){
            return number_format($row->kapasitas ?? 0);
        })
        ->editColumn('harga',function($row){
            return number_format($row->harga ?? 0, 2);
        })
        ->editColumn('gross',function($row){
            return number_format($row->gross ?? 0, 2);
        })
        ->editColumn('expected_gross',function($row){
            return number_format($row->expected_gross ?? 0, 2);
        })
        ->editColumn('selisih',function($row){
            return number_format($row->selisih ?? 0, 2);
        })
        ->editColumn('pajak',function($row){
            return $row->pajak !== null && $row->pajak !== '' ? $row->pajak . '%' : '-';
        })
        ->rawColumns(['issue'])
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
                FORMAT(SUM(CAST(REPLACE(COALESCE(NULLIF(k.kapasitas, ''), '0'), ',', '') AS DECIMAL(20,2))), 2) AS seats_available,
                FORMAT(
                    CASE
                        WHEN SUM(CAST(REPLACE(COALESCE(NULLIF(k.kapasitas, ''), '0'), ',', '') AS DECIMAL(20,2))) > 0
                        THEN (
                            (
                                COALESCE(SUM(CASE WHEN p.show = 1 THEN p.jumlah END), 0) +
                                COALESCE(SUM(CASE WHEN p.show = 2 THEN p.jumlah END), 0) +
                                COALESCE(SUM(CASE WHEN p.show = 3 THEN p.jumlah END), 0) +
                                COALESCE(SUM(CASE WHEN p.show = 4 THEN p.jumlah END), 0) +
                                COALESCE(SUM(CASE WHEN p.show = 5 THEN p.jumlah END), 0) +
                                COALESCE(SUM(CASE WHEN p.show = 6 THEN p.jumlah END), 0) +
                                COALESCE(SUM(CASE WHEN p.show = 7 THEN p.jumlah END), 0)
                            ) / SUM(CAST(REPLACE(COALESCE(NULLIF(k.kapasitas, ''), '0'), ',', '') AS DECIMAL(20,2)))
                        ) * 100
                        ELSE 0
                    END,
                    2
                ) AS occupancy_rate,
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
                FORMAT(p.harga, 2) AS atp,
                FORMAT(
                    CASE
                        WHEN COALESCE(SUM(p.gross), 0) > 0
                        THEN ((COALESCE(SUM(p.gross), 0) * mb.pajak / 100) / COALESCE(SUM(p.gross), 0)) * 100
                        ELSE 0
                    END,
                    2
                ) AS effective_tax_rate,
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

        $toNumber = function ($value) {
            return (float) str_replace(',', '', $value ?? 0);
        };

        $totals = [
            'S1' => 0,
            'S2' => 0,
            'S3' => 0,
            'S4' => 0,
            'S5' => 0,
            'S6' => 0,
            'S7' => 0,
            'Total' => 0,
            'seats_available' => 0,
            'gross' => 0,
            'pajak' => 0,
            'net' => 0,
            'share_ph' => 0,
            'royalty' => 0,
            'total_akhir' => 0,
        ];

        foreach ($rows as $r) {
            $totals['S1'] += $toNumber($r->S1);
            $totals['S2'] += $toNumber($r->S2);
            $totals['S3'] += $toNumber($r->S3);
            $totals['S4'] += $toNumber($r->S4);
            $totals['S5'] += $toNumber($r->S5);
            $totals['S6'] += $toNumber($r->S6);
            $totals['S7'] += $toNumber($r->S7);
            $totals['Total'] += $toNumber($r->Total);
            $totals['seats_available'] += $toNumber($r->seats_available);
            $totals['gross'] += $toNumber($r->gross);
            $totals['pajak'] += $toNumber($r->pajak);
            $totals['net'] += $toNumber($r->net);
            $totals['share_ph'] += $toNumber($r->share_ph);
            $totals['royalty'] += $toNumber($r->royalty);
            $totals['total_akhir'] += $toNumber($r->total_akhir);
        }

        if ($request->query('format') === 'json') {
            return response()->json([
                'rows' => $rows,
                'totals' => $totals,
                'row_count' => count($rows),
            ]);
        }

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $headers = [
            'Tanggal', 'Kategori', 'Kota', 'Nama Bioskop', 'Studio', 'Kapasitas',
            'S1','S2','S3','S4','S5','S6','S7','Total','Kapasitas Tersedia','Occupancy Rate','Harga','Gross','ATP','Effective Tax Rate','Pajak %','Pajak','Net','Share','Share PH','Royalty (1.5%)','Total Akhir'
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
            $sheet->setCellValue('O' . $rowNum, $r->seats_available);
            $sheet->setCellValue('P' . $rowNum, $r->occupancy_rate . '%');
            $sheet->setCellValue('Q' . $rowNum, $r->harga);
            $sheet->setCellValue('R' . $rowNum, $r->gross);
            $sheet->setCellValue('S' . $rowNum, $r->atp);
            $sheet->setCellValue('T' . $rowNum, $r->effective_tax_rate . '%');
            $sheet->setCellValue('U' . $rowNum, $r->pajak_persen . '%');
            $sheet->setCellValue('V' . $rowNum, $r->pajak);
            $sheet->setCellValue('W' . $rowNum, $r->net);
            $sheet->setCellValue('X' . $rowNum, '50%');
            $sheet->setCellValue('Y' . $rowNum, $r->share_ph);
            $sheet->setCellValue('Z' . $rowNum, $r->royalty);
            $sheet->setCellValue('AA' . $rowNum, $r->total_akhir);
            $rowNum++;
        }

        $sheet->setCellValue('A' . $rowNum, 'TOTAL');
        $sheet->mergeCells('A' . $rowNum . ':F' . $rowNum);
        $sheet->setCellValue('G' . $rowNum, $totals['S1']);
        $sheet->setCellValue('H' . $rowNum, $totals['S2']);
        $sheet->setCellValue('I' . $rowNum, $totals['S3']);
        $sheet->setCellValue('J' . $rowNum, $totals['S4']);
        $sheet->setCellValue('K' . $rowNum, $totals['S5']);
        $sheet->setCellValue('L' . $rowNum, $totals['S6']);
        $sheet->setCellValue('M' . $rowNum, $totals['S7']);
        $sheet->setCellValue('N' . $rowNum, $totals['Total']);
        $sheet->setCellValue('O' . $rowNum, number_format($totals['seats_available'], 2));
        $sheet->setCellValue('P' . $rowNum, $totals['seats_available'] ? number_format(($totals['Total'] / $totals['seats_available']) * 100, 2) . '%' : '0.00%');
        $sheet->setCellValue('R' . $rowNum, number_format($totals['gross'], 2));
        $sheet->setCellValue('S' . $rowNum, $totals['Total'] ? number_format($totals['gross'] / $totals['Total'], 2) : '0.00');
        $sheet->setCellValue('T' . $rowNum, $totals['gross'] ? number_format(($totals['pajak'] / $totals['gross']) * 100, 2) . '%' : '0.00%');
        $sheet->setCellValue('V' . $rowNum, number_format($totals['pajak'], 2));
        $sheet->setCellValue('W' . $rowNum, number_format($totals['net'], 2));
        $sheet->setCellValue('Y' . $rowNum, number_format($totals['share_ph'], 2));
        $sheet->setCellValue('Z' . $rowNum, number_format($totals['royalty'], 2));
        $sheet->setCellValue('AA' . $rowNum, number_format($totals['total_akhir'], 2));
        $sheet->getStyle('A' . $rowNum . ':AA' . $rowNum)->getFont()->setBold(true);

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
