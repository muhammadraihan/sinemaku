<?php

namespace Tests\Unit;

use App\Services\CinepointDailyPayloadParser;
use PHPUnit\Framework\TestCase;

class CinepointDailyPayloadParserTest extends TestCase
{
    public function test_browser_payload_requires_all_unique_ranked_rows(): void
    {
        $payload = ['period_label'=>'Sep 17, 2026','source_total'=>1,'entries'=>[['source_movie_id'=>'1','rank'=>1,'title'=>'A','poster_url'=>null,'daily_admissions'=>1,'total_admissions'=>2]]];
        $this->assertSame('2026-09-17', (new CinepointDailyPayloadParser())->parseBrowser($payload)['period_date']);
        $payload['source_total'] = 2;
        $this->expectException(\UnexpectedValueException::class);
        (new CinepointDailyPayloadParser())->parseBrowser($payload);
    }

    public function test_it_parses_unique_complete_daily_entries(): void
    {
        $parser = new CinepointDailyPayloadParser();
        $result = $parser->parsePages([["b" => ["response_output" => ["list" => ["pagination" => ["page" => 0, "limit" => 10, "total" => 2], "content" => [
            ["id" => 1, "title" => "Film A", "image_title" => "https://example.test/a.jpg", "admission" => 10, "total_admission" => 20, "rank" => ["current_rank" => 1]],
            ["id" => 2, "title" => "Film B", "image_title" => null, "admission" => 5, "total_admission" => 8, "rank" => ["current_rank" => 2]],
        ]]]]]], '2026-09-17');
        $this->assertSame(2, $result['source_total']);
        $this->assertCount(2, $result['entries']);
        $this->assertFalse($result['partial']);
    }

    public function test_it_rejects_malformed_and_duplicate_entries(): void
    {
        $this->expectException(\UnexpectedValueException::class);
        (new CinepointDailyPayloadParser())->parsePages([["b" => ["response_output" => ["list" => ["pagination" => ["page" => 0, "limit" => 10, "total" => 2], "content" => [["id" => 1, "title" => "A", "admission" => 1, "total_admission" => 1], ["id" => 1, "title" => "A", "admission" => 1, "total_admission" => 1]]]]]]], 'not-a-date');
    }
}
