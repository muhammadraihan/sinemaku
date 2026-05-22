<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Models\Pelaporan;
use App\Models\TypeTiket;
use App\Models\KategoriBioskop;
use App\Models\MasterBioskop;
use App\Models\Laporan;


use Auth;
use DataTables;
use DB;
use URL;
use Helper;
use Image;
use Response;

class DashboardController extends Controller
{
    /**
   * Redirect to dashboard
   * @return [type] [description]
   */

  public function index()
    {
        $bioskop_kategori = KategoriBioskop::all()->pluck('name', 'uuid')->toArray();
        $bioskop_kategori = ['ALL' => 'Semua ...'] + $bioskop_kategori;
        // $nama_bioskop = MasterBioskop::all()->pluck('nama_bioskop', 'uuid');
        $kota = MasterBioskop::selectRaw('Distinct kota')->pluck('kota', 'kota');
        // $type_tiket = TypeTiket::all()->pluck('name', 'uuid');
        $nama_film = Pelaporan::selectRaw('Distinct nama_film')->pluck('nama_film', 'nama_film');
        $last = DB::table('pelaporans')
                ->select('nama_film')
                ->when(DB::getSchemaBuilder()->hasColumn('pelaporans', 'created_at'),
                    fn($q) => $q->orderByDesc('created_at'))
                ->orderByDesc('tgl_tayang')
                ->limit(1)
                ->first();

        $periode = DB::table('pelaporans')
          ->where('nama_film', $last->nama_film) // pastikan $nama_film sudah ditentukan
          ->selectRaw('MIN(DATE(tgl_tayang)) AS tgl_awal, MAX(DATE(tgl_tayang)) AS tgl_akhir')
          ->first();

        $total_penonton = DB::table('pelaporans')
          ->where('nama_film', $last->nama_film) // pastikan $nama_film sudah ditentukan
          ->selectRaw('CAST(COALESCE(SUM(jumlah), 0) AS UNSIGNED) as penonton')
          ->first();
        return view('backoffice.dashboard', compact('bioskop_kategori', 'kota', 'nama_film', 'last', 'periode', 'total_penonton'));
    }

    public function getTopCities(Request $request)
    {
        $nama_film = $request->nama_film;
        $start_date = $request->tgl_mulai;
        $end_date = $request->tgl_akhir;
        $bioskop_kategori = $request->bioskop_kategori;

        $query = DB::table('pelaporans')
            ->select('kota', DB::raw('SUM(jumlah) as jumlah'))
            ->whereBetween('tgl_tayang', [$start_date, $end_date])
            ->where('nama_film', $nama_film);
        
        if ($bioskop_kategori != 'ALL') {
            $query->where('kategori', $bioskop_kategori);
        }

        $data = $query->groupBy('kota')
            ->orderByDesc('jumlah')
            ->limit(20)
            ->get();

        return response()->json($data);
    }

