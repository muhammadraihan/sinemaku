<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CityPerformanceFoundationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config([
            'database.default' => 'city_performance_test',
            'database.connections.city_performance_test' => [
                'driver' => 'sqlite',
                'database' => ':memory:',
                'prefix' => '',
                'foreign_key_constraints' => true,
            ],
        ]);
        DB::purge('city_performance_test');
        DB::setDefaultConnection('city_performance_test');
    }

    public function test_schema_creates_the_city_performance_foundation_and_dedupe_constraint(): void
    {
        require_once database_path('migrations/2026_09_20_000001_create_city_performance_tables.php');
        (new \CreateCityPerformanceTables)->up();

        foreach (['sources', 'cities', 'cinemas', 'movie_mappings', 'collection_runs', 'city_runs', 'showtimes', 'estimates'] as $suffix) {
            $this->assertTrue(Schema::hasTable('city_performance_'.$suffix));
        }
        $this->assertTrue(Schema::hasColumns('city_performance_collection_runs', ['source_id', 'period_date', 'method_version', 'coverage_ratio', 'status', 'error_message']));
        $this->assertTrue(Schema::hasColumns('city_performance_showtimes', ['collection_run_id', 'identity_hash', 'starts_at', 'format', 'weight']));

        $source = DB::table('city_performance_sources')->insertGetId(['key' => 'public-source', 'name' => 'Public Source', 'created_at' => now(), 'updated_at' => now()]);
        $run = DB::table('city_performance_collection_runs')->insertGetId(['source_id' => $source, 'period_date' => '2026-09-20', 'method_version' => 'v1', 'status' => 'complete', 'created_at' => now(), 'updated_at' => now()]);
        $city = DB::table('city_performance_cities')->insertGetId(['name' => 'Jakarta', 'slug' => 'jakarta', 'created_at' => now(), 'updated_at' => now()]);
        $cinema = DB::table('city_performance_cinemas')->insertGetId(['source_id' => $source, 'city_id' => $city, 'source_cinema_id' => 'jkt-1', 'name' => 'Cinema', 'created_at' => now(), 'updated_at' => now()]);
        $row = ['collection_run_id' => $run, 'city_id' => $city, 'cinema_id' => $cinema, 'source_movie_id' => 'film-1', 'identity_hash' => str_repeat('a', 64), 'show_date' => '2026-09-20', 'starts_at' => '19:30:00', 'weight' => 1, 'created_at' => now(), 'updated_at' => now()];
        DB::table('city_performance_showtimes')->insert($row);

        $this->expectException(\Illuminate\Database\QueryException::class);
        DB::table('city_performance_showtimes')->insert($row);
    }

    public function test_route_requires_authentication_and_renders_honest_empty_state(): void
    {
        $this->get('/backoffice/city-performance')->assertRedirect('/login');

        require_once database_path('migrations/2019_10_07_071356_create_permission_tables.php');
        require_once database_path('migrations/2021_07_20_165942_create_menus_table.php');
        require_once database_path('migrations/2021_07_24_143951_menu_role.php');
        (new \CreatePermissionTables)->up();
        (new \CreateMenusTable)->up();
        (new \MenuRole)->up();
        Schema::create('users', function ($table) {
            $table->increments('id');
            $table->string('uuid')->nullable();
            $table->string('name');
            $table->string('email');
            $table->string('password')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });
        $userId = DB::table('users')->insertGetId(['name' => 'Tester', 'email' => 'tester@example.test', 'created_at' => now(), 'updated_at' => now()]);
        $roleId = DB::table('roles')->insertGetId(['name' => 'superadmin', 'guard_name' => 'web']);
        DB::table('model_has_roles')->insert(['role_id' => $roleId, 'model_type' => User::class, 'model_id' => $userId]);
        $user = User::findOrFail($userId);

        $this->actingAs($user)->get('/backoffice/city-performance')
            ->assertOk()
            ->assertSee('City Performance')
            ->assertSee('Belum ada observasi showtime')
            ->assertSee('Method version')
            ->assertSee('Coverage');
    }
}
