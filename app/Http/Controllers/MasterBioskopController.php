<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Models\MasterBioskop;
use App\Models\KategoriBioskop;
use App\Models\TypeTiket;
use App\Models\Kapasitas;
use App\Models\CinemaTicketPrice;
use App\Models\CalendarHoliday;
use Illuminate\Validation\ValidationException;
use App\Services\Calendar\IndonesiaHolidayCalendar;
use Throwable;

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
            $data = MasterBioskop::with(['Categories', 'ticketPrices' => function ($query) {
                $query->with('ticketType')->where('active', true)->orderByDesc('valid_from');
            }])->get();

            return Datatables::of($data)
                ->addIndexColumn()
                ->editColumn('type', function ($row){
                    return $row->Categories->name ?? '';
                })
                ->editColumn('nama_bioskop', function ($row) {
                    return mb_strtoupper($row->nama_bioskop ?? '', 'UTF-8');
                })
                ->addColumn('ticket_price_summary', function ($row) {
                    if (mb_strtoupper((string) optional($row->Categories)->name, 'UTF-8') !== 'XXI') {
                        return '<span class="text-muted">—</span>';
                    }

                    $price = $row->ticketPrices->first(function ($item) {
                        return mb_strtoupper((string) optional($item->ticketType)->name, 'UTF-8') === 'REGULAR'
                            && $item->valid_from->lte(now()->toDateString())
                            && ($item->valid_until === null || $item->valid_until->gte(now()->toDateString()));
                    });

                    if (!$price) {
                        return '<span class="badge badge-warning">Belum diatur</span>';
                    }

                    $rupiah = fn ($value) => 'Rp'.number_format((float) $value, 2, ',', '.');
                    return '<div class="price-monitor">'
                        .'<span><small>Sen–Kam</small><strong>'.$rupiah($price->weekday_price).'</strong></span>'
                        .'<span><small>Jumat</small><strong>'.$rupiah($price->friday_price).'</strong></span>'
                        .'<span><small>Akhir pekan/libur</small><strong>'.$rupiah($price->weekend_holiday_price).'</strong></span>'
                        .'</div>';
                })
                ->addColumn('action', function ($row) {
                    return '
                            <a class="btn btn-success btn-sm btn-icon waves-effect waves-themed" href="' . route('masterbioskop.edit', $row->uuid) . '"><i class="fal fa-edit"></i></a>
                            <a class="btn btn-danger btn-sm btn-icon waves-effect waves-themed delete-btn" data-url="' . URL::route('masterbioskop.destroy', $row->uuid) . '" data-id="' . $row->uuid . '" data-token="' . csrf_token() . '" data-toggle="modal" data-target="#modal-delete"><i class="fal fa-trash-alt"></i></a>';
                })
                ->removeColumn('id')
                ->removeColumn('uuid')
                ->rawColumns(['action','type','ticket_price_summary'])
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
        $this->normalizeTicketPricePayload($request, 'ticket_price');

        $request->validate([
            'type' => 'required|exists:kategori_bioskops,uuid',
            'nama_bioskop' => 'required|string|max:255',
            'kota' => 'required|string|max:255',
            'pajak' => 'nullable|numeric|min:0|max:100',
            'no_telephone' => 'nullable|string|max:50',
            'ticket_types' => 'nullable|array',
            'ticket_types.*.name' => 'nullable|string|max:255',
            'capacities' => 'nullable|array',
            'capacities.*.ticket_type_ref' => 'required|string|max:100',
            'capacities.*.studio' => 'required_with:capacities|string|max:50',
            'capacities.*.kapasitas' => 'required_with:capacities|numeric|min:0',
            'ticket_price' => 'nullable|array',
            'ticket_price.weekday_price' => 'nullable|numeric|min:0',
            'ticket_price.friday_price' => 'nullable|numeric|min:0',
            'ticket_price.weekend_holiday_price' => 'nullable|numeric|min:0',
            'ticket_price.valid_from' => 'nullable|date',
            'ticket_price.valid_until' => 'nullable|date|after_or_equal:ticket_price.valid_from',
        ], [
            '*.required' => 'Field :attribute tidak boleh kosong.',
            '*.numeric' => 'Field :attribute harus berisi angka.',
        ]);

        $category = KategoriBioskop::where('uuid', $request->type)->firstOrFail();
        $isXxi = mb_strtoupper((string) $category->name, 'UTF-8') === 'XXI';
        $ticketPrice = $request->input('ticket_price');
        if (!$isXxi) {
            $ticketPrice = null;
        } elseif (is_array($ticketPrice) && collect($ticketPrice)->filter(fn ($value) => $value !== null && $value !== '')->isNotEmpty()) {
            foreach (['weekday_price', 'friday_price', 'weekend_holiday_price', 'valid_from'] as $field) {
                if (!isset($ticketPrice[$field]) || $ticketPrice[$field] === '') {
                    throw ValidationException::withMessages(["ticket_price.$field" => 'Lengkapi seluruh harga REGULAR XXI atau kosongkan semua field harga.']);
                }
            }
        } else {
            $ticketPrice = null;
        }

        $ticketTypes = collect($request->input('ticket_types', []) )
            ->map(fn ($item) => ['name' => mb_strtoupper(trim((string) ($item['name'] ?? '')), 'UTF-8')])
            ->filter(fn ($item) => $item['name'] !== '');
        $capacities = collect($request->input('capacities', []))->values();
        $existingTicketIds = $capacities->pluck('ticket_type_ref')
            ->filter(fn ($reference) => !str_starts_with((string) $reference, 'new:'))
            ->unique()
            ->values();
        $existingTickets = TypeTiket::where('kategori', $request->type)
            ->whereIn('uuid', $existingTicketIds)
            ->get()
            ->keyBy('uuid');

        foreach ($capacities as $index => $capacity) {
            $reference = (string) ($capacity['ticket_type_ref'] ?? '');
            $newTicketIndex = str_starts_with($reference, 'new:') ? (int) substr($reference, 4) : null;
            if (($newTicketIndex === null || !$ticketTypes->has($newTicketIndex)) && !$existingTickets->has($reference)) {
                return back()->withInput()->withErrors(["capacities.$index.ticket_type_ref" => 'Pilih tipe tiket yang tersedia untuk kategori bioskop ini.']);
            }
        }

        DB::transaction(function () use ($request, $ticketTypes, $capacities, $existingTickets, $ticketPrice, $isXxi) {
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
                $reference = (string) $capacity['ticket_type_ref'];
                $ticket = str_starts_with($reference, 'new:')
                    ? $createdTickets->get((int) substr($reference, 4))
                    : $existingTickets->get($reference);
                $model = new Kapasitas();
                $model->kategori = $bioskop->type;
                $model->kota = $bioskop->kota;
                $model->nama_bioskop = $bioskop->uuid;
                $model->type_tiket = $ticket->uuid;
                $model->studio = trim((string) $capacity['studio']);
                $model->kapasitas = $capacity['kapasitas'];
                $model->save();
            }

            if ($isXxi && $ticketPrice) {
                $regularTicket = TypeTiket::where('kategori', $bioskop->type)
                    ->whereRaw('UPPER(name) = ?', ['REGULAR'])
                    ->first();
                if (!$regularTicket) {
                    throw ValidationException::withMessages(['ticket_price' => 'Tipe tiket REGULAR untuk kategori XXI belum tersedia.']);
                }
                CinemaTicketPrice::create([
                    'uuid' => (string) \Illuminate\Support\Str::uuid(),
                    'master_bioskop_uuid' => $bioskop->uuid,
                    'type_tiket_uuid' => $regularTicket->uuid,
                    'weekday_price' => $ticketPrice['weekday_price'],
                    'friday_price' => $ticketPrice['friday_price'],
                    'weekend_holiday_price' => $ticketPrice['weekend_holiday_price'],
                    'valid_from' => $ticketPrice['valid_from'],
                    'valid_until' => $ticketPrice['valid_until'] ?? null,
                    'active' => true,
                    'created_by' => Auth::user()->uuid,
                ]);
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

        $ticketTypes = TypeTiket::where('kategori', $bioskop->type)->orderBy('name')->get();
        $ticketPrices = CinemaTicketPrice::with('ticketType')->where('master_bioskop_uuid', $bioskop->uuid)->orderByDesc('valid_from')->get();
        $holidays = CalendarHoliday::where('active', true)->orderBy('holiday_date')->get();
        return view('masterbioskop.edit', compact('bioskop', 'bioskop_kategori', 'ticketTypes', 'ticketPrices', 'holidays'));
    }

    private function normalizeTicketPricePayload(Request $request, ?string $key = null): void
    {
        $payload = $key ? $request->input($key) : $request->only(['weekday_price', 'friday_price', 'weekend_holiday_price']);
        if (!is_array($payload)) {
            return;
        }

        foreach (['weekday_price', 'friday_price', 'weekend_holiday_price'] as $field) {
            if (!array_key_exists($field, $payload) || $payload[$field] === null || $payload[$field] === '') {
                continue;
            }

            $value = trim((string) $payload[$field]);
            if (preg_match('/^\d{1,3}(?:\.\d{3})*(?:,\d{1,2})?$/', $value) || preg_match('/^\d+(?:,\d{1,2})?$/', $value)) {
                $payload[$field] = str_replace(',', '.', str_replace('.', '', $value));
            }
        }

        $request->merge($key ? [$key => $payload] : $payload);
    }

    public function storeTicketPrice(Request $request, $id)
    {
        $this->normalizeTicketPricePayload($request);
        $bioskop = MasterBioskop::uuid($id);
        $data = $request->validate([
            'type_tiket_uuid' => 'required|exists:type_tikets,uuid',
            'weekday_price' => 'required|numeric|min:0', 'friday_price' => 'required|numeric|min:0',
            'weekend_holiday_price' => 'required|numeric|min:0', 'valid_from' => 'required|date', 'valid_until' => 'nullable|date|after_or_equal:valid_from',
        ]);
        $ticket = TypeTiket::where('uuid', $data['type_tiket_uuid'])->where('kategori', $bioskop->type)->first();
        if (!$ticket) throw ValidationException::withMessages(['type_tiket_uuid' => 'Tipe tiket bukan bagian dari kategori bioskop ini.']);
        $overlap = CinemaTicketPrice::where('master_bioskop_uuid', $bioskop->uuid)->where('type_tiket_uuid', $ticket->uuid)->where('active', true)->overlapping($data['valid_from'], $data['valid_until'])->exists();
        if ($overlap) throw ValidationException::withMessages(['valid_from' => 'Periode harga bertabrakan dengan periode aktif yang sudah ada.']);
        $data['uuid'] = (string) \Illuminate\Support\Str::uuid(); $data['master_bioskop_uuid'] = $bioskop->uuid; $data['active'] = true; $data['created_by'] = Auth::user()->uuid;
        CinemaTicketPrice::create($data);
        return back()->with('success', 'Harga tiket berhasil ditambahkan.');
    }

    public function updateTicketPrice(Request $request, $id, $price)
    {
        $this->normalizeTicketPricePayload($request);
        $bioskop = MasterBioskop::uuid($id); $model = CinemaTicketPrice::where('uuid', $price)->where('master_bioskop_uuid', $bioskop->uuid)->firstOrFail();
        $data = $request->validate(['weekday_price'=>'required|numeric|min:0','friday_price'=>'required|numeric|min:0','weekend_holiday_price'=>'required|numeric|min:0','valid_from'=>'required|date','valid_until'=>'nullable|date|after_or_equal:valid_from']);
        $overlap = CinemaTicketPrice::where('master_bioskop_uuid',$bioskop->uuid)->where('type_tiket_uuid',$model->type_tiket_uuid)->where('uuid','<>',$model->uuid)->where('active',true)->overlapping($data['valid_from'],$data['valid_until'])->exists();
        if ($overlap) throw ValidationException::withMessages(['valid_from' => 'Periode harga bertabrakan dengan periode aktif yang sudah ada.']);
        $model->fill($data); $model->edited_by = Auth::user()->uuid; $model->save(); return back()->with('success','Harga tiket berhasil diperbarui.');
    }

    public function destroyTicketPrice($id, $price)
    {
        $bioskop = MasterBioskop::uuid($id); CinemaTicketPrice::where('uuid',$price)->where('master_bioskop_uuid',$bioskop->uuid)->delete(); return back()->with('success','Harga tiket berhasil dihapus.');
    }

    public function storeHoliday(Request $request)
    {
        $data = $request->validate(['holiday_date'=>'required|date|unique:calendar_holidays,holiday_date','name'=>'required|string|max:255']);
        $data['uuid'] = (string) \Illuminate\Support\Str::uuid(); $data['active'] = true; $data['created_by'] = Auth::user()->uuid; CalendarHoliday::create($data); return back()->with('success','Hari libur berhasil ditambahkan.');
    }

    public function destroyHoliday($holiday)
    {
        CalendarHoliday::where('uuid',$holiday)->delete(); return back()->with('success','Hari libur berhasil dihapus.');
    }

    public function previewHolidaySync(Request $request, IndonesiaHolidayCalendar $calendar)
    {
        $data = $request->validate(['year' => 'required|integer|min:2020|max:2100']);

        try {
            $holidays = $calendar->fetch((int) $data['year']);
        } catch (Throwable $exception) {
            return back()->withErrors(['holiday_sync' => $exception->getMessage()])->withFragment('holidays');
        }

        session(['holiday_sync_preview' => ['year' => (int) $data['year'], 'items' => $holidays]]);
        return back()->with('holiday_sync_preview_count', count($holidays))->withFragment('holidays');
    }

    public function applyHolidaySync(Request $request)
    {
        $data = $request->validate(['holiday_dates' => 'required|array|min:1', 'holiday_dates.*' => 'required|date_format:Y-m-d']);
        $preview = session('holiday_sync_preview');
        if (!is_array($preview) || !isset($preview['items'])) {
            return back()->withErrors(['holiday_sync' => 'Preview kalender sudah tidak tersedia. Ambil ulang data kalender sebelum menerapkan.'])->withFragment('holidays');
        }

        $allowed = collect($preview['items'])->keyBy('holiday_date');
        $selected = collect($data['holiday_dates'])->unique();
        if ($selected->contains(fn ($date) => !$allowed->has($date))) {
            throw ValidationException::withMessages(['holiday_dates' => 'Pilihan hari libur tidak sesuai dengan data preview.']);
        }

        $created = 0;
        DB::transaction(function () use ($selected, $allowed, &$created) {
            foreach ($selected as $date) {
                $item = $allowed->get($date);
                $model = CalendarHoliday::firstOrCreate(
                    ['holiday_date' => $date],
                    ['uuid' => (string) \Illuminate\Support\Str::uuid(), 'name' => $item['name'], 'active' => true, 'created_by' => Auth::user()->uuid]
                );
                if ($model->wasRecentlyCreated) {
                    $created++;
                }
            }
        });

        session()->forget('holiday_sync_preview');
        return back()->with('success', $created.' hari libur berhasil ditambahkan dari kalender Indonesia.')->withFragment('holidays');
    }

    /**
     * Update the specified resource.
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
