<?php

namespace Tests\Unit;

use App\Models\CalendarHoliday;
use App\Models\CinemaTicketPrice;
use App\Services\Reports\CinemaTicketPriceResolver;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CinemaTicketPriceResolverTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config(['database.default' => 'sqlite']);
        config(['database.connections.sqlite.database' => ':memory:']);

        Schema::create('cinema_ticket_prices', function (Blueprint $table) {
            $table->increments('id');
            $table->string('uuid')->unique();
            $table->string('master_bioskop_uuid');
            $table->string('type_tiket_uuid');
            $table->decimal('weekday_price', 15, 2);
            $table->decimal('friday_price', 15, 2);
            $table->decimal('weekend_holiday_price', 15, 2);
            $table->date('valid_from');
            $table->date('valid_until')->nullable();
            $table->boolean('active')->default(true);
            $table->string('created_by')->nullable();
            $table->string('edited_by')->nullable();
            $table->timestamps();
        });

        Schema::create('calendar_holidays', function (Blueprint $table) {
            $table->increments('id');
            $table->string('uuid')->unique();
            $table->date('holiday_date')->unique();
            $table->string('name');
            $table->boolean('active')->default(true);
            $table->string('created_by')->nullable();
            $table->string('edited_by')->nullable();
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('calendar_holidays');
        Schema::dropIfExists('cinema_ticket_prices');

        parent::tearDown();
    }

    /** @test */
    public function it_resolves_weekday_friday_and_weekend_price_bands(): void
    {
        $this->createPrice();
        $resolver = new CinemaTicketPriceResolver();

        $this->assertSame('50000.00', $resolver->resolve('cinema-uuid', 'ticket-uuid', '2026-09-28')['price']);
        $this->assertSame('60000.00', $resolver->resolve('cinema-uuid', 'ticket-uuid', '2026-09-25')['price']);
        $weekend = $resolver->resolve('cinema-uuid', 'ticket-uuid', '2026-09-26');
        $this->assertSame('70000.00', $weekend['price']);
        $this->assertSame('weekend_holiday', $weekend['day_group']);
    }

    /** @test */
    public function it_only_resolves_an_active_price_in_its_valid_period(): void
    {
        $this->createPrice(['valid_from' => '2026-09-01', 'valid_until' => '2026-09-30']);
        $resolver = new CinemaTicketPriceResolver();

        $this->assertNull($resolver->resolve('cinema-uuid', 'ticket-uuid', '2026-08-31'));
        $this->assertNotNull($resolver->resolve('cinema-uuid', 'ticket-uuid', '2026-09-30'));
        $this->assertNull($resolver->resolve('cinema-uuid', 'ticket-uuid', '2026-10-01'));
    }

    /** @test */
    public function it_uses_weekend_holiday_price_for_an_active_calendar_holiday(): void
    {
        $this->createPrice();
        CalendarHoliday::create([
            'uuid' => 'holiday-uuid',
            'holiday_date' => '2026-09-29',
            'name' => 'Hari Libur',
            'active' => true,
        ]);

        $result = (new CinemaTicketPriceResolver())->resolve('cinema-uuid', 'ticket-uuid', '2026-09-29');

        $this->assertSame('70000.00', $result['price']);
        $this->assertSame('weekend_holiday', $result['day_group']);
    }

    /** @test */
    public function it_returns_null_when_no_matching_active_price_exists(): void
    {
        $this->createPrice(['active' => false]);

        $this->assertNull((new CinemaTicketPriceResolver())->resolve('cinema-uuid', 'ticket-uuid', '2026-09-28'));
        $this->assertNull((new CinemaTicketPriceResolver())->resolve('other-cinema', 'ticket-uuid', '2026-09-28'));
    }

    /** @test */
    public function it_can_query_price_periods_that_overlap_a_candidate_period(): void
    {
        $this->createPrice(['valid_from' => '2026-09-01', 'valid_until' => '2026-09-30']);

        $this->assertSame(1, CinemaTicketPrice::query()->overlapping('2026-09-30', '2026-10-10')->count());
        $this->assertSame(0, CinemaTicketPrice::query()->overlapping('2026-10-01', '2026-10-10')->count());
        $this->assertSame(1, CinemaTicketPrice::query()->overlapping('2026-08-01', null)->count());
    }

    private function createPrice(array $attributes = []): CinemaTicketPrice
    {
        return CinemaTicketPrice::create(array_merge([
            'uuid' => 'price-uuid',
            'master_bioskop_uuid' => 'cinema-uuid',
            'type_tiket_uuid' => 'ticket-uuid',
            'weekday_price' => '50000.00',
            'friday_price' => '60000.00',
            'weekend_holiday_price' => '70000.00',
            'valid_from' => '2026-01-01',
            'valid_until' => null,
            'active' => true,
        ], $attributes));
    }
}
