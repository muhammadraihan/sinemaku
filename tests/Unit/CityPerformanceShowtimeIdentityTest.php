<?php

namespace Tests\Unit;

use App\Services\CityPerformance\ShowtimeIdentity;
use PHPUnit\Framework\TestCase;

class CityPerformanceShowtimeIdentityTest extends TestCase
{
    public function test_identity_is_deterministic_across_whitespace_case_and_time_notation(): void
    {
        $identity = new ShowtimeIdentity();

        $first = $identity->make('source-1', ' CINEMA-9 ', ' Movie-22 ', '2026-09-20', '19:30', ' IMAX ');
        $second = $identity->make('SOURCE-1', 'cinema-9', 'movie-22', '2026-09-20', '19:30:00', 'imax');

        $this->assertSame($first, $second);
        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $first);
    }

    public function test_identity_changes_when_an_observed_showtime_dimension_changes(): void
    {
        $identity = new ShowtimeIdentity();
        $base = $identity->make('source-1', 'cinema-9', 'movie-22', '2026-09-20', '19:30', 'regular');

        $this->assertNotSame($base, $identity->make('source-1', 'cinema-9', 'movie-22', '2026-09-20', '20:30', 'regular'));
        $this->assertNotSame($base, $identity->make('source-1', 'cinema-9', 'movie-22', '2026-09-20', '19:30', 'imax'));
    }
}
