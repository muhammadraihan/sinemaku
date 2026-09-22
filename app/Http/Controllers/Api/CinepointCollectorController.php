<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\CinepointDailyIngestor;
use App\Services\CinepointRemoteSyncQueue;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Database\QueryException;

class CinepointCollectorController extends Controller
{
    public function store(Request $request, CinepointDailyIngestor $ingestor)
    {
        return $this->idempotent($request, function () use ($request, $ingestor) {
            $payload = $this->jsonObject($request);
            $result = $ingestor->ingest($payload);
            return [$result, $result['status'] === 'duplicate' ? 200 : 201];
        }, 'Snapshot collector gagal disimpan.');
    }

    public function claim(Request $request, CinepointRemoteSyncQueue $queue)
    {
        return $this->idempotent($request, function () use ($request, $queue) {
            $payload = $this->jsonObject($request);
            if (array_keys($payload) !== ['worker_id'] || !is_string($payload['worker_id']) || trim($payload['worker_id']) === '' || strlen($payload['worker_id']) > 100) throw new \UnexpectedValueException('Worker collector tidak valid.');
            Cache::put('cinepoint-collector-heartbeat', now('Asia/Jakarta')->toIso8601String(), 86400);
            return [$queue->claim(trim($payload['worker_id'])), 200];
        }, 'Claim collector gagal diproses.');
    }

    public function result(Request $request, int $id, CinepointRemoteSyncQueue $queue)
    {
        return $this->idempotent($request, function () use ($request, $id, $queue) {
            $payload = $this->jsonObject($request); $keys = array_keys($payload); sort($keys);
            $allowed = ($payload['status'] ?? null) === 'success' ? ['lease_token', 'snapshot', 'status'] : ['error_code', 'lease_token', 'status']; sort($allowed);
            if ($keys !== $allowed || !is_string($payload['lease_token']) || !in_array($payload['status'] ?? null, ['success', 'failed'], true) ||
                ($payload['status'] === 'success' && !is_array($payload['snapshot'])) ||
                ($payload['status'] === 'failed' && (!is_string($payload['error_code']) || !preg_match('/^[a-z0-9_]{1,64}$/D', $payload['error_code'])))) throw new \UnexpectedValueException('Hasil worker collector tidak valid.');
            return [$queue->finish($id, $payload['lease_token'], $payload['status'], $payload['snapshot'] ?? null, $payload['error_code'] ?? null), 200];
        }, 'Hasil collector gagal disimpan.');
    }

    private function idempotent(Request $request, callable $action, string $failure)
    {
        $delivery = (string) $request->header('X-Cinepoint-Delivery');
        $fingerprint = hash('sha256', $request->method()."\n/".$request->path()."\n".$request->getContent());
        try {
            return DB::transaction(function () use ($delivery, $fingerprint, $action) {
                $prior = DB::table('cinepoint_collector_deliveries')->where('delivery_id', $delivery)->lockForUpdate()->first();
                if ($prior) {
                    if (!hash_equals($prior->request_fingerprint, $fingerprint)) return response()->json(['message' => 'Conflicting collector delivery.'], 409);
                    return response(Crypt::decryptString($prior->response_body), 200)->header('Content-Type', 'application/json');
                }
                DB::table('cinepoint_collector_deliveries')->insert(['delivery_id'=>$delivery, 'request_fingerprint'=>$fingerprint, 'created_at'=>now(), 'updated_at'=>now()]);
                [$payload, $status] = $action();
                $body = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
                DB::table('cinepoint_collector_deliveries')->where('delivery_id',$delivery)->update(['response_status'=>$status, 'response_body'=>Crypt::encryptString($body), 'updated_at'=>now()]);
                return response($body, $status)->header('Content-Type', 'application/json');
            }, 3);
        } catch (QueryException $e) {
            $prior = DB::table('cinepoint_collector_deliveries')->where('delivery_id',$delivery)->first();
            if ($prior && hash_equals($prior->request_fingerprint,$fingerprint)) return response(Crypt::decryptString($prior->response_body),200)->header('Content-Type','application/json');
            if ($prior) return response()->json(['message'=>'Conflicting collector delivery.'],409);
            return response()->json(['message'=>$failure],500);
        } catch (\JsonException|\UnexpectedValueException $e) { return response()->json(['message' => $e instanceof \JsonException ? 'JSON collector tidak valid.' : $e->getMessage()], 422); }
        catch (\RuntimeException $e) { return response()->json(['message'=>$e->getCode() === 409 ? 'Lease invalid or expired.' : $failure], $e->getCode() === 409 ? 409 : 500); }
        catch (\Throwable $e) { return response()->json(['message'=>$failure], 500); }
    }

    private function jsonObject(Request $request): array
    {
        $payload = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);
        if (!is_array($payload) || array_values($payload) === $payload) throw new \UnexpectedValueException('Payload collector harus object JSON.');
        return $payload;
    }
}
