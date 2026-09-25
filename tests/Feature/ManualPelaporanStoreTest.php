<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ManualPelaporanStoreTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config([
            'database.default' => 'manual_pelaporan_test',
            'database.connections.manual_pelaporan_test' => [
                'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '', 'foreign_key_constraints' => true,
            ],
        ]);
        DB::purge('manual_pelaporan_test');
        DB::setDefaultConnection('manual_pelaporan_test');

        Schema::create('users', function ($table) {
            $table->increments('id'); $table->string('uuid')->unique(); $table->string('name');
            $table->string('email')->nullable(); $table->string('password')->nullable(); $table->rememberToken(); $table->timestamps();
        });
        Schema::create('kategori_bioskops', function ($table) {
            $table->increments('id'); $table->string('uuid')->unique(); $table->string('name')->nullable(); $table->timestamps();
        });
        Schema::create('master_bioskops', function ($table) {
            $table->increments('id'); $table->string('uuid')->unique(); $table->string('nama_bioskop');
            $table->string('type'); $table->string('kota'); $table->string('pajak')->nullable(); $table->timestamps();
        });
        Schema::create('master_films', function ($table) {
            $table->increments('id'); $table->string('uuid')->unique(); $table->string('name'); $table->date('tgl_tayang')->nullable(); $table->timestamps();
        });
        Schema::create('type_tikets', function ($table) {
            $table->increments('id'); $table->string('uuid')->unique(); $table->string('name'); $table->string('kategori'); $table->timestamps();
        });
        Schema::create('kapasitas', function ($table) {
            $table->increments('id'); $table->string('uuid')->unique(); $table->string('kategori'); $table->string('kota');
            $table->string('nama_bioskop'); $table->string('type_tiket'); $table->string('studio'); $table->string('kapasitas'); $table->timestamps();
        });
        Schema::create('pelaporans', function ($table) {
            $table->increments('id'); $table->string('uuid')->unique(); $table->string('kategori'); $table->string('provinsi')->nullable();
            $table->string('kota'); $table->string('nama_bioskop'); $table->string('nama_film'); $table->date('tgl_tayang');
            $table->time('jam_tayang')->nullable(); $table->string('show'); $table->string('type_tiket'); $table->string('harga');
            $table->string('jumlah'); $table->string('gross'); $table->string('tax')->nullable(); $table->string('net')->nullable();
            $table->string('studio'); $table->string('created_by')->nullable(); $table->timestamps();
        });

        DB::table('kategori_bioskops')->insert(['uuid' => 'category-1', 'name' => 'XXI']);
        DB::table('master_bioskops')->insert(['uuid' => 'cinema-1', 'nama_bioskop' => 'CINEMA ONE', 'type' => 'category-1', 'kota' => 'JAKARTA', 'pajak' => 10]);
        DB::table('master_films')->insert(['uuid' => 'film-1', 'name' => 'FILM TEST']);
        DB::table('type_tikets')->insert([
            ['uuid' => 'ticket-regular', 'name' => 'REGULAR', 'kategori' => 'category-1'],
            ['uuid' => 'ticket-vip', 'name' => 'VIP', 'kategori' => 'category-1'],
        ]);
        DB::table('kapasitas')->insert([
            ['uuid' => 'capacity-regular-1', 'kategori' => 'category-1', 'kota' => 'JAKARTA', 'nama_bioskop' => 'cinema-1', 'type_tiket' => 'ticket-regular', 'studio' => '1', 'kapasitas' => 100],
            ['uuid' => 'capacity-vip-2', 'kategori' => 'category-1', 'kota' => 'JAKARTA', 'nama_bioskop' => 'cinema-1', 'type_tiket' => 'ticket-vip', 'studio' => '2', 'kapasitas' => 40],
        ]);
    }

    public function test_manual_report_can_store_multiple_ticket_and_studio_rows_in_one_submit(): void
    {
        $user = $this->createUser();

        $response = $this->actingAs($user)->post(route('pelaporan.store'), $this->payload([
            'type_tiket' => ['ticket-regular', 'ticket-vip'],
            'studio' => ['capacity-regular-1', 'capacity-vip-2'],
            'show' => ['1', '1'],
            'jam_tayang' => ['11:00', '11:00'],
            'harga' => ['50,000', '100,000'],
            'jumlah' => ['10', '2'],
            'gross' => ['500000.00', '200000.00'],
            'tax' => ['10', '10'],
            'net' => ['450000.00', '180000.00'],
        ]));

        $response->assertRedirect(route('pelaporan.index'));
        $this->assertSame(2, DB::table('pelaporans')->count());
        $this->assertDatabaseHas('pelaporans', ['type_tiket' => 'ticket-regular', 'studio' => 'capacity-regular-1', 'gross' => '500000.00']);
        $this->assertDatabaseHas('pelaporans', ['type_tiket' => 'ticket-vip', 'studio' => 'capacity-vip-2', 'gross' => '200000.00']);
    }

    public function test_manual_report_rejects_capacity_from_another_ticket_type_without_partial_rows(): void
    {
        $user = $this->createUser();

        $response = $this->actingAs($user)->from(route('pelaporan.create'))->post(route('pelaporan.store'), $this->payload([
            'type_tiket' => ['ticket-regular', 'ticket-vip'],
            'studio' => ['capacity-vip-2', 'capacity-vip-2'],
            'show' => ['1', '1'],
            'jam_tayang' => ['11:00', '11:00'],
            'harga' => ['50,000', '100,000'],
            'jumlah' => ['10', '2'],
            'gross' => ['500000.00', '200000.00'],
            'tax' => ['10', '10'],
            'net' => ['450000.00', '180000.00'],
        ]));

        $response->assertRedirect(route('pelaporan.create'))->assertSessionHasErrors('studio.0');
        $this->assertSame(0, DB::table('pelaporans')->count());
    }

    private function payload(array $overrides): array
    {
        return array_merge([
            'kategori' => 'category-1', 'kota' => 'JAKARTA', 'nama_bioskop' => 'cinema-1',
            'nama_film' => 'FILM TEST', 'tgl_tayang' => '24-09-2026', 'provinsi' => 'DKI JAKARTA',
        ], $overrides);
    }

    private function createUser(): User
    {
        $id = DB::table('users')->insertGetId([
            'uuid' => 'manual-user', 'name' => 'Manual Tester', 'email' => 'manual@example.test', 'password' => bcrypt('secret'),
        ]);
        return User::findOrFail($id);
    }
}
