<?php

namespace App\Services\Reports;

use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

class KcmXlsxParser
{
    public function parse(string $path): array
    {
        if (!is_file($path) || !is_readable($path)) throw new \InvalidArgumentException('File Excel KCM tidak dapat dibaca.');
        try { $book = IOFactory::load($path); } catch (\Throwable $e) { throw new \InvalidArgumentException('Isi Excel KCM tidak dapat dibaca.', 0, $e); }
        $all = ['rows'=>[], 'audit'=>[], 'warnings'=>[], 'totals'=>['sold'=>0.0,'free'=>0.0,'promo'=>0.0,'gross'=>0.0], 'cinema'=>null, 'city'=>null, 'date'=>null, 'layout'=>null];
        foreach ($book->getWorksheetIterator() as $sheet) {
            $parsed = $this->parseSheet($sheet->toArray(null, true, true, false), $sheet->getTitle());
            if (!$parsed) continue;
            foreach (['cinema','city','date','layout'] as $key) $all[$key] ??= $parsed[$key];
            $all['rows'] = array_merge($all['rows'], $parsed['rows']); $all['audit'] = array_merge($all['audit'], $parsed['audit']); $all['warnings'] = array_merge($all['warnings'], $parsed['warnings']);
            foreach ($all['totals'] as $key=>$_) $all['totals'][$key] += $parsed['totals'][$key];
        }
        if (!$all['rows']) throw new \InvalidArgumentException('Tidak ada detail penjualan KCM yang dapat diparse dari workbook.');
        return ['layout'=>$all['layout'], 'cinema_name'=>$all['cinema'], 'city'=>$all['city'] ?? '', 'report_date'=>$all['date'], 'rows'=>$all['rows'], 'row_audit'=>$all['audit'], 'source_totals'=>$all['totals'], 'blocking_warnings'=>array_values(array_unique($all['warnings']))];
    }