    public function getCharAudience(Request $request)
    {
        // Ambil & rapikan parameter
       $nama_film        = $request->input('nama_film');
        $start_date_input = $request->input('tgl_mulai');
        $end_date_input   = $request->input('tgl_akhir');
        $bioskop_kategori = $request->input('bioskop_kategori', 'ALL');

        // Jika SEMUA filter utama kosong ⇒ pakai default dari data terakhir di tabel
        if (empty($nama_film) && empty($start_date_input) && empty($end_date_input)) {

            // 1) Dapatkan nama_film terakhir yang di-add
            //   - Prioritaskan created_at kalau ada, fallback ke tgl_tayang
            $last = DB::table('pelaporans')
                ->select('nama_film')
                ->when(DB::getSchemaBuilder()->hasColumn('pelaporans', 'created_at'),
                    fn($q) => $q->orderByDesc('created_at'))
                ->orderByDesc('tgl_tayang')
                ->limit(1)
                ->first();

            if ($last) {
                $nama_film = $last->nama_film;

                // 2) Rentang tanggal min–max untuk film tsb
                $range = DB::table('pelaporans')
                    ->where('nama_film', $nama_film)
                    ->selectRaw('MIN(tgl_tayang) AS min_tgl, MAX(tgl_tayang) AS max_tgl')
                    ->first();

                if ($range && $range->min_tgl && $range->max_tgl) {
                    $start_date_input = Carbon::parse($range->min_tgl)->toDateString();
                    $end_date_input   = Carbon::parse($range->max_tgl)->toDateString();
                }
            }

            // kategori biarkan ALL (sesuai permintaan)
            $bioskop_kategori = 'ALL';
        }

        // Normalisasi tanggal (00:00:00 s.d. 23:59:59)
        $start_date = $start_date_input ? Carbon::parse($start_date_input)->startOfDay()->toDateTimeString() : null;
        $end_date   = $end_date_input   ? Carbon::parse($end_date_input)->endOfDay()->toDateTimeString()   : null;

        // Helper untuk apply filter dasar
        $base = DB::table('pelaporans')
            ->when($start_date && $end_date, fn($q) => $q->whereBetween('tgl_tayang', [$start_date, $end_date]))
            ->when($nama_film, fn($q) => $q->where('nama_film', $nama_film))
            ->when($bioskop_kategori && $bioskop_kategori !== 'ALL', fn($q) => $q->where('kategori', $bioskop_kategori));

        // try {
            // ===================== 1) TOP 10 KOTA =====================
            $top_cities = (clone $base)
                ->select('kota', DB::raw('SUM(jumlah) AS jumlah'))
                ->groupBy('kota')
                ->orderByDesc('jumlah')
                ->limit(20)
                ->get()
                ->map(fn($r) => ['kota' => (string)$r->kota, 'jumlah' => (int)$r->jumlah])
                ->values();

            // ===================== 2) GRAFIK SHOW (per tanggal) =====================
            // pakai COUNT(*) sbg jumlah show per hari
            $shows_over_time = (clone $base)
                ->selectRaw('`show` AS show_label, SUM(`jumlah`) AS jumlah')
                ->groupByRaw('`show`')
                ->orderByRaw('`show`')
                ->get()
                ->map(fn($r) => ['show' => (string)$r->show_label, 'jumlah' => (int)$r->jumlah])
                ->values();

            // ===================== 3) PENONTON BY BIOSKOP =====================
            // asumsi kolom bioskop = 'bioskop' (ubah ke 'nama_bioskop' jika kolommu berbeda)
            $viewers_by_cinema = (clone $base)
                ->leftJoin('kategori_bioskops as mb', 'pelaporans.kategori', '=', 'mb.uuid')
                ->selectRaw("
                    CASE
                        WHEN UPPER(mb.name) IN ('XXI','CGV','CINEPOLIS') THEN UPPER(mb.name)
                        ELSE 'LOCAL CINEMA'
                    END AS bioskop_nama,
                    SUM(pelaporans.jumlah) AS penonton
                ")
                ->groupByRaw("
                    CASE
                        WHEN UPPER(mb.name) IN ('XXI','CGV','CINEPOLIS') THEN UPPER(mb.name)
                        ELSE 'LOCAL CINEMA'
                    END
                ")
                ->orderByDesc('penonton')
                ->get()
                ->map(fn($r) => [
                    'bioskop'  => (string)$r->bioskop_nama,
                    'penonton' => (int)$r->penonton,
                ])
                ->values();

            // ===================== 4) TOP 10 BIOSKOP =====================
            // $top_cinemas = $viewers_by_cinema->take(10)->values();
            $top_cinemas = (clone $base)
                ->leftJoin('master_bioskops as mb', 'pelaporans.nama_bioskop', '=', 'mb.uuid') // sesuaikan: 'uuid'
                ->selectRaw('
                    pelaporans.nama_bioskop AS bioskop_uuid,
                    COALESCE(mb.nama_bioskop, pelaporans.nama_bioskop) AS bioskop_nama,
                    SUM(pelaporans.jumlah) AS penonton
                ')
                ->groupBy('pelaporans.nama_bioskop', 'mb.nama_bioskop')
                ->orderByDesc('penonton')
                ->limit(20)
                ->get()
                ->map(fn($r) => [
                    'bioskop'  => (string)$r->bioskop_nama,
                    'penonton' => (int)$r->penonton,
                ])
                ->values();

            // ===================== 5) UNDERPERFORMING KOTA =====================
            // Ambil 10 terbawah (>0 agar tidak kebagian yang nol, silakan hapus ->where > 0 kalau ingin tampilkan nol)
            $underperf_cities = (clone $base)
                ->select('kota', DB::raw('SUM(jumlah) AS penonton'))
                ->groupBy('kota')
                ->havingRaw('SUM(jumlah) > 0')
                ->orderBy('penonton', 'asc')
                ->limit(20)
                ->get()
                ->map(fn($r) => ['kota' => (string)$r->kota, 'penonton' => (int)$r->penonton])
                ->values();

            // ===================== 6) UNDERPERFORMING BIOSKOP =====================
            $underperf_cinemas = (clone $base)
                ->leftJoin('master_bioskops as mb', 'pelaporans.nama_bioskop', '=', 'mb.uuid') // sesuaikan: 'uuid'
                ->selectRaw('
                    pelaporans.nama_bioskop AS bioskop_uuid,
                    COALESCE(mb.nama_bioskop, pelaporans.nama_bioskop) AS bioskop_nama,
                    SUM(pelaporans.jumlah) AS penonton
                ')
                ->groupBy('pelaporans.nama_bioskop', 'mb.nama_bioskop') // aman utk ONLY_FULL_GROUP_BY
                ->havingRaw('SUM(jumlah) > 0')
                ->orderBy('penonton', 'asc')
                ->limit(20)
                ->get()
                ->map(fn($r) => ['nama_bioskop' => (string)$r->bioskop_nama, 'penonton' => (int)$r->penonton])
                ->values();

            // Kembalikan JSON lengkap
            return response()->json([
                'top_cities'          => $top_cities,
                'shows_over_time'     => $shows_over_time,
                'viewers_by_cinema'   => $viewers_by_cinema,
                'top_cinemas'         => $top_cinemas,
                'underperf_cities'    => $underperf_cities,
                'underperf_cinemas'   => $underperf_cinemas,
            ]);

        // } catch (\Throwable $e) {
        //     // Log error & kirim 500 agar mudah dilacak
        //     \Log::error('getCharAudience failed: '.$e->getMessage(), ['trace' => $e->getTraceAsString()]);
        //     return response()->json(['message' => 'Server error'], 500);
        // }
    }
}
