<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

trait CorrectsImportPreview
{
    private function prepareCorrectionRows(array &$rows): void
    {
        foreach ($rows as &$row) {
            if (empty($row['row_id'])) $row['row_id'] = (string) Str::uuid();
            if (empty($row['original_row'])) {
                $row['original_row'] = array_diff_key($row, array_flip(['original_row', 'correction_issues']));
            }
        }
    }

    private function putImportPreview(string $key, array $value, $expiry): void
    {
        // Refreshes cannot turn the original thirty-minute lease into a sliding session.
        $value['expires_at'] = $value['expires_at'] ?? $expiry->timestamp;
        if (isset($value['mapping']) && isset($value['source_type'])) $value['mapping']['source_type'] = $value['source_type'];
        Cache::put($key, $value, max(1, $value['expires_at'] - now()->timestamp));
    }

    private function getImportPreview(string $key): ?array
    {
        $value = Cache::get($key);
        if ($value && isset($value['expires_at']) && $value['expires_at'] <= now()->timestamp) {
            Cache::forget($key);
            return null;
        }
        return $value;
    }

    private function correctionIssues(array $rows): array
    {
        return array_values(array_unique(array_merge([], ...array_column($rows, 'correction_issues'))));
    }

    private function duplicateCorrectionRows(array $rows, bool $pdf): array
    {
        $seen = [];
        foreach ($rows as $row) {
            $fields = $pdf ? ['tanggal','studio','type_tiket','jam_tayang','show','harga'] : ['tgl_tayang','nama_film','source_cinema','source_city','studio','ticket_name','jam_tayang','show','harga'];
            $key = json_encode(array_map(fn ($field) => mb_strtoupper(trim((string)($row[$field] ?? ''))), $fields));
            if (isset($seen[$key])) return ['Detail preview duplikat setelah mapping/koreksi. Periksa studio, show, jam, dan tipe tiket sebelum import.'];
            $seen[$key] = true;
        }
        return [];
    }

