<?php

namespace App\Services;

use Carbon\Carbon;
use UnexpectedValueException;

class CinepointDailyPayloadParser
{
    private const MAX_ENTRIES = 100;
    private const MAX_ADMISSIONS = 999999999999;

    public function parseBrowser(array $payload): array
    {
        $this->assertKeys($payload, ['period_label', 'source_total', 'entries'], 'payload');
        if (!is_string($payload['period_label']) || !is_int($payload['source_total']) || !is_array($payload['entries'])) {
            throw new UnexpectedValueException('Tipe payload collector tidak valid.');
        }
        $label = $payload['period_label'];
        try {
            $date = Carbon::createFromFormat('!M j, Y', $label, 'Asia/Jakarta');
            if (!$date || $date->format('M j, Y') !== $label) throw new UnexpectedValueException();
        } catch (\Throwable $e) {
            throw new UnexpectedValueException('Periode daily tidak valid.');
        }
        if ($date->startOfDay()->greaterThan(Carbon::now('Asia/Jakarta')->startOfDay())) {
            throw new UnexpectedValueException('Periode daily tidak boleh di masa depan.');
        }
        $total = $payload['source_total'];
        if ($total < 1 || $total > self::MAX_ENTRIES || count($payload['entries']) !== $total) {
            throw new UnexpectedValueException('Payload browser tidak lengkap atau melampaui batas.');
        }
        $seen = []; $ranks = []; $entries = [];
        foreach ($payload['entries'] as $row) {
            if (!is_array($row)) throw new UnexpectedValueException('Baris film harus berupa object.');
            $this->assertKeys($row, ['source_movie_id','rank','title','poster_url','daily_admissions','total_admissions'], 'baris film');
            if (!is_string($row['source_movie_id']) || !is_int($row['rank']) || !is_string($row['title']) ||
                !is_int($row['daily_admissions']) || !is_int($row['total_admissions']) ||
                (!is_null($row['poster_url']) && !is_string($row['poster_url']))) {
                throw new UnexpectedValueException('Tipe baris film tidak valid.');
            }
            $id = trim($row['source_movie_id']); $title = trim($row['title']); $rank = $row['rank'];
            if ($id === '' || strlen($id) > 191 || isset($seen[$id]) || $title === '' || mb_strlen($title) > 255 ||
                $rank < 1 || $rank > $total || isset($ranks[$rank]) || $row['daily_admissions'] < 0 ||
                $row['total_admissions'] < $row['daily_admissions'] || $row['total_admissions'] > self::MAX_ADMISSIONS) {
                throw new UnexpectedValueException('Nilai baris film tidak valid.');
            }
            $seen[$id] = true; $ranks[$rank] = true;
            $entries[] = ['source_movie_id'=>$id,'rank'=>$rank,'title'=>$title,
                'poster_url'=>$this->safeHttpsUrl($row['poster_url']),
                'daily_admissions'=>$row['daily_admissions'],'total_admissions'=>$row['total_admissions']];
        }
        $rankValues = array_keys($ranks); sort($rankValues);
        if ($rankValues !== range(1, $total)) throw new UnexpectedValueException('Rank film harus berurutan tanpa celah.');
        usort($entries, function ($a, $b) { return $a['rank'] <=> $b['rank']; });
        return ['period_date'=>$date->toDateString(),'source_total'=>$total,'collected_count'=>$total,'partial'=>false,'pages'=>[],'entries'=>$entries];
    }

    public function parseHtml(string $html, string $period): array
    {
        if (!preg_match('/<script[^>]+id=["\']ng-state["\'][^>]*>(.*?)<\/script>/s', $html, $match)) throw new UnexpectedValueException('Cinepoint ng-state tidak ditemukan.');
        $state = json_decode(html_entity_decode($match[1], ENT_QUOTES | ENT_HTML5), true, 512, JSON_THROW_ON_ERROR);
        $pages = array_values(array_filter($state, function ($item) { return is_array($item) && strpos((string)($item['u'] ?? ''), '/box-office/daily') !== false; }));
        if (!$pages) throw new UnexpectedValueException('Payload daily ranking tidak ditemukan.');
        return $this->parsePages($pages, $period);
    }

    public function parsePages(array $pages, string $period): array
    {
        try { $date = Carbon::createFromFormat('!Y-m-d', $period, 'Asia/Jakarta'); if ($date->format('Y-m-d') !== $period) throw new UnexpectedValueException(); }
        catch (\Throwable $e) { throw new UnexpectedValueException('Periode daily tidak valid.'); }
        $rows=[]; $total=null;
        foreach ($pages as $page) {
            $list=$page['b']['response_output']['list']??null;
            if (!is_array($list) || !is_array($list['content']??null)) throw new UnexpectedValueException('Struktur pagination daily tidak valid.');
            $total=$total===null?(int)($list['pagination']['total']??0):$total;
            foreach($list['content'] as $row) $rows[]=['source_movie_id'=>(string)($row['id']??''),'rank'=>(int)($row['rank']['current_rank']??0),'title'=>(string)($row['title']??''),'poster_url'=>$row['image_title']??null,'daily_admissions'=>$this->strictExternalInt($row['admission']??null),'total_admissions'=>$this->strictExternalInt($row['total_admission']??null)];
        }
        return $this->parseBrowser(['period_label'=>$date->format('M j, Y'),'source_total'=>$total,'entries'=>$rows]);
    }

    private function strictExternalInt($value): int
    {
        if (!is_int($value) && !(is_string($value) && preg_match('/^\d+$/D', $value))) throw new UnexpectedValueException('Admissions film tidak valid.');
        return (int)$value;
    }

    private function safeHttpsUrl($url): ?string
    {
        if ($url === null) return null;
        if (strlen($url) > 2048 || filter_var($url, FILTER_VALIDATE_URL) === false || strtolower((string)parse_url($url, PHP_URL_SCHEME)) !== 'https' || strtolower((string) parse_url($url, PHP_URL_HOST)) !== 'cinepoint-assets.s3.amazonaws.com' || parse_url($url, PHP_URL_USER) !== null || parse_url($url, PHP_URL_PASS) !== null || parse_url($url, PHP_URL_PORT) !== null) {
            throw new UnexpectedValueException('URL poster harus HTTPS yang valid.');
        }
        return $url;
    }

    private function assertKeys(array $value, array $expected, string $label): void
    {
        $keys=array_keys($value); sort($keys); sort($expected);
        if ($keys !== $expected) throw new UnexpectedValueException("Struktur {$label} tidak valid.");
    }
}
