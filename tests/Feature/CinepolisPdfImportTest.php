<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CinepolisPdfImportTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config([
            'database.default' => 'cinepolis_pdf_test',
            'database.connections.cinepolis_pdf_test' => [
                'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '', 'foreign_key_constraints' => true,
            ],
            'cache.default' => 'array',
        ]);
        DB::purge('cinepolis_pdf_test');
        DB::setDefaultConnection('cinepolis_pdf_test');
        Cache::flush();

        foreach (['users', 'kategori_bioskops', 'master_bioskops', 'master_films', 'type_tikets', 'kapasitas', 'kotas', 'provinces', 'pelaporans'] as $table) {
            Schema::create($table, function ($table) {
                $table->increments('id');
                $table->string('uuid')->nullable()->unique();
                $table->string('name')->nullable();
                $table->string('nama')->nullable();
                $table->string('nama_bioskop')->nullable();
                $table->string('type')->nullable();
                $table->string('kota')->nullable();
                $table->string('provinsi_id')->nullable();
                $table->string('kategori')->nullable();
                $table->string('nama_film')->nullable();
                $table->date('tgl_tayang')->nullable();
                $table->time('jam_tayang')->nullable();
                $table->string('show')->nullable();
                $table->string('type_tiket')->nullable();
                $table->string('harga')->nullable();
                $table->string('jumlah')->nullable();
                $table->string('gross')->nullable();
                $table->string('tax')->nullable();
                $table->string('net')->nullable();
                $table->string('studio')->nullable();
                $table->string('kapasitas')->nullable();
                $table->string('provinsi')->nullable();
                $table->string('created_by')->nullable();
                $table->string('edited_by')->nullable();
                $table->string('email')->nullable();
                $table->string('password')->nullable();
                $table->rememberToken();
                $table->timestamps();
            });
        }
    }

    public function test_preview_then_confirm_inserts_once_and_consumes_the_token(): void
    {
        $user = $this->seedResolvedMappings();
        $file = new UploadedFile(base_path('tests/Fixtures/cinepolis-vista-sample.pdf'), 'cinepolis.pdf', 'application/pdf', null, true);

        $preview = $this->actingAs($user)->post(route('pelaporan.upload.cinepolis.preview'), ['file' => $file]);
        $preview->assertOk()->assertJsonPath('status', 'success')->assertJsonCount(7, 'preview');
        $this->assertSame(0, DB::table('pelaporans')->count());
        $token = $preview->json('token');

        $this->actingAs($user)->post(route('pelaporan.upload.cinepolis.confirm'), ['token' => $token])
            ->assertOk()->assertJsonPath('inserted', 7);
        $this->assertSame(7, DB::table('pelaporans')->count());
        $this->actingAs($user)->post(route('pelaporan.upload.cinepolis.confirm'), ['token' => $token])->assertStatus(422);
    }

    public function test_preview_blocks_unresolved_master_mappings_without_writing_rows(): void
    {
        $user = $this->createUser();
        $file = new UploadedFile(base_path('tests/Fixtures/cinepolis-vista-sample.pdf'), 'cinepolis.pdf', 'application/pdf', null, true);

        $this->actingAs($user)->post(route('pelaporan.upload.cinepolis.preview'), ['file' => $file])
            ->assertOk()->assertJsonPath('status', 'success')->assertJsonCount(7, 'preview');
        $this->assertSame(0, DB::table('pelaporans')->count());
    }

    public function test_preview_maps_multiple_screen_blocks_to_their_own_studios(): void
    {
        $user = $this->createUser();
        DB::table('kategori_bioskops')->insert(['uuid' => 'category-1', 'name' => 'CINEPOLIS']);
        DB::table('master_bioskops')->insert(['uuid' => 'cinema-1', 'nama_bioskop' => 'LIPPO PLAZA JEMBER', 'type' => 'category-1', 'kota' => 'JEMBER']);
        DB::table('master_films')->insert(['uuid' => 'film-1', 'name' => 'PATAH HATI YANG KUPILIH']);
        DB::table('kotas')->insert(['uuid' => 'city-1', 'nama' => 'JEMBER', 'provinsi_id' => 'province-1']);
        DB::table('provinces')->insert(['uuid' => 'province-1', 'nama' => 'JAWA TIMUR']);
        DB::table('type_tikets')->insert(['uuid' => 'ticket-1', 'name' => 'REGULAR', 'kategori' => 'category-1']);
        DB::table('kapasitas')->insert([
            ['uuid' => 'studio-04', 'kategori' => 'category-1', 'nama_bioskop' => 'cinema-1', 'type_tiket' => 'ticket-1', 'studio' => 'CINEMA 04'],
            ['uuid' => 'studio-06', 'kategori' => 'category-1', 'nama_bioskop' => 'cinema-1', 'type_tiket' => 'ticket-1', 'studio' => 'CINEMA 06'],
        ]);
        $file = new UploadedFile(base_path('tests/Fixtures/cinepolis-jember-two-screens.pdf'), 'cinepolis-jember.pdf', 'application/pdf', null, true);

        $preview = $this->actingAs($user)->post(route('pelaporan.upload.cinepolis.preview'), ['file' => $file]);
        $preview->assertOk()->assertJsonCount(2, 'preview')->assertJsonPath('blocking_issues', []);
        $this->assertSame(['4', '6'], array_column($preview->json('preview'), 'studio'));

        $this->actingAs($user)->post(route('pelaporan.upload.cinepolis.confirm'), ['token' => $preview->json('token')])
            ->assertOk()->assertJsonPath('inserted', 2);
        $this->assertSame(['studio-04', 'studio-06'], DB::table('pelaporans')->orderBy('jam_tayang', 'desc')->pluck('studio')->all());
    }

    public function test_name_like_match_is_shown_and_requires_explicit_confirmation(): void
    {
        $user = $this->createUser();
        DB::table('kategori_bioskops')->insert(['uuid' => 'category-1', 'name' => 'CINEPOLIS']);
        DB::table('master_bioskops')->insert(['uuid' => 'cinema-1', 'nama_bioskop' => 'CINÉPOLIS LIPPO PLAZA JEMBER', 'type' => 'category-1', 'kota' => 'JEMBER']);
        DB::table('master_films')->insert(['uuid' => 'film-1', 'name' => 'PATAH HATI YANG KUPILIH']);
        DB::table('kotas')->insert(['uuid' => 'city-1', 'nama' => 'JEMBER', 'provinsi_id' => 'province-1']);
        DB::table('provinces')->insert(['uuid' => 'province-1', 'nama' => 'JAWA TIMUR']);
        DB::table('type_tikets')->insert(['uuid' => 'ticket-1', 'name' => 'REGULAR', 'kategori' => 'category-1']);
        DB::table('kapasitas')->insert([
            ['uuid' => 'studio-04', 'kategori' => 'category-1', 'nama_bioskop' => 'cinema-1', 'type_tiket' => 'ticket-1', 'studio' => 'CINEMA 04'],
            ['uuid' => 'studio-06', 'kategori' => 'category-1', 'nama_bioskop' => 'cinema-1', 'type_tiket' => 'ticket-1', 'studio' => 'CINEMA 06'],
        ]);
        $file = new UploadedFile(base_path('tests/Fixtures/cinepolis-jember-two-screens.pdf'), 'cinepolis-jember.pdf', 'application/pdf', null, true);

        $preview = $this->actingAs($user)->post(route('pelaporan.upload.cinepolis.preview'), ['file' => $file]);
        $preview->assertOk()->assertJsonPath('cinema_mapping.requires_confirmation', true)
            ->assertJsonPath('cinema_mapping.report_name', 'LIPPO PLAZA JEMBER')
            ->assertJsonPath('cinema_mapping.master_name', 'CINÉPOLIS LIPPO PLAZA JEMBER');
        $token = $preview->json('token');

        $this->actingAs($user)->post(route('pelaporan.upload.cinepolis.confirm'), ['token' => $token])
            ->assertStatus(422)->assertJsonPath('message', 'Konfirmasi nama bioskop diperlukan sebelum import.');
        $this->actingAs($user)->post(route('pelaporan.upload.cinepolis.confirm'), ['token' => $token, 'confirm_cinema_mapping' => 1])
            ->assertOk()->assertJsonPath('inserted', 2);
    }

    public function test_ambiguous_name_returns_city_picklist_and_imports_only_selected_candidate(): void
    {
        $user = $this->createUser();
        DB::table('kategori_bioskops')->insert(['uuid' => 'category-1', 'name' => 'CINEPOLIS']);
        DB::table('master_bioskops')->insert([
            ['uuid' => 'cinema-jember', 'nama_bioskop' => 'CINÉPOLIS LIPPO PLAZA JEMBER', 'type' => 'category-1', 'kota' => 'JEMBER'],
            ['uuid' => 'cinema-jember-city', 'nama_bioskop' => 'CINÉPOLIS LIPPO PLAZA JEMBER CITY', 'type' => 'category-1', 'kota' => 'JEMBER CITY'],
        ]);
        DB::table('master_films')->insert(['uuid' => 'film-1', 'name' => 'PATAH HATI YANG KUPILIH']);
        DB::table('kotas')->insert([
            ['uuid' => 'city-1', 'nama' => 'JEMBER', 'provinsi_id' => 'province-1'],
            ['uuid' => 'city-2', 'nama' => 'JEMBER CITY', 'provinsi_id' => 'province-1'],
        ]);
        DB::table('provinces')->insert(['uuid' => 'province-1', 'nama' => 'JAWA TIMUR']);
        DB::table('type_tikets')->insert(['uuid' => 'ticket-1', 'name' => 'REGULAR', 'kategori' => 'category-1']);
        DB::table('kapasitas')->insert([
            ['uuid' => 'selected-studio-4', 'kategori' => 'category-1', 'nama_bioskop' => 'cinema-jember-city', 'type_tiket' => 'ticket-1', 'studio' => '4'],
            ['uuid' => 'selected-studio-6', 'kategori' => 'category-1', 'nama_bioskop' => 'cinema-jember-city', 'type_tiket' => 'ticket-1', 'studio' => '6'],
        ]);
        $file = new UploadedFile(base_path('tests/Fixtures/cinepolis-jember-two-screens.pdf'), 'cinepolis-jember.pdf', 'application/pdf', null, true);

        $preview = $this->actingAs($user)->post(route('pelaporan.upload.cinepolis.preview'), ['file' => $file]);
        $preview->assertOk()->assertJsonPath('cinema_mapping.ambiguous', true)
            ->assertJsonPath('cinema_mapping.candidates.0.city', 'JEMBER')
            ->assertJsonPath('cinema_mapping.candidates.1.city', 'JEMBER CITY');
        $token = $preview->json('token');

        $this->actingAs($user)->post(route('pelaporan.upload.cinepolis.confirm'), ['token' => $token])
            ->assertStatus(422)->assertJsonPath('message', 'Pilih salah satu Master Bioskop yang sesuai sebelum import.');
        $this->actingAs($user)->post(route('pelaporan.upload.cinepolis.confirm'), ['token' => $token, 'cinema_uuid' => 'not-a-candidate'])
            ->assertStatus(422);
        $this->actingAs($user)->post(route('pelaporan.upload.cinepolis.confirm'), ['token' => $token, 'cinema_uuid' => 'cinema-jember-city'])
            ->assertOk()->assertJsonPath('inserted', 2);
        $this->assertSame(['cinema-jember-city'], DB::table('pelaporans')->distinct()->pluck('nama_bioskop')->all());
        $this->assertSame(['selected-studio-4', 'selected-studio-6'], DB::table('pelaporans')->orderBy('jam_tayang', 'desc')->pluck('studio')->all());
    }

    public function test_quick_master_creates_missing_ticket_and_returns_refreshed_preview(): void
    {
        $user = $this->createUser();
        DB::table('kategori_bioskops')->insert(['uuid' => 'category-1', 'name' => 'CINEPOLIS']);
        DB::table('master_bioskops')->insert(['uuid' => 'cinema-1', 'nama_bioskop' => 'PALEMBANG ICON', 'type' => 'category-1', 'kota' => 'PALEMBANG']);
        DB::table('master_films')->insert(['uuid' => 'film-1', 'name' => 'PERAYAAN MATI RASA']);
        DB::table('kotas')->insert(['uuid' => 'city-1', 'nama' => 'PALEMBANG', 'provinsi_id' => 'province-1']);
        DB::table('provinces')->insert(['uuid' => 'province-1', 'nama' => 'SUMATERA SELATAN']);
        DB::table('type_tikets')->insert(['uuid' => 'ticket-regular', 'name' => 'REGULAR', 'kategori' => 'category-1']);
        $file = new UploadedFile(base_path('tests/Fixtures/cinepolis-palembang-icon-multi-ticket.pdf'), 'palembang.pdf', 'application/pdf', null, true);
        $preview = $this->actingAs($user)->post(route('pelaporan.upload.cinepolis.preview'), ['file' => $file]);

        $response = $this->actingAs($user)->post(route('pelaporan.upload.cinepolis.quick-master'), [
            'token' => $preview->json('token'),
            'resource' => 'ticket_type',
            'name' => 'COMPLIMENTRY VOUCHER',
        ]);

        $response->assertOk()->assertJsonPath('status', 'success');
        $this->assertDatabaseHas('type_tikets', ['name' => 'COMPLIMENTRY VOUCHER', 'kategori' => 'category-1']);
        $this->assertStringNotContainsString('Tipe tiket COMPLIMENTRY VOUCHER belum tersedia', implode(' ', $response->json('blocking_issues')));
    }

    public function test_quick_master_creates_missing_capacity_for_pdf_studio(): void
    {
        $user = $this->createUser();
        DB::table('kategori_bioskops')->insert(['uuid' => 'category-1', 'name' => 'CINEPOLIS']);
        DB::table('master_bioskops')->insert(['uuid' => 'cinema-1', 'nama_bioskop' => 'PALEMBANG ICON', 'type' => 'category-1', 'kota' => 'PALEMBANG']);
        DB::table('master_films')->insert(['uuid' => 'film-1', 'name' => 'PERAYAAN MATI RASA']);
        DB::table('kotas')->insert(['uuid' => 'city-1', 'nama' => 'PALEMBANG', 'provinsi_id' => 'province-1']);
        DB::table('provinces')->insert(['uuid' => 'province-1', 'nama' => 'SUMATERA SELATAN']);
        DB::table('type_tikets')->insert(['uuid' => 'ticket-regular', 'name' => 'REGULAR', 'kategori' => 'category-1']);
        $file = new UploadedFile(base_path('tests/Fixtures/cinepolis-palembang-icon-multi-ticket.pdf'), 'palembang.pdf', 'application/pdf', null, true);
        $preview = $this->actingAs($user)->post(route('pelaporan.upload.cinepolis.preview'), ['file' => $file]);

        $response = $this->actingAs($user)->post(route('pelaporan.upload.cinepolis.quick-master'), [
            'token' => $preview->json('token'),
            'resource' => 'capacity',
            'cinema_uuid' => 'cinema-1',
            'ticket_uuid' => 'ticket-regular',
            'studio' => 'CINEMA 02',
            'kapasitas' => 100,
        ]);

        $response->assertOk()->assertJsonPath('status', 'success');
        $this->assertDatabaseHas('kapasitas', ['nama_bioskop' => 'cinema-1', 'type_tiket' => 'ticket-regular', 'studio' => '2', 'kapasitas' => '100']);
    }

    public function test_quick_master_rejects_values_not_present_in_preview(): void
    {
        $user = $this->createUser();
        DB::table('kategori_bioskops')->insert(['uuid' => 'category-1', 'name' => 'CINEPOLIS']);
        $file = new UploadedFile(base_path('tests/Fixtures/cinepolis-palembang-icon-multi-ticket.pdf'), 'palembang.pdf', 'application/pdf', null, true);
        $preview = $this->actingAs($user)->post(route('pelaporan.upload.cinepolis.preview'), ['file' => $file]);

        $this->actingAs($user)->post(route('pelaporan.upload.cinepolis.quick-master'), [
            'token' => $preview->json('token'),
            'resource' => 'ticket_type',
            'name' => 'TIKET PALSU',
        ])->assertStatus(422);
        $this->assertDatabaseMissing('type_tikets', ['name' => 'TIKET PALSU']);
    }

    public function test_preview_token_is_user_bound_and_same_report_cannot_be_imported_twice(): void
    {
        $owner = $this->seedResolvedMappings();
        $other = $this->createUser('user-2', 'other@example.test');
        $file = new UploadedFile(base_path('tests/Fixtures/cinepolis-vista-sample.pdf'), 'cinepolis.pdf', 'application/pdf', null, true);

        $preview = $this->actingAs($owner)->post(route('pelaporan.upload.cinepolis.preview'), ['file' => $file]);
        $token = $preview->json('token');
        $this->actingAs($other)->post(route('pelaporan.upload.cinepolis.confirm'), ['token' => $token])->assertStatus(403);
        $this->actingAs($owner)->post(route('pelaporan.upload.cinepolis.confirm'), ['token' => $token])->assertOk();

        $secondPreview = $this->actingAs($owner)->post(route('pelaporan.upload.cinepolis.preview'), ['file' => $file]);
        $this->actingAs($owner)->post(route('pelaporan.upload.cinepolis.confirm'), ['token' => $secondPreview->json('token')])
            ->assertStatus(422)
            ->assertJsonPath('message', 'Import diblokir karena terdapat data yang sudah pernah diimport.');
        $this->assertSame(7, DB::table('pelaporans')->count());
    }

    private function seedResolvedMappings(): User
    {
        $user = $this->createUser();
        DB::table('kategori_bioskops')->insert(['uuid' => 'category-1', 'name' => 'CINEPOLIS']);
        DB::table('master_bioskops')->insert(['uuid' => 'cinema-1', 'nama_bioskop' => 'MAXXBOX LIPPO VILLAGE', 'type' => 'category-1', 'kota' => 'TANGERANG']);
        DB::table('master_films')->insert(['uuid' => 'film-1', 'name' => 'BOLEHKAH SEKALI SAJA KUMENANGIS']);
        DB::table('kotas')->insert(['uuid' => 'city-1', 'nama' => 'TANGERANG', 'provinsi_id' => 'province-1']);
        DB::table('provinces')->insert(['uuid' => 'province-1', 'nama' => 'BANTEN']);
        foreach (['REGULAR', 'REGULAR-O'] as $index => $name) {
            $ticket = 'ticket-'.($index + 1);
            DB::table('type_tikets')->insert(['uuid' => $ticket, 'name' => $name, 'kategori' => 'category-1']);
            DB::table('kapasitas')->insert(['uuid' => 'studio-'.($index + 1), 'kategori' => 'category-1', 'nama_bioskop' => 'cinema-1', 'type_tiket' => $ticket, 'studio' => '5']);
        }
        return $user;
    }

    private function createUser(string $uuid = 'user-1', string $email = 'tester@example.test'): User
    {
        $id = DB::table('users')->insertGetId(['uuid' => $uuid, 'name' => 'Tester', 'email' => $email, 'password' => bcrypt('secret')]);
        return User::findOrFail($id);
    }
}
