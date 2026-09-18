<?php
namespace Tests\Unit;
use App\Services\CinepointDailyPayloadParser;
use PHPUnit\Framework\TestCase;
class CinepointBrowserValidationTest extends TestCase
{
    private function payload(): array { return ['period_label'=>'Sep 17, 2026','source_total'=>2,'entries'=>[['source_movie_id'=>'1','rank'=>1,'title'=>'A','poster_url'=>null,'daily_admissions'=>10,'total_admissions'=>20],['source_movie_id'=>'2','rank'=>2,'title'=>'B','poster_url'=>null,'daily_admissions'=>5,'total_admissions'=>8]]]; }
    /** @dataProvider invalidCases */
    public function test_rejects_invalid_payload(string $case): void {
        $p=$this->payload();
        if($case==='duplicate') $p['entries'][1]['source_movie_id']='1';
        if($case==='rank') $p['entries'][1]['rank']=1;
        if($case==='missing') unset($p['entries'][0]['daily_admissions']);
        if($case==='number') $p['entries'][0]['daily_admissions']='invalid';
        if($case==='period') $p['period_label']='Feb 31, 2026';
        if($case==='count') array_pop($p['entries']);
        $this->expectException(\UnexpectedValueException::class);
        (new CinepointDailyPayloadParser)->parseBrowser($p);
    }
    public static function invalidCases(): array { return array_map(fn($v)=>[$v],['duplicate','rank','missing','number','period','count']); }
}
