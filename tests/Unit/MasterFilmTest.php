<?php

namespace Tests\Unit;

use App\Models\MasterFilm;
use Carbon\Carbon;
use PHPUnit\Framework\TestCase;

class MasterFilmTest extends TestCase
{
    /** @test */
    public function it_normalizes_film_names_for_master_matching()
    {
        $this->assertSame('AGAK LAEN', MasterFilm::normalizeName('  Agak Laen  '));
    }

    /** @test */
    public function one_month_filter_start_date_does_not_overflow()
    {
        $startDate = Carbon::parse('2026-03-31')
            ->subMonthNoOverflow()
            ->toDateString();

        $this->assertSame('2026-02-28', $startDate);
    }
}
