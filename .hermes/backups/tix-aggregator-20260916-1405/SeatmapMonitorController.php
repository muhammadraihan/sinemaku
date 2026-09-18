<?php

namespace App\Http\Controllers;

use App\Models\SeatmapShowtime;
use App\Models\SeatmapSnapshot;
use App\Models\SeatmapSource;
use App\Models\SeatmapSourceAccount;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class SeatmapMonitorController extends Controller
{
    public function index()
    {
        return view('seatmap-monitor.index');
    }

    public function ranking(Request $request)
    {
        $date = $request->input('date', Carbon::today()->toDateString());
        $query = SeatmapShowtime::query()
            ->where('show_date', $date)
            ->where('status', 'active');

        if ($request->filled('source')) {
            $query->where('source_uuid', $request->source);
        }

        $showtimes = $query->get();
        if ($showtimes->isEmpty()) {
            return response()->json([
                'date' => $date,
                'data' => [],
                'meta' => [
                    'last_captured_at' => null,
                    'disclaimer' => $this->disclaimer(),
                ],
            ]);
        }

        $ids = $showtimes->pluck('uuid')->all();
        $snapshots = SeatmapSnapshot::whereIn('showtime_uuid', $ids)
            ->orderBy('captured_at')
            ->get()
            ->groupBy('showtime_uuid');

        $ranked = $showtimes->groupBy(function ($showtime) {
            return $this->normalizeFilmName($showtime->film_name);
        })->map(function ($filmShowtimes, $normalizedFilmName) use ($snapshots) {
            $latestEstimated = 0;
            $previousEstimated = 0;
            $totalSeats = 0;
            $lastCapturedAt = null;
            $sources = collect();
            $cinemas = collect();

            foreach ($filmShowtimes as $showtime) {
                $sources->push($showtime->source_uuid);
                $cinemas->push($showtime->cinema_name);
                $rows = $snapshots->get($showtime->uuid, collect());
                $latest = $rows->last();
                $previous = $rows->count() > 1 ? $rows->get($rows->count() - 2) : null;

                if ($latest) {
                    $latestEstimated += (int) ($latest->unavailable_seats ?? $latest->sold_seats ?? 0);
                    $totalSeats += (int) ($latest->total_seats ?? $showtime->total_seats ?? 0);
                    $lastCapturedAt = !$lastCapturedAt || $latest->captured_at->greaterThan($lastCapturedAt)
                        ? $latest->captured_at : $lastCapturedAt;
                }
                if ($previous) {
                    $previousEstimated += (int) ($previous->unavailable_seats ?? $previous->sold_seats ?? 0);
                }
            }

            $delta = $latestEstimated - $previousEstimated;
            $change = $previousEstimated > 0 ? round(($delta / $previousEstimated) * 100, 1) : null;

            return [
                'film_name' => $filmShowtimes->first()->film_name,
                'normalized_film_name' => $normalizedFilmName,
                'daily_estimated_admissions' => $latestEstimated,
                'change_percent' => $change,
                'delta_admissions' => $delta,
                'estimated_occupancy_percent' => $totalSeats > 0 ? round(($latestEstimated / $totalSeats) * 100, 2) : null,
                'showtimes' => $filmShowtimes->count(),
                'source_count' => $sources->filter()->unique()->count(),
                'cinema_count' => $cinemas->filter()->unique()->count(),
                'chains_label' => $this->providerCoverageLabel($sources->filter()->unique()->values()->all()),
                'last_captured_at' => optional($lastCapturedAt)->toIso8601String(),
            ];
        })->values()->sortByDesc('daily_estimated_admissions')->values();

        $ranked = $ranked->map(function ($row, $index) {
            $row['rank'] = $index + 1;
            return $row;
        });

        return response()->json([
            'date' => $date,
            'data' => $ranked,
            'meta' => [
                'last_captured_at' => $ranked->pluck('last_captured_at')->filter()->max(),
                'disclaimer' => $this->disclaimer(),
            ],
        ]);
    }

    public function timeline(Request $request, $film)
    {
        $date = $request->input('date', Carbon::today()->toDateString());
        $showtimes = SeatmapShowtime::where('film_name', $film)
            ->where('show_date', $date)
            ->where('status', 'active')
            ->get();

        $snapshots = SeatmapSnapshot::whereIn('showtime_uuid', $showtimes->pluck('uuid'))
            ->orderBy('captured_at')
            ->get()
            ->groupBy(function ($snapshot) {
                return $snapshot->captured_at->format('Y-m-d H:i:00');
            })
            ->map(function ($items, $capturedAt) {
                return [
                    'captured_at' => $capturedAt,
                    'estimated_occupied' => $items->sum(function ($item) {
                        return (int) ($item->unavailable_seats ?? $item->sold_seats ?? 0);
                    }),
                    'showtimes_observed' => $items->count(),
                ];
            })->values();

        return response()->json([
            'film_name' => $film,
            'date' => $date,
            'data' => $snapshots,
            'disclaimer' => $this->disclaimer(),
        ]);
    }

    public function sources()
    {
        $sources = SeatmapSource::withCount('accounts')->orderBy('provider')->get();
        return view('seatmap-monitor.sources', compact('sources'));
    }

    public function storeSource(Request $request)
    {
        $data = $request->validate([
            'provider' => ['required', Rule::in(['cinepolis', 'cgv', 'xxi', 'other'])],
            'label' => 'required|string|max:100',
            'base_url' => 'nullable|url|max:255',
            'notes' => 'nullable|string|max:2000',
        ], $this->messages());

        $data['status'] = 'draft';
        $data['created_by'] = Auth::user()->uuid;
        SeatmapSource::create($data);

        return response()->json(['message' => 'Source berhasil dibuat. Tambahkan akun berizin sebelum collector dapat digunakan.']);
    }

    public function storeAccount(Request $request)
    {
        $data = $request->validate([
            'source_uuid' => 'required|exists:seatmap_sources,uuid',
            'label' => 'required|string|max:100',
            'username' => 'nullable|string|max:255|required_without:access_token',
            'password' => 'nullable|string|max:255|required_with:username',
            'access_token' => 'nullable|string|max:4000|required_without:username',
            'authorization_confirmed' => 'accepted',
        ], $this->messages());

        unset($data['authorization_confirmed']);
        $data['status'] = 'pending';
        $data['created_by'] = Auth::user()->uuid;
        SeatmapSourceAccount::create($data);

        return response()->json(['message' => 'Akun tersimpan terenkripsi. Password dan token tidak dapat ditampilkan kembali.']);
    }

    public function deleteAccount($account)
    {
        $account = SeatmapSourceAccount::uuid($account);
        $account->delete();

        return response()->json(['message' => 'Akun source telah dihapus.']);
    }

    private function normalizeFilmName($name)
    {
        $name = mb_strtoupper(trim(preg_replace('/\\s+/', ' ', $name)));
        $name = preg_replace('/\\s*\\((2D|3D|IMAX|REGULAR|PREMIUM)\\)\\s*$/i', '', $name);
        $name = preg_replace('/\\s+(2D|3D|IMAX|REGULAR|PREMIUM)\\s*$/i', '', $name);
        return trim($name);
    }

    private function providerCoverageLabel(array $sourceUuids)
    {
        if (!$sourceUuids) {
            return 'Belum ada source';
        }

        return SeatmapSource::whereIn('uuid', $sourceUuids)
            ->pluck('provider')
            ->map(function ($provider) {
                return strtoupper($provider);
            })
            ->unique()
            ->implode(' · ');
    }

    private function disclaimer()
    {
        return 'Ranking gabungan lintas chain/bioskop dari ketersediaan seat map publik. Bukan admissions resmi; dapat mencakup kursi held, blocked, reserved, atau tidak tersedia karena alasan operasional.';
    }

    private function messages()
    {
        return [
            'provider.required' => 'Pilih provider sumber.',
            'provider.in' => 'Provider sumber tidak didukung.',
            'label.required' => 'Label wajib diisi.',
            'base_url.url' => 'URL sumber tidak valid.',
            'source_uuid.required' => 'Pilih source terlebih dahulu.',
            'source_uuid.exists' => 'Source tidak ditemukan.',
            'username.required_without' => 'Isi username/email atau access token.',
            'password.required_with' => 'Password wajib diisi jika username digunakan.',
            'access_token.required_without' => 'Isi access token atau username/email.',
            'authorization_confirmed.accepted' => 'Konfirmasi bahwa akun ini berizin wajib dicentang.',
        ];
    }
}
