<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CinemaMasterWizardTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config([
            'database.default' => 'cinema_master_wizard_test',
            'database.connections.cinema_master_wizard_test' => [
                'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '', 'foreign_key_constraints' => true,
            ],
        ]);
        DB::purge('cinema_master_wizard_test');
        DB::setDefaultConnection('cinema_master_wizard_test');

        foreach (['users', 'kategori_bioskops', 'master_bioskops', 'type_tikets', 'kapasitas'] as $tableName) {
            Schema::create($tableName, function ($table) {
                $table->increments('id');
                $table->string('uuid')->nullable()->unique();
                $table->string('name')->nullable();
                $table->string('nama_bioskop')->nullable();
                $table->string('type')->nullable();
                $table->string('kota')->nullable();
                $table->string('no_telephone')->nullable();
                $table->string('pajak')->nullable();
                $table->string('kategori')->nullable();
                $table->string('type_tiket')->nullable();
                $table->string('studio')->nullable();
                $table->string('kapasitas')->nullable();
                $table->string('created_by')->nullable();
                $table->string('edited_by')->nullable();
                $table->string('email')->nullable();
                $table->string('password')->nullable();
                $table->rememberToken();
                $table->timestamps();
            });
        }

        DB::table('kategori_bioskops')->insert(['uuid' => 'category-1', 'name' => 'CINEPOLIS']);
    }

    public function test_cinema_wizard_creates_cinema_ticket_types_and_capacities_together(): void
    {
        $user = $this->createUser();

        $response = $this->actingAs($user)->post(route('masterbioskop.store'), [
            'type' => 'category-1',
            'nama_bioskop' => 'LIVING PLAZA BALIKPAPAN',
            'kota' => 'BALIKPAPAN',
            'pajak' => 10,
            'no_telephone' => '0542123456',
            'ticket_types' => [
                ['name' => 'REGULAR'],
                ['name' => 'REGULAR DIST FULL'],
            ],
            'capacities' => [
                ['ticket_type_ref' => 'new:0', 'studio' => '1', 'kapasitas' => 120],
                ['ticket_type_ref' => 'new:1', 'studio' => '1', 'kapasitas' => 10],
                ['ticket_type_ref' => 'new:0', 'studio' => '2', 'kapasitas' => 90],
            ],
        ]);

        $response->assertRedirect(route('masterbioskop.index'));
        $cinema = DB::table('master_bioskops')->where('nama_bioskop', 'LIVING PLAZA BALIKPAPAN')->first();
        $this->assertNotNull($cinema);
        $this->assertSame(['REGULAR', 'REGULAR DIST FULL'], DB::table('type_tikets')->orderBy('id')->pluck('name')->all());
        $this->assertSame(3, DB::table('kapasitas')->where('nama_bioskop', $cinema->uuid)->count());
        $this->assertSame(['1', '1', '2'], DB::table('kapasitas')->orderBy('id')->pluck('studio')->all());
    }

    public function test_cinema_wizard_can_use_existing_ticket_types_from_the_selected_category(): void
    {
        $user = $this->createUser();
        DB::table('type_tikets')->insert(['uuid' => 'existing-regular', 'kategori' => 'category-1', 'name' => 'REGULAR']);

        $this->actingAs($user)->post(route('masterbioskop.store'), [
            'type' => 'category-1',
            'nama_bioskop' => 'EXISTING TICKET CINEMA',
            'kota' => 'BALIKPAPAN',
            'capacities' => [[
                'ticket_type_ref' => 'existing-regular',
                'studio' => '1',
                'kapasitas' => 100,
            ]],
        ])->assertRedirect(route('masterbioskop.index'));

        $cinema = DB::table('master_bioskops')->where('nama_bioskop', 'EXISTING TICKET CINEMA')->first();
        $this->assertNotNull($cinema);
        $this->assertSame(1, DB::table('type_tikets')->count());
        $this->assertSame('existing-regular', DB::table('kapasitas')->where('nama_bioskop', $cinema->uuid)->value('type_tiket'));
    }

    public function test_invalid_capacity_reference_leaves_no_partial_wizard_data(): void
    {
        $user = $this->createUser();

        $response = $this->actingAs($user)->from(route('masterbioskop.create'))->post(route('masterbioskop.store'), [
            'type' => 'category-1',
            'nama_bioskop' => 'INVALID CINEMA',
            'kota' => 'BALIKPAPAN',
            'pajak' => 10,
            'ticket_types' => [['name' => 'REGULAR']],
            'capacities' => [['ticket_type_ref' => 'new:4', 'studio' => '1', 'kapasitas' => 100]],
        ]);

        $response->assertRedirect(route('masterbioskop.create'))->assertSessionHasErrors('capacities.0.ticket_type_ref');
        $this->assertSame(0, DB::table('master_bioskops')->count());
        $this->assertSame(0, DB::table('type_tikets')->count());
        $this->assertSame(0, DB::table('kapasitas')->count());
    }

    public function test_ticket_type_create_page_can_store_multiple_rows(): void
    {
        $user = $this->createUser();

        $this->actingAs($user)->post(route('typetiket.store'), [
            'kategori' => 'category-1',
            'ticket_types' => [['name' => 'regular'], ['name' => 'vip']],
        ])->assertRedirect(route('typetiket.index'));

        $this->assertSame(['REGULAR', 'VIP'], DB::table('type_tikets')->orderBy('id')->pluck('name')->all());
    }

    public function test_capacity_create_page_can_apply_ticket_rows_to_multiple_cinemas(): void
    {
        $user = $this->createUser();
        DB::table('master_bioskops')->insert([
            ['uuid' => 'cinema-1', 'type' => 'category-1', 'nama_bioskop' => 'CINEMA ONE', 'kota' => 'BALIKPAPAN'],
            ['uuid' => 'cinema-2', 'type' => 'category-1', 'nama_bioskop' => 'CINEMA TWO', 'kota' => 'SAMARINDA'],
        ]);
        DB::table('type_tikets')->insert(['uuid' => 'ticket-1', 'kategori' => 'category-1', 'name' => 'FREE PASS']);

        $this->actingAs($user)->post(route('kapasitas.store'), [
            'kategori' => 'category-1',
            'nama_bioskop' => ['cinema-1', 'cinema-2'],
            'capacities' => [
                ['type_tiket' => 'ticket-1', 'studio' => '1', 'kapasitas' => 120],
            ],
        ])->assertRedirect(route('kapasitas.index'));

        $this->assertSame(2, DB::table('kapasitas')->count());
        $this->assertDatabaseHas('kapasitas', ['nama_bioskop' => 'cinema-1', 'type_tiket' => 'ticket-1', 'studio' => '1', 'kapasitas' => '120', 'kota' => 'BALIKPAPAN']);
        $this->assertDatabaseHas('kapasitas', ['nama_bioskop' => 'cinema-2', 'type_tiket' => 'ticket-1', 'studio' => '1', 'kapasitas' => '120', 'kota' => 'SAMARINDA']);
    }

    public function test_capacity_create_page_can_store_multiple_rows(): void
    {
        $user = $this->createUser();
        DB::table('master_bioskops')->insert(['uuid' => 'cinema-1', 'type' => 'category-1', 'nama_bioskop' => 'CINEMA TEST', 'kota' => 'BALIKPAPAN']);
        DB::table('type_tikets')->insert([
            ['uuid' => 'ticket-1', 'kategori' => 'category-1', 'name' => 'REGULAR'],
            ['uuid' => 'ticket-2', 'kategori' => 'category-1', 'name' => 'VIP'],
        ]);

        $this->actingAs($user)->post(route('kapasitas.store'), [
            'kategori' => 'category-1',
            'nama_bioskop' => 'cinema-1',
            'kota' => 'BALIKPAPAN',
            'capacities' => [
                ['type_tiket' => 'ticket-1', 'studio' => '1', 'kapasitas' => 100],
                ['type_tiket' => 'ticket-2', 'studio' => '1', 'kapasitas' => 20],
                ['type_tiket' => 'ticket-1', 'studio' => '2', 'kapasitas' => 80],
            ],
        ])->assertRedirect(route('kapasitas.index'));

        $this->assertSame(3, DB::table('kapasitas')->count());
        $this->assertSame(['1', '1', '2'], DB::table('kapasitas')->orderBy('id')->pluck('studio')->all());
    }

    private function createUser(): User
    {
        $id = DB::table('users')->insertGetId([
            'uuid' => 'wizard-user', 'name' => 'Wizard Tester', 'email' => 'wizard@example.test', 'password' => bcrypt('secret'),
        ]);

        return User::findOrFail($id);
    }
}