    private function correctionHistory(array $cached): ?string
    {
        if (empty($cached['corrections']) && empty($cached['exclusions'])) return null;
        return json_encode(['corrections' => $cached['corrections'] ?? [], 'partial_exclusions' => $cached['exclusions'] ?? []], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    }


    private function withImportTotals(array $mapping, array $rows, bool $pdf): array
    {
        $included = array_values(array_filter($rows, fn ($row) => empty($row['excluded'])));
        $total = fn (array $set, string $field) => array_sum(array_map(fn ($row) => (float) ($row[$field] ?? 0), $set));
        $source = $pdf ? ['admits'=>$total($rows,'jumlah'),'gross'=>$total($rows,'gross'),'tax_amount'=>$total($rows,'tax_amount'),'net'=>$total($rows,'net')] : ['admits'=>$total($rows,'jumlah'),'gross'=>array_sum(array_map(fn($row)=>(float)($row['jumlah']??0)*(float)($row['harga']??0),$rows))];
        $import = $pdf ? ['admits'=>$total($included,'jumlah'),'gross'=>$total($included,'gross'),'tax_amount'=>$total($included,'tax_amount'),'net'=>$total($included,'net')] : ['admits'=>$total($included,'jumlah'),'gross'=>array_sum(array_map(fn($row)=>(float)($row['jumlah']??0)*(float)($row['harga']??0),$included))];
        $mapping['summary'] = array_merge($mapping['summary'] ?? [], ['source_totals'=>$source,'import_totals'=>$import,'delta'=>array_map(fn($value,$key)=>$value-($import[$key]??0),$source,array_keys($source)),'imported_rows'=>count($included),'excluded_rows'=>count($rows)-count($included)]);
        return $mapping;
    }

    public function excludeImportPreview(Request $request)
    {
        $data = $request->validate(['token'=>'required|uuid','provider'=>'required|in:XXI,CGV,SAMS STUDIOS,NSC,CINEPOLIS PDF,PLATINUM PDF','row_id'=>'required|uuid','reason'=>'required|string|min:3|max:500','restore'=>'nullable|boolean']);
        $pdf = in_array($data['provider'], ['CINEPOLIS PDF', 'PLATINUM PDF'], true);
        $key = $pdf ? strtolower(explode(' ', $data['provider'])[0]).'_pdf_preview:'.$data['token'] : $this->legacyPreviewKey($data['token']);
        return Cache::lock('report-import-mutation', 120)->block(5, function () use ($data, $pdf, $key, $request) {
            $cached=$this->getImportPreview($key);
            if (!$cached || (!$pdf && ($cached['provider'] ?? null) !== $data['provider'])) return response()->json(['message'=>'Preview sudah kedaluwarsa. Silakan upload ulang file.'],422);
            if (($cached['created_by'] ?? null)!==(Auth::user()->uuid ?? null)) return response()->json(['message'=>'Preview ini bukan milik sesi pengguna aktif.'],403);
            $rows=$pdf ? $cached['parsed']['rows'] : $cached['rows']; $i=array_search($data['row_id'],array_column($rows,'row_id'),true);
            if ($i===false) throw ValidationException::withMessages(['row_id'=>'Baris tidak ditemukan pada preview ini.']);
            $rows[$i]['excluded']=!$request->boolean('restore'); $rows[$i]['exclusion_reason']=$rows[$i]['excluded'] ? trim($data['reason']) : null;
            $cached['exclusions'][]=['row_id'=>$data['row_id'],'reason'=>trim($data['reason']),'user_uuid'=>Auth::user()->uuid,'at'=>now()->toIso8601String()];
            if ($pdf) { $cached['parsed']['rows']=$rows; $method=$data['provider']==='CINEPOLIS PDF'?'mapCinepolisPreview':'mapPlatinumPreview'; $mapping=$this->$method($cached['parsed'],$cached['mapping']['cinema_uuid']??null); }
            else {
                $cached['rows']=$rows;
                $mapping=$this->mapLegacyPreview($rows,$data['provider'],$data['provider']==='XXI'&&($cached['source_type']??null)==='pdf');
                $mapping=$this->withPendingFreeAssignments($mapping, $cached['pending_free_assignments'] ?? []);
            }
            $mapping=$this->withImportTotals($mapping, $rows, $pdf); $mapping['source_type']=$cached['source_type']??($pdf?'pdf':'excel'); $cached['mapping']=$mapping; $this->putImportPreview($key,$cached,now()->addMinutes(30));
            return response()->json(array_merge($mapping,['status'=>'success','token'=>$data['token'],'message'=>'Baris dikeluarkan dari import. Data sumber tetap tersimpan di audit preview.']));
        });
    }

    public function correctImportPreview(Request $request)
    {
        $data = $request->validate([
            'token' => 'required|uuid',
            'provider' => 'required|in:XXI,CGV,SAMS STUDIOS,NSC,CINEPOLIS PDF,PLATINUM PDF',
            'row_id' => 'required|uuid',
            'changes' => 'required|array|min:1|max:14',
            'reason' => 'required|string|min:3|max:500',
        ]);
        $pdf = in_array($data['provider'], ['CINEPOLIS PDF', 'PLATINUM PDF'], true);
        $key = $pdf ? (strtolower(explode(' ', $data['provider'])[0]).'_pdf_preview:'.$data['token']) : $this->legacyPreviewKey($data['token']);
        return Cache::lock('report-import-mutation', 120)->block(5, function () use ($data, $pdf, $key, $request) {
            $cached = $this->getImportPreview($key);
            if (!$cached || (!$pdf && ($cached['provider'] ?? null) !== $data['provider'])) {
                return response()->json(['message' => 'Preview sudah kedaluwarsa. Silakan upload ulang file.'], 422);
            }
            if (($cached['created_by'] ?? null) !== (Auth::user()->uuid ?? null)) {
                return response()->json(['message' => 'Preview ini bukan milik sesi pengguna aktif.'], 403);
            }
            $rows = $pdf ? $cached['parsed']['rows'] : $cached['rows'];
            $index = array_search($data['row_id'], array_column($rows, 'row_id'), true);
            if ($index === false) throw ValidationException::withMessages(['row_id' => 'Baris tidak ditemukan pada preview ini.']);
            $row = $rows[$index];
            $rules = [
                'jam_tayang' => 'required|date_format:H:i',
                'studio' => 'required|string|max:30|regex:/^[A-Za-z ]*[0-9]{1,3}$/',
                'show' => 'required|integer|min:1|max:50',
                'jumlah' => 'required|integer|min:0|max:1000000',
                'harga' => 'required|numeric|min:0|max:100000000',
            ];
            $rules += $pdf ? [
                'tanggal' => 'required|date_format:Y-m-d',
                'type_tiket' => 'required|string|max:100',
            ] : [
                'tgl_tayang' => 'required|date_format:Y-m-d',
                'nama_film' => 'required|string|max:255',
                'source_cinema' => 'required|string|max:255',
                'source_city' => 'nullable|string|max:255',
                'ticket_name' => 'required|string|max:100',
            ];
            // XXI PDFs intentionally derive prices from the dated master, not operator input.
            if (!$pdf && $data['provider'] === 'XXI' && ($cached['source_type'] ?? null) === 'pdf') unset($rules['harga']);
            foreach ($data['changes'] as $field => $value) {
                if (!isset($rules[$field])) throw ValidationException::withMessages(['changes.'.$field => 'Kolom ini tidak boleh dikoreksi. Nilai turunan dan identitas master dihitung server.']);
            }
            $activeRules = array_intersect_key($rules, $data['changes']);
            $changes = Validator::make($data['changes'], $activeRules)->validate();
            $before = array_intersect_key($row, $changes);
            $row = array_replace($row, $changes);
            $ticket = mb_strtoupper($row[$pdf ? 'type_tiket' : 'ticket_name']);
            $original = $row['original_row'];
            $originalTicket = mb_strtoupper($original[$pdf ? 'type_tiket' : 'ticket_name']);
            $freeNames = ['FREE PASS', 'BOGOF', 'COMPLIMENTARY', 'COMPLEMENTARY'];
            if (in_array($ticket, $freeNames, true) !== in_array($originalTicket, $freeNames, true)) {
                throw ValidationException::withMessages(['changes' => 'Klasifikasi tiket berbayar/Free sumber tidak boleh diubah. Gunakan alokasi Free.']);
            }
            if (!$pdf && in_array($ticket, $freeNames, true)) $row['harga'] = 0;
            $financialChanged = (float)$row['jumlah'] !== (float)$original['jumlah'] || (float)$row['harga'] !== (float)$original['harga'];
            if ($pdf && $financialChanged) {
                // Preserve the original provider's effective tax and net contract (including
                // Platinum inclusive-net/deduction profiles), never assume net = gross - tax.
                $gross = round((float)$row['harga'] * (int)$row['jumlah'], 2);
                $baseGross = (float)$original['gross'];
                $row['gross'] = $gross;
                $row['tax_amount'] = $baseGross > 0 ? round($gross * $original['tax_amount'] / $baseGross, 2) : 0;
                $row['net'] = $baseGross > 0 ? round($gross * $original['net'] / $baseGross, 2) : 0;
                $row['tax_rate'] = $gross > 0 ? round($row['tax_amount'] / $gross * 100, 4) : 0;
            } elseif ($pdf) {
                foreach (['gross', 'tax_amount', 'tax_rate', 'net'] as $field) $row[$field] = $original[$field];
            } else {
                // Legacy imports resolve tax and net from current masters on every remap.
                $row['net'] = 0;
            }
            $row['correction_issues'] = [];
            if ($financialChanged) {
                $row['correction_issues'][] = 'Rekonsiliasi sumber baris '.$row['row_id'].': jumlah/harga hasil koreksi berbeda dari detail asli. Total sumber tetap otoritatif; pulihkan nilai asli atau unggah laporan sumber yang telah diperbaiki sebelum import.';
            }
            $rows[$index] = $row;
            $cached['corrections'][] = [
                'row_id' => $row['row_id'], 'original_row' => $original,
                'before' => $before, 'after' => array_intersect_key($row, $changes),
                'reason' => trim($data['reason']), 'user_uuid' => Auth::user()->uuid, 'at' => now()->toIso8601String(),
            ];
            if ($pdf) {
                $cached['parsed']['rows'] = $rows;
                foreach (['admits' => 'jumlah', 'gross' => 'gross', 'tax_amount' => 'tax_amount', 'net' => 'net'] as $total => $field) {
                    $cached['parsed']['totals'][$total] = array_sum(array_column($rows, $field));
                }
                $method = $data['provider'] === 'CINEPOLIS PDF' ? 'mapCinepolisPreview' : 'mapPlatinumPreview';
                $mapping = $this->$method($cached['parsed'], $cached['mapping']['cinema_uuid'] ?? null);
            } else {
                $cached['rows'] = $rows;
                $mapping = $this->mapLegacyPreview($cached['rows'], $data['provider'], $data['provider'] === 'XXI' && ($cached['source_type'] ?? null) === 'pdf');
                $mapping = $this->withPendingFreeAssignments($mapping, $cached['pending_free_assignments'] ?? []);
                if (isset($cached['mapping']['source_totals'])) $mapping['source_totals'] = $cached['mapping']['source_totals'];
            }
            $mapping = $this->withImportTotals($mapping, $rows, $pdf);
            $mapping['source_type'] = $cached['source_type'] ?? ($pdf ? 'pdf' : 'excel');
            $cached['mapping'] = $mapping;
            $this->putImportPreview($key, $cached, now()->addMinutes(30));
            return response()->json(array_merge($mapping, ['status' => 'success', 'token' => $data['token'], 'message' => 'Koreksi disimpan di preview. Belum ada laporan yang diimport.']));
        });
    }
}
