<?php

namespace Tests\Unit;

use App\Services\Reports\XxiPdfParser;
use PHPUnit\Framework\TestCase;

class XxiPdfParserTest extends TestCase
{
    /** @test */
    public function it_parses_xxi_layout_text_into_paid_rows_and_pending_free_assignments(): void
    {
        $result = (new XxiPdfParser())->parseText(<<<'PDF'
FILM MEMBURU PEMANGSA
RELEASE: 24 SEPTEMBER 2026
SHOW: SABTU, 26 SEPTEMBER 2026
GROUP XXI
CINEMA St Kp 1 2 3 4 5 6 7 PTN FP
** JAKARTA **
BLOK M XXI 3 314 - 17 33 75 66 71 - 262 4
** BANDUNG **
BRAGA XXI 3 124 - - - - - - 28 28 -
TOTAL 290 4
PDF);

        $this->assertSame('MEMBURU PEMANGSA', $result['film_name']);
        $this->assertSame('2026-09-26', $result['report_date']);
        $this->assertSame([
            [
                'source_row' => 7,
                'tgl_tayang' => '2026-09-26',
                'nama_film' => 'MEMBURU PEMANGSA',
                'source_cinema' => 'BLOK M XXI',
                'source_city' => 'JAKARTA',
                'studio' => '3',
                'capacity' => 314,
                'ticket_name' => 'REGULAR',
                'jam_tayang' => '13:00',
                'show' => '2',
                'jumlah' => 17,
                'harga' => 0.0,
                'tax' => 0.0,
                'net' => 0.0,
            ],
            [
                'source_row' => 7,
                'tgl_tayang' => '2026-09-26',
                'nama_film' => 'MEMBURU PEMANGSA',
                'source_cinema' => 'BLOK M XXI',
                'source_city' => 'JAKARTA',
                'studio' => '3',
                'capacity' => 314,
                'ticket_name' => 'REGULAR',
                'jam_tayang' => '15:00',
                'show' => '3',
                'jumlah' => 33,
                'harga' => 0.0,
                'tax' => 0.0,
                'net' => 0.0,
            ],
            [
                'source_row' => 7,
                'tgl_tayang' => '2026-09-26',
                'nama_film' => 'MEMBURU PEMANGSA',
                'source_cinema' => 'BLOK M XXI',
                'source_city' => 'JAKARTA',
                'studio' => '3',
                'capacity' => 314,
                'ticket_name' => 'REGULAR',
                'jam_tayang' => '17:00',
                'show' => '4',
                'jumlah' => 75,
                'harga' => 0.0,
                'tax' => 0.0,
                'net' => 0.0,
            ],
            [
                'source_row' => 7,
                'tgl_tayang' => '2026-09-26',
                'nama_film' => 'MEMBURU PEMANGSA',
                'source_cinema' => 'BLOK M XXI',
                'source_city' => 'JAKARTA',
                'studio' => '3',
                'capacity' => 314,
                'ticket_name' => 'REGULAR',
                'jam_tayang' => '19:00',
                'show' => '5',
                'jumlah' => 66,
                'harga' => 0.0,
                'tax' => 0.0,
                'net' => 0.0,
            ],
            [
                'source_row' => 7,
                'tgl_tayang' => '2026-09-26',
                'nama_film' => 'MEMBURU PEMANGSA',
                'source_cinema' => 'BLOK M XXI',
                'source_city' => 'JAKARTA',
                'studio' => '3',
                'capacity' => 314,
                'ticket_name' => 'REGULAR',
                'jam_tayang' => '21:00',
                'show' => '6',
                'jumlah' => 71,
                'harga' => 0.0,
                'tax' => 0.0,
                'net' => 0.0,
            ],
            [
                'source_row' => 9,
                'tgl_tayang' => '2026-09-26',
                'nama_film' => 'MEMBURU PEMANGSA',
                'source_cinema' => 'BRAGA XXI',
                'source_city' => 'BANDUNG',
                'studio' => '3',
                'capacity' => 124,
                'ticket_name' => 'REGULAR',
                'jam_tayang' => '23:00',
                'show' => '7',
                'jumlah' => 28,
                'harga' => 0.0,
                'tax' => 0.0,
                'net' => 0.0,
            ],
        ], $result['rows']);
        $this->assertSame([
            [
                'key' => 'XXI-7',
                'source_row' => 7,
                'source_cinema' => 'BLOK M XXI',
                'source_city' => 'JAKARTA',
                'jumlah' => 4,
                'candidate_shows' => [
                    ['show' => 2, 'jam_tayang' => '13:00'],
                    ['show' => 3, 'jam_tayang' => '15:00'],
                    ['show' => 4, 'jam_tayang' => '17:00'],
                    ['show' => 5, 'jam_tayang' => '19:00'],
                    ['show' => 6, 'jam_tayang' => '21:00'],
                ],
            ],
        ], $result['pending_free_assignments']);
        $this->assertSame(['ptn' => 290, 'fp' => 4], $result['source_totals']);
    }

    /** @test */
    public function it_assigns_a_leading_blank_merged_cell_row_to_the_next_cinema_group(): void
    {
        $result = (new XxiPdfParser())->parseText(<<<'PDF'
FILM MEMBURU PEMANGSA
SHOW: SUNDAY, 28 SEPTEMBER 2026
CINEMA St Kp 1 2 3 4 5 6 PTN FP
** PADANG **
PLAZA ANDALAS XXI 3 117 - - 27 - 20 - 47 -

3 119 - - 14 - - - 14 -

TRANSMART PADANG XXI
4 119 - - 34 - 48 - 82 -
5 119 - 4 - - - - 4 -
TOTAL 147 -
PDF);

        $plaza = array_values(array_filter($result['rows'], fn (array $row) => $row['source_cinema'] === 'PLAZA ANDALAS XXI'));
        $transmart = array_values(array_filter($result['rows'], fn (array $row) => $row['source_cinema'] === 'TRANSMART PADANG XXI'));

        $this->assertSame([27, 20], array_column($plaza, 'jumlah'));
        $this->assertSame(['3', '4', '5'], array_values(array_unique(array_column($transmart, 'studio'))));
        $this->assertSame(14, collect($transmart)->first(fn (array $row) => $row['studio'] === '3')['jumlah']);
    }

    /** @test */
    public function it_rejects_malformed_rows_and_total_mismatches_without_guessing(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('PTN');

        (new XxiPdfParser())->parseText(<<<'PDF'
FILM TEST
SHOW: SATURDAY, 26 SEPTEMBER 2026
** JAKARTA **
BLOK M XXI 3 314 - 17 33 - - - - 99 -
PDF);
    }
}
