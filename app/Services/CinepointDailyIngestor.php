<?php

namespace App\Services;

use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

class CinepointDailyIngestor
{
    public function ingest(array $payload): array
    {
        $parsed = app(CinepointDailyPayloadParser::class)->parseBrowser($payload);
        $fingerprint = hash('sha256', json_encode([
            'period_date'=>$parsed['period_date'], 'source_total'=>$parsed['source_total'], 'entries'=>$parsed['entries'],
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
        try {
            return DB::transaction(function () use ($parsed, $fingerprint) {
                $existing=DB::table('cinepoint_daily_snapshots')->where('payload_fingerprint',$fingerprint)->first();
                if ($existing) return $this->duplicate($existing);
                $now=now();
                $id=DB::table('cinepoint_daily_snapshots')->insertGetId(['period_date'=>$parsed['period_date'],'payload_fingerprint'=>$fingerprint,'status'=>'running','source_total'=>$parsed['source_total'],'collected_count'=>0,'is_complete'=>false,'started_at'=>$now,'created_at'=>$now,'updated_at'=>$now]);
                foreach($parsed['entries'] as $entry) DB::table('cinepoint_daily_entries')->insert(array_merge($entry,['snapshot_id'=>$id,'created_at'=>$now,'updated_at'=>$now]));
                $stored=DB::table('cinepoint_daily_entries')->where('snapshot_id',$id)->count();
                if ($stored !== $parsed['source_total']) throw new \RuntimeException('Snapshot collector gagal diverifikasi.');
                DB::table('cinepoint_daily_snapshots')->where('id',$id)->update(['status'=>'success','collected_count'=>$stored,'is_complete'=>true,'finished_at'=>now(),'updated_at'=>now()]);
                return ['snapshot_id'=>$id,'period_date'=>$parsed['period_date'],'source_total'=>$parsed['source_total'],'status'=>'stored'];
            }, 3);
        } catch (QueryException $e) {
            $existing=DB::table('cinepoint_daily_snapshots')->where('payload_fingerprint',$fingerprint)->first();
            if ($existing) return $this->duplicate($existing);
            throw $e;
        }
    }
    private function duplicate($snapshot): array
    {
        return ['snapshot_id'=>$snapshot->id,'period_date'=>$snapshot->period_date,'source_total'=>(int)$snapshot->source_total,'status'=>'duplicate'];
    }
}