    private function parseSheet(array $rows, string $sheet): ?array
    {
        $header = null; foreach ($rows as $i=>$row) if (collect($row)->contains(fn($v)=>preg_match('/^SHOW\s*\d+$/i', trim((string)$v)))) { $header=$i; break; }
        if ($header === null) return null;
        $firstHeader = $this->norm($rows[$header][0] ?? '');
        $sinemaku = (in_array($this->norm($rows[$header][0] ?? ''), ['KOTA', 'CITY'], true) && $this->norm($rows[$header][1] ?? '') === 'MOVIE') || collect($rows[$header + 1] ?? [])->contains(fn ($value) => $this->norm($value) === 'PROMO');
        $layout = $sinemaku ? 'SINEMAKU' : 'EXTERNAL';
        $cinema = $this->metadata($rows, ['NAMA BIOSKOP','CINEMA']) ?? $this->fromText($rows, '/NAMA\s+BIOSKOP\s*:\s*(.+)/i') ?? $this->value($rows[0][0] ?? null);
        $city = $sinemaku ? ($this->metadata($rows, ['CITY']) ?? $this->value($rows[1][0] ?? null)) : $this->metadata($rows, ['CITY']);
        $date = $this->metadata($rows, ['DATE','REPORT DATE','TANGGAL']) ?? $this->dateFromAnyCell($rows) ?? $this->fromText($rows, '/(?:HARI\s*\/?\s*TANGGAL|TANGGAL)\s*:?\s*(.+)/i');
        $filmColumn = $sinemaku ? 1 : 2;
        $studioColumn = $sinemaku ? 3 : 1;
        $priceColumn = $sinemaku ? (in_array($this->norm($rows[$header][4] ?? ''), ['HTM','PRICE'], true) ? 4 : 3) : $this->headerColumn($rows[$header], ['HTM','PRICE']);
        $groups=[]; foreach ($rows[$header] as $col=>$value) if (preg_match('/^SHOW\s*(\d+)$/i', trim((string)$value), $m)) $groups[]=['show'=>(int)$m[1], 'col'=>(int)$col];
        if (!$groups) return null;
        $sub = $this->findSubHeader($rows, $header, $groups[0]['col']);
        $totals = $this->totalColumns($rows[$header], $sub === null ? [] : $rows[$sub]);
        $out=['rows'=>[], 'audit'=>[], 'warnings'=>[], 'totals'=>['sold'=>0.0,'free'=>0.0,'promo'=>0.0,'gross'=>0.0], 'cinema'=>$cinema, 'city'=>$city, 'date'=>$this->date($date), 'layout'=>$layout];
        for ($i=($sub ?? $header)+1; $i<count($rows); $i++) {
            $line=$rows[$i];
            $film=$this->value($line[$filmColumn] ?? null);
            $numericKota = $sinemaku && $this->isNumericValue($line[0] ?? null);
            $studio=$this->value($line[$numericKota ? 0 : $studioColumn] ?? null);
            $price=$this->money($line[$priceColumn] ?? null);
            if ($this->norm($line[0] ?? '') === 'TOTAL' || $this->norm($line[0] ?? '') === 'NOTE' || $this->norm($line[0] ?? '') === 'GRAND TOTAL') break;
            if ($film === '' || $studio === '' || $price === null) continue;
            $date = $sinemaku ? $out['date'] : ($this->date($line[0] ?? null) ?? $out['date']); if (!$date) throw new \InvalidArgumentException("Tanggal KCM tidak dapat dibaca pada sheet $sheet baris ".($i+1).'.');
            $sold=0; $free=0; $promo=0; $rowStart=count($out['rows']);
            foreach ($groups as $group) {
                $labels = $this->labels($rows, $header, $sub, $group['col'], $sinemaku ? 3 : 2);
                $paid=$this->number($line[$group['col'] + ($labels['sold'] ?? 0)] ?? null); $fp=$this->number($line[$group['col'] + ($labels['free'] ?? 1)] ?? null); $pr=$sinemaku ? $this->number($line[$group['col'] + ($labels['promo'] ?? 2)] ?? null) : 0;
                $sold += $paid; $free += $fp; $promo += $pr;
                if ($paid > 0) $out['rows'][]=$this->row($sheet,$i+1,$date,$film,$cinema,$city,$studio,'REGULAR',$group['show'],$paid,$price);
                if ($fp > 0) $out['rows'][]=$this->row($sheet,$i+1,$date,$film,$cinema,$city,$studio,'FREE PASS',$group['show'],$fp,0.0);
                // KCM Sinemaku explicitly labels Promo as BOGO. It is a complimentary
                // ticket with zero canonical price; the printed promo amount remains audit-only.
                if ($pr > 0 && $sinemaku) $out['rows'][]=$this->row($sheet,$i+1,$date,$film,$cinema,$city,$studio,'BOGOF',$group['show'],$pr,0.0);
            }
            $printedSold=$this->number($line[$totals['sold']] ?? null); $printedFree=$this->number($line[$totals['free']] ?? null); $printedPromo=$sinemaku?$this->number($line[$totals['promo']] ?? null):0; $printedGross=$this->money($line[$totals['gross']] ?? null) ?? 0.0;
            if (!$this->same($sold,$printedSold)) throw new \InvalidArgumentException("Total sold tidak sama dengan detail show pada sheet $sheet baris ".($i+1).'.');
            if (!$this->same($free,$printedFree)) throw new \InvalidArgumentException("Total free tidak sama dengan detail show pada sheet $sheet baris ".($i+1).'.');
            if ($sinemaku && !$this->same($promo,$printedPromo)) throw new \InvalidArgumentException("Total promo tidak sama dengan detail show pada sheet $sheet baris ".($i+1).'.');
            $freeIsPaid = !$sinemaku && $this->same($printedGross, ($sold + $free) * $price);
            if (!$this->same($printedGross, $sold * $price) && !$freeIsPaid) throw new \InvalidArgumentException("Jumlah uang tidak sama dengan harga × sold/free pada sheet $sheet baris ".($i+1).'.');
            if ($freeIsPaid) for ($j=$rowStart; $j<count($out['rows']); $j++) if ($out['rows'][$j]['ticket_name'] === 'FREE PASS') { $out['rows'][$j]['harga']=$price; $out['rows'][$j]['net']=$out['rows'][$j]['jumlah']*$price; }
            $audit=['source_sheet'=>$sheet,'source_row'=>$i+1,'film'=>$film,'studio'=>$studio,'sold'=>$sold,'free'=>$free,'promo'=>$promo,'printed_sold'=>$printedSold,'printed_free'=>$printedFree,'printed_promo'=>$printedPromo,'printed_gross'=>$printedGross]; $out['audit'][]=$audit;
            foreach (['sold'=>$sold,'free'=>$free,'promo'=>$promo] as $key=>$amount) $out['totals'][$key]+=$amount; $out['totals']['gross'] += $printedGross;
        }
        return $out;
    }
    private function row($sheet,$row,$date,$film,$cinema,$city,$studio,$ticket,$show,$jumlah,$harga): array { $id=hash('sha256', "$sheet|$row|$ticket|$show"); return ['source_id'=>$id,'source_sheet'=>$sheet,'source_row'=>$row,'tgl_tayang'=>$date,'nama_film'=>$this->norm($film),'source_cinema'=>$this->norm($cinema),'source_city'=>$this->norm($city),'studio'=>$this->norm($studio),'ticket_name'=>$ticket,'jam_tayang'=>null,'show'=>(string)$show,'jumlah'=>(float)$jumlah,'harga'=>(float)$harga,'tax'=>0,'net'=>(float)$jumlah*(float)$harga]; }
    private function headerColumn(array $header, array $names): int { foreach ($header as $i => $value) if (in_array($this->norm($value), $names, true)) return (int) $i; return 0; }
    private function findSubHeader($rows,$header,$col): ?int { for($i=$header+1;$i<min(count($rows),$header+4);$i++) if (in_array($this->norm($rows[$i][$col] ?? ''),['SOLD','SO'])) return $i; return null; }
    private function labels($rows,$header,$sub,$col,$count): array { $out=[]; for($i=0;$i<$count;$i++){ $v=$this->norm($rows[$sub][$col+$i] ?? ''); if(in_array($v,['SOLD','SO']))$out['sold']=$i; elseif(in_array($v,['FREE','FP']))$out['free']=$i; elseif($v==='PROMO')$out['promo']=$i; } return $out; }
    private function totalColumns($head,$sub): array { $start=null; $labels=[]; foreach($head as $i=>$v){ $n=$this->norm($v); if(str_starts_with($n,'TOTAL')) { $start ??= $i; if(str_contains($n,'SOLD')||preg_match('/TOTAL\s+SO$/',$n))$labels['sold']=$i; elseif(str_contains($n,'FREE')||preg_match('/TOTAL\s+FP$/',$n))$labels['free']=$i; elseif(str_contains($n,'PROMO'))$labels['promo']=$i; elseif(str_contains($n,'SALES'))$labels['gross']=$i; }} if($start===null) throw new \InvalidArgumentException('Kolom total KCM tidak ditemukan.'); foreach($sub as $i=>$v){ if($i<$start)continue; $n=$this->norm($v); if(in_array($n,['SOLD','SO']))$labels['sold']=$i; elseif(in_array($n,['FREE','FP']))$labels['free']=$i; elseif($n==='PROMO')$labels['promo']=$i;} return ['sold'=>$labels['sold']??$start,'free'=>$labels['free']??$start+1,'promo'=>$labels['promo']??$start+2,'gross'=>$labels['gross']??$this->nextTotal($head,$start)]; }
    private function nextTotal($head,$start): int { for($i=$start+1;$i<count($head);$i++) if(str_starts_with($this->norm($head[$i]),'TOTAL')) return $i; return $start+3; }
    private function metadata($rows,$labels): ?string { foreach($rows as $r) foreach($labels as $label) if($this->norm($r[0]??'')===$label) foreach(array_slice($r,1) as $v) if($this->value($v)!=='') return $this->value($v); return null; }
    private function fromText($rows,$pattern): ?string { foreach($rows as $r) foreach($r as $v) if(preg_match($pattern,(string)$v,$m)) return trim($m[1]); return null; }
    private function dateFromAnyCell(array $rows): ?string { foreach(array_slice($rows,0,8) as $row) foreach($row as $value){$text=$this->norm($value); if(preg_match('/(\d{1,2}\s+(?:JANUARI|FEBRUARI|MARET|APRIL|MEI|JUNI|JULI|AGUSTUS|SEPTEMBER|OKTOBER|NOVEMBER|DESEMBER)\s+\d{4})/u',$text,$m)) return $this->date($m[1]);} return null; }
    private function date($v): ?string { if($v===null||trim((string)$v)==='')return null; try { if(is_numeric($v))return ExcelDate::excelToDateTimeObject($v)->format('Y-m-d'); $s=trim((string)$v); $s=str_ireplace(['JANUARI','FEBRUARI','MARET','APRIL','MEI','JUNI','JULI','AGUSTUS','SEPTEMBER','OKTOBER','NOVEMBER','DESEMBER'],['January','February','March','April','May','June','July','August','September','October','November','December'],$s); foreach(['d/m/Y','d-m-Y','Y-m-d','d F Y','j F Y'] as $format){$d=\DateTimeImmutable::createFromFormat('!'.$format,$s); $e=\DateTimeImmutable::getLastErrors(); if($d!==false&&($e===false||($e['warning_count']===0&&$e['error_count']===0)))return $d->format('Y-m-d');} return Carbon::parse($s)->format('Y-m-d'); }catch(\Throwable $e){return null;} }
    private function money($v): ?float { $v=trim((string)$v); if($v===''||preg_match('/^-+$/',$v))return null; $v=preg_replace('/[^0-9,.-]/','',$v); if(str_contains($v,',')&&str_contains($v,'.')){ $lastComma=strrpos($v,','); $lastDot=strrpos($v,'.'); $v=$lastComma>$lastDot?str_replace(['.'],[''],$v):str_replace([','],[''],$v); if($lastComma>$lastDot)$v=str_replace(',','.',$v); } elseif(str_contains($v,','))$v=str_replace(',','',$v); elseif(preg_match('/^-?\d{1,3}(?:\.\d{3})+$/',$v))$v=str_replace('.','',$v); return is_numeric($v)?(float)$v:null; }
    private function number($v): float { return $this->money($v) ?? 0.0; }
    private function isNumericValue($value): bool { return $value !== null && trim((string) $value) !== '' && is_numeric(trim((string) $value)); }
    private function same($a,$b): bool{return abs(round($a,2)-round($b,2))<=0.01;}
    private function value($v): string{return trim(preg_replace('/\s+/u',' ',(string)$v));}
    private function norm($v): string{return mb_strtoupper($this->value($v),'UTF-8');}
}
