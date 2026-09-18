<?php

namespace App\Services;

use Carbon\Carbon;
use UnexpectedValueException;

class CinepointDailyPayloadParser
{
    public function parseHtml(string $html, string $period): array
    {
        if (!preg_match('/<script[^>]+id=["\']ng-state["\'][^>]*>(.*?)<\/script>/s', $html, $match)) {
            throw new UnexpectedValueException('Cinepoint ng-state tidak ditemukan.');
        }
        $state = json_decode(html_entity_decode($match[1], ENT_QUOTES | ENT_HTML5), true, 512, JSON_THROW_ON_ERROR);
        $pages = [];
        foreach ($state as $item) {
            if (is_array($item) && strpos((string) ($item['u'] ?? ''), '/box-office/daily') !== false) {
                $pages[] = $item;
            }
        }
        if (!$pages) throw new UnexpectedValueException('Payload daily ranking tidak ditemukan.');
        return $this->parsePages($pages, $period);
    }

    public function parseBrowser(array $payload): array
    {
        $label = trim((string) ($payload['period_label'] ?? ''));
        try {
            $date = Carbon::createFromFormat('!M j, Y', $label, 'Asia/Jakarta');
            if (!$date || $date->format('M j, Y') !== $label) throw new UnexpectedValueException('Periode daily tidak valid.');
            $period = $date->toDateString();
        } catch (\Throwable $e) { throw new UnexpectedValueException('Periode daily tidak valid.'); }
        $total = (int) ($payload['source_total'] ?? 0);
        $rows = $payload['entries'] ?? null;
        if ($total < 1 || !is_array($rows) || count($rows) !== $total) {
            throw new UnexpectedValueException("Payload browser tidak lengkap: {$total} film diharapkan, " . (is_array($rows) ? count($rows) : 0) . ' diterima.');
        }
        $seen = [];
        $ranks = [];
        $entries = [];
        foreach ($rows as $row) {
            foreach (['source_movie_id','rank','title','daily_admissions','total_admissions'] as $key) if (!array_key_exists($key, $row)) throw new UnexpectedValueException("Field {$key} browser tidak tersedia.");
            $id = (string) $row['source_movie_id'];
            $rank = filter_var($row['rank'], FILTER_VALIDATE_INT, ['options'=>['min_range'=>1]]);
            $daily = filter_var($row['daily_admissions'], FILTER_VALIDATE_INT, ['options'=>['min_range'=>0]]);
            $cumulative = filter_var($row['total_admissions'], FILTER_VALIDATE_INT, ['options'=>['min_range'=>0]]);
            if ($id === '' || isset($seen[$id])) throw new UnexpectedValueException("Duplikat atau ID film kosong: {$id}.");
            if ($rank === false || isset($ranks[$rank]) || trim((string) $row['title']) === '' || $daily === false || $cumulative === false) throw new UnexpectedValueException("Baris film {$id} tidak valid atau rank duplikat.");
            $seen[$id] = true;
            $ranks[$rank] = true;
            $entries[] = ['source_movie_id'=>$id,'title'=>trim((string)$row['title']),'poster_url'=>$this->safeUrl($row['poster_url'] ?? null),'daily_admissions'=>(int)$row['daily_admissions'],'total_admissions'=>(int)$row['total_admissions'],'rank'=>(int)$row['rank']];
        }
        usort($entries, fn($a,$b)=>$a['rank'] <=> $b['rank']);
        return ['period_date'=>$period,'source_total'=>$total,'collected_count'=>count($entries),'partial'=>false,'pages'=>[],'entries'=>$entries];
    }

    public function parsePages(array $pages, string $period): array
    {
        try { $date = Carbon::createFromFormat('Y-m-d', $period)->format('Y-m-d'); }
        catch (\Throwable $e) { throw new UnexpectedValueException('Periode daily tidak valid.'); }
        $entries = []; $seen = []; $total = null; $limit = null; $pageNumbers = [];
        foreach ($pages as $page) {
            $list = $page['b']['response_output']['list'] ?? null;
            if (!is_array($list) || !isset($list['pagination'], $list['content']) || !is_array($list['content'])) throw new UnexpectedValueException('Struktur pagination daily tidak valid.');
            $pagination = $list['pagination'];
            $total = $total === null ? (int) ($pagination['total'] ?? -1) : $total;
            $limit = (int) ($pagination['limit'] ?? 0); $pageNumbers[] = (int) ($pagination['page'] ?? -1);
            if ($total < 0 || $limit < 1) throw new UnexpectedValueException('Metadata pagination daily tidak valid.');
            foreach ($list['content'] as $row) {
                foreach (['id','title','admission','total_admission'] as $key) if (!array_key_exists($key, $row)) throw new UnexpectedValueException("Field {$key} daily tidak tersedia.");
                $id = (string) $row['id'];
                if (isset($seen[$id])) throw new UnexpectedValueException("Duplikat film {$id} pada pagination.");
                if (!is_numeric($row['admission']) || !is_numeric($row['total_admission']) || (int)$row['admission'] < 0 || (int)$row['total_admission'] < 0) throw new UnexpectedValueException("Admissions film {$id} tidak valid.");
                $seen[$id] = true;
                $entries[] = ['source_movie_id'=>$id,'title'=>trim((string)$row['title']),'poster_url'=>$this->safeUrl($row['image_title'] ?? null),'daily_admissions'=>(int)$row['admission'],'total_admissions'=>(int)$row['total_admission'],'rank'=>(int)($row['rank']['current_rank'] ?? count($entries)+1)];
            }
        }
        usort($entries, fn($a,$b)=>$a['rank'] <=> $b['rank']);
        return ['period_date'=>$date,'source_total'=>$total,'collected_count'=>count($entries),'partial'=>count($entries)!==$total,'pages'=>$pageNumbers,'entries'=>$entries];
    }

    private function safeUrl($url): ?string
    {
        if (!$url) return null;
        $parts = parse_url((string)$url);
        return isset($parts['scheme'], $parts['host']) && in_array(strtolower($parts['scheme']), ['http','https'], true) ? (string)$url : null;
    }
}
