<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class LegacyExcelImportPreviewTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config([
            'database.default' => 'legacy_excel_preview_test',
            'database.connections.legacy_excel_preview_test' => [
                'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '', 'foreign_key_constraints' => true,
            ],
            'cache.default' => 'array',
        ]);
        DB::purge('legacy_excel_preview_test');
        DB::setDefaultConnection('legacy_excel_preview_test');
        Cache::flush();

        foreach (['users', 'kategori_bioskops', 'master_bioskops', 'master_films', 'type_tikets', 'kapasitas', 'pelaporans'] as $tableName) {
            Schema::create($tableName, function ($table) {
                $table->increments('id');
                $table->string('uuid')->nullable()->unique();
                $table->string('name')->nullable();
                $table->string('nama_bioskop')->nullable();
                $table->string('type')->nullable();
                $table->string('kota')->nullable();
                $table->string('pajak')->nullable();
                $table->string('kategori')->nullable();
                $table->string('nama_film')->nullable();
                $table->date('tgl_tayang')->nullable();
                $table->string('jam_tayang')->nullable();
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

    public function test_preview_ui_has_a_modal_transition_fallback(): void
    {
        $view = file_get_contents(resource_path('views/pelaporan/index.blade.php'));

        $this->assertStringContainsString('function escapeHtml(value)', $view);
        $this->assertStringContainsString('function legacyRowForIssue(preview, issue)', $view);
        $this->assertStringContainsString('function openLegacyQuickMaster(button)', $view);
        $this->assertStringContainsString("$('#legacy-preview-issues .legacy-quick-master').off('click').on('click'", $view);
        $this->assertStringContainsString('function openPreviewAfterUploadModal(callback)', $view);
        $this->assertStringContainsString("window.setTimeout(finish, 450);", $view);
        $this->assertStringContainsString("openPreviewAfterUploadModal(function () { showLegacyPreview(res, bioskop, legacyUrls[bioskop]); });", $view);
        $this->assertLessThan(
            strpos($view, "$('#uploadForm').on('submit'"),
            strpos($view, 'var pdfEndpoints = {')
        );
    }

    public function test_xxi_preview_writes_no_canonical_rows_then_confirm_consumes_its_user_bound_token(): void
    {
        $owner = $this->seedResolvedXxiMappings();
        $file = $this->makeXxiFile();

        $preview = $this->actingAs($owner)->post(route('pelaporan.upload.xxi'), ['file' => $file]);
        $preview->assertOk()->assertJsonPath('status', 'success')->assertJsonCount(1, 'preview')->assertJsonPath('blocking_issues', []);
        $this->assertSame(0, DB::table('pelaporans')->count());
        $token = $preview->json('token');

        $other = $this->createUser('other-user', 'other@example.test');
        $this->actingAs($other)->post(route('pelaporan.upload.xxi.confirm'), ['token' => $token])->assertStatus(403);
        $this->actingAs($owner)->post(route('pelaporan.upload.xxi.confirm'), ['token' => $token])->assertOk()->assertJsonPath('inserted', 1);
        $this->assertSame(1, DB::table('pelaporans')->count());
        $this->actingAs($owner)->post(route('pelaporan.upload.xxi.confirm'), ['token' => $token])->assertStatus(422);
    }

    public function test_xxi_preview_resolves_duplicate_cinema_names_by_exact_city(): void
    {
        $owner = $this->createUser();
        DB::table('kategori_bioskops')->insert(['uuid' => 'xxi-category', 'name' => 'XXI']);
        DB::table('master_bioskops')->insert([
            ['uuid' => 'xxi-jakarta', 'nama_bioskop' => 'XXI TEST', 'type' => 'xxi-category', 'kota' => 'JAKARTA', 'pajak' => '10'],
            ['uuid' => 'xxi-surabaya', 'nama_bioskop' => 'XXI TEST', 'type' => 'xxi-category', 'kota' => 'SURABAYA', 'pajak' => '10'],
        ]);
        DB::table('master_films')->insert(['uuid' => 'xxi-film', 'name' => 'FILM TEST']);
        DB::table('type_tikets')->insert(['uuid' => 'xxi-ticket', 'name' => 'REGULAR', 'kategori' => 'xxi-category']);
        DB::table('kapasitas')->insert(['uuid' => 'xxi-capacity', 'kategori' => 'xxi-category', 'nama_bioskop' => 'xxi-jakarta', 'type_tiket' => 'xxi-ticket', 'studio' => '1', 'kapasitas' => '100']);

        $preview = $this->actingAs($owner)->post(route('pelaporan.upload.xxi'), ['file' => $this->makeXxiFile()]);

        $preview->assertOk()
            ->assertJsonPath('blocking_issues', [])
            ->assertJsonPath('preview.0.cinema_uuid', 'xxi-jakarta')
            ->assertJsonPath('preview.0.mapping_status', 'Siap');
    }

    public function test_xxi_preview_warns_and_blocks_when_name_and_city_are_still_ambiguous(): void
    {
        $owner = $this->createUser();
        DB::table('kategori_bioskops')->insert(['uuid' => 'xxi-category', 'name' => 'XXI']);
        DB::table('master_bioskops')->insert([
            ['uuid' => 'xxi-jakarta-a', 'nama_bioskop' => 'XXI TEST', 'type' => 'xxi-category', 'kota' => 'JAKARTA', 'pajak' => '10'],
            ['uuid' => 'xxi-jakarta-b', 'nama_bioskop' => 'XXI TEST', 'type' => 'xxi-category', 'kota' => 'JAKARTA', 'pajak' => '10'],
        ]);
        DB::table('master_films')->insert(['uuid' => 'xxi-film', 'name' => 'FILM TEST']);
        DB::table('type_tikets')->insert(['uuid' => 'xxi-ticket', 'name' => 'REGULAR', 'kategori' => 'xxi-category']);

        $preview = $this->actingAs($owner)->post(route('pelaporan.upload.xxi'), ['file' => $this->makeXxiFile()]);

        $preview->assertOk()
            ->assertJsonPath('preview.0.mapping_status', 'Diblokir')
            ->assertJsonPath('preview.0.cinema_uuid', null)
            ->assertJsonFragment(['warnings' => ['Bioskop XXI TEST di kota JAKARTA memiliki lebih dari satu mapping master dan memerlukan pemilihan.']]);
        $this->assertStringContainsString('Bioskop XXI TEST di kota JAKARTA memiliki mapping ambigu', implode(' ', $preview->json('blocking_issues')));
    }

    public function test_xxi_quick_master_capacity_uses_the_preview_row_mapping(): void
    {
        $owner = $this->createUser();
        DB::table('kategori_bioskops')->insert(['uuid' => 'xxi-category', 'name' => 'XXI']);
        DB::table('master_bioskops')->insert(['uuid' => 'xxi-cinema', 'nama_bioskop' => 'XXI TEST', 'type' => 'xxi-category', 'kota' => 'JAKARTA', 'pajak' => '10']);
        DB::table('master_films')->insert(['uuid' => 'xxi-film', 'name' => 'FILM TEST']);
        DB::table('type_tikets')->insert(['uuid' => 'xxi-ticket', 'name' => 'REGULAR', 'kategori' => 'xxi-category']);

        $preview = $this->actingAs($owner)->post(route('pelaporan.upload.xxi'), ['file' => $this->makeXxiFile()]);
        $preview->assertOk()->assertJsonFragment(['mapping_status' => 'Diblokir']);

        $refresh = $this->actingAs($owner)->post(route('pelaporan.upload.xxi.quick-master'), [
            'token' => $preview->json('token'),
            'resource' => 'capacity',
            'source_row' => 2,
            'ticket_name' => 'REGULAR',
            'studio' => '1',
            'kapasitas' => 100,
        ]);

        $refresh->assertOk()->assertJsonPath('status', 'success')->assertJsonPath('blocking_issues', []);
        $this->assertDatabaseHas('kapasitas', ['nama_bioskop' => 'xxi-cinema', 'type_tiket' => 'xxi-ticket', 'studio' => '1', 'kapasitas' => '100']);

        $this->actingAs($owner)->post(route('pelaporan.upload.xxi.quick-master'), [
            'token' => $preview->json('token'),
            'resource' => 'capacity',
            'source_row' => 2,
            'ticket_name' => 'REGULAR',
            'studio' => '1',
            'kapasitas' => 120,
        ])->assertOk()->assertJsonPath('status', 'success');

        $this->assertSame(1, DB::table('kapasitas')->count());
        $this->assertDatabaseHas('kapasitas', ['nama_bioskop' => 'xxi-cinema', 'type_tiket' => 'xxi-ticket', 'studio' => '1', 'kapasitas' => '120']);
        $this->assertSame(0, DB::table('pelaporans')->count());
    }

    public function test_cgv_preview_warns_and_blocks_duplicate_cinema_names_without_source_city(): void
    {
        $owner = $this->createUser();
        DB::table('kategori_bioskops')->insert(['uuid' => 'cgv-category', 'name' => 'CGV']);
        DB::table('master_bioskops')->insert([
            ['uuid' => 'cgv-jakarta', 'nama_bioskop' => 'CGV TEST', 'type' => 'cgv-category', 'kota' => 'JAKARTA'],
            ['uuid' => 'cgv-surabaya', 'nama_bioskop' => 'CGV TEST', 'type' => 'cgv-category', 'kota' => 'SURABAYA'],
        ]);

        $preview = $this->actingAs($owner)->post(route('pelaporan.upload.cgv'), ['file' => $this->makeCgvFile()]);

        $preview->assertOk()->assertJsonPath('preview.0.cinema_uuid', null);
        $this->assertStringContainsString('Bioskop CGV TEST memiliki mapping ambigu', implode(' ', $preview->json('blocking_issues')));
        $this->assertContains('Bioskop CGV TEST memiliki lebih dari satu mapping master dan memerlukan pemilihan.', $preview->json('warnings'));
    }

    public function test_sams_preview_warns_and_blocks_duplicate_cinema_names_without_source_city(): void
    {
        $owner = $this->createUser();
        DB::table('kategori_bioskops')->insert(['uuid' => 'sams-category', 'name' => 'SAMS STUDIOS']);
        DB::table('master_bioskops')->insert([
            ['uuid' => 'sams-jakarta', 'nama_bioskop' => 'SAMS TEST', 'type' => 'sams-category', 'kota' => 'JAKARTA'],
            ['uuid' => 'sams-bandung', 'nama_bioskop' => 'SAMS TEST', 'type' => 'sams-category', 'kota' => 'BANDUNG'],
        ]);

        $preview = $this->actingAs($owner)->post(route('pelaporan.upload.sams'), ['file' => $this->makeSamsFile()]);

        $preview->assertOk()->assertJsonPath('preview.0.cinema_uuid', null);
        $this->assertStringContainsString('Bioskop SAMS TEST memiliki mapping ambigu', implode(' ', $preview->json('blocking_issues')));
        $this->assertContains('Bioskop SAMS TEST memiliki lebih dari satu mapping master dan memerlukan pemilihan.', $preview->json('warnings'));
    }

    public function test_cgv_preview_preserves_ticket_type_and_six_showtime_columns_without_writing(): void
    {
        $owner = $this->createUser();
        DB::table('kategori_bioskops')->insert(['uuid' => 'cgv-category', 'name' => 'CGV']);

        $preview = $this->actingAs($owner)->post(route('pelaporan.upload.cgv'), ['file' => $this->makeCgvFile()]);

        $preview->assertOk()->assertJsonPath('status', 'success')->assertJsonCount(5, 'preview')
            ->assertJsonPath('preview.0.ticket_name', 'VELVET')
            ->assertJsonPath('preview.0.jam_tayang', '10:15')
            ->assertJsonPath('preview.2.ticket_name', 'VELVET')
            ->assertJsonPath('preview.2.jam_tayang', '13:30');
        $this->assertSame(0, DB::table('pelaporans')->count());
    }

    public function test_cgv_preview_maps_each_showtime_free_to_zero_priced_free_pass(): void
    {
        $owner = $this->createUser();
        DB::table('kategori_bioskops')->insert(['uuid' => 'cgv-category', 'name' => 'CGV']);

        $preview = $this->actingAs($owner)->post(route('pelaporan.upload.cgv'), ['file' => $this->makeCgvFile()]);

        $preview->assertOk()->assertJsonPath('status', 'success')->assertJsonCount(5, 'preview');
        $this->assertSame(['VELVET', 'FREE PASS', 'VELVET', 'FREE PASS', 'FREE PASS'], array_column($preview->json('preview'), 'ticket_name'));
        $this->assertSame(['10:15', '10:15', '13:30', '13:30', '15:45'], array_column($preview->json('preview'), 'jam_tayang'));
        $this->assertSame([3, 1, 4, 2, 3], array_column($preview->json('preview'), 'jumlah'));
        $this->assertSame([75000.0, 0.0, 75000.0, 0.0, 0.0], array_map('floatval', array_column($preview->json('preview'), 'harga')));
        $this->assertSame(0, DB::table('pelaporans')->count());
    }

    public function test_cgv_confirm_import_persists_free_pass_with_zero_amounts(): void
    {
        $owner = $this->createUser();
        DB::table('kategori_bioskops')->insert(['uuid' => 'cgv-category', 'name' => 'CGV']);
        DB::table('master_bioskops')->insert(['uuid' => 'cgv-cinema', 'nama_bioskop' => 'CGV TEST', 'type' => 'cgv-category', 'kota' => 'JAKARTA', 'pajak' => '10']);
        DB::table('master_films')->insert(['uuid' => 'cgv-film', 'name' => 'FILM CGV']);
        DB::table('type_tikets')->insert([
            ['uuid' => 'cgv-velvet', 'name' => 'VELVET', 'kategori' => 'cgv-category'],
            ['uuid' => 'cgv-free-pass', 'name' => 'FREE PASS', 'kategori' => 'cgv-category'],
        ]);
        DB::table('kapasitas')->insert([
            ['uuid' => 'cgv-velvet-capacity', 'kategori' => 'cgv-category', 'nama_bioskop' => 'cgv-cinema', 'type_tiket' => 'cgv-velvet', 'studio' => '2', 'kapasitas' => '120'],
            ['uuid' => 'cgv-free-capacity', 'kategori' => 'cgv-category', 'nama_bioskop' => 'cgv-cinema', 'type_tiket' => 'cgv-free-pass', 'studio' => '2', 'kapasitas' => '120'],
        ]);

        $preview = $this->actingAs($owner)->post(route('pelaporan.upload.cgv'), ['file' => $this->makeCgvFile()]);
        $preview->assertOk()->assertJsonPath('blocking_issues', [])->assertJsonPath('summary.ready', 5);

        $this->actingAs($owner)->post(route('pelaporan.upload.cgv.confirm'), [
            'token' => $preview->json('token'),
        ])->assertOk()->assertJsonPath('inserted', 5);

        $this->assertSame(2, DB::table('pelaporans')->where('type_tiket', 'cgv-velvet')->count());
        $this->assertSame(3, DB::table('pelaporans')->where('type_tiket', 'cgv-free-pass')->count());
        $this->assertSame(0, DB::table('pelaporans')->where('type_tiket', 'cgv-free-pass')->where(function ($query) {
            $query->where('harga', '!=', '0')->orWhere('gross', '!=', '0')->orWhere('net', '!=', '0');
        })->count());
    }

    public function test_nsc_preview_preserves_paid_and_bogof_rows_without_writing(): void
    {
        $owner = $this->createUser();
        DB::table('kategori_bioskops')->insert(['uuid' => 'nsc-category', 'name' => 'NSC']);

        $preview = $this->actingAs($owner)->post(route('pelaporan.upload.nsc'), ['file' => $this->makeNscFile()]);

        $preview->assertOk()->assertJsonPath('status', 'success')->assertJsonCount(2, 'preview')
            ->assertJsonPath('preview.0.source_cinema', 'NSC TEST')
            ->assertJsonPath('preview.0.ticket_name', 'REGULAR')
            ->assertJsonPath('preview.1.ticket_name', 'BOGOF')
            ->assertJsonPath('summary.paid', 4)
            ->assertJsonPath('summary.free', 2)
            ->assertJsonPath('summary.gross', 100000);
        $this->assertSame(0, DB::table('pelaporans')->count());
    }

    public function test_nsc_can_assign_unallocated_free_to_a_source_show_before_confirm(): void
    {
        $owner = $this->createUser();
        DB::table('kategori_bioskops')->insert(['uuid' => 'nsc-category', 'name' => 'NSC']);

        $preview = $this->actingAs($owner)->post(route('pelaporan.upload.nsc'), ['file' => $this->makeNscMissingFreeFile()]);
        $preview->assertOk()->assertJsonPath('pending_free_assignments.0.jumlah', 5);
        $this->assertNotEmpty($preview->json('blocking_issues'));
        $this->actingAs($owner)->post(route('pelaporan.upload.nsc.confirm'), ['token' => $preview->json('token')])->assertStatus(422);
        $this->actingAs($owner)->post(route('pelaporan.upload.nsc.assign-free'), [
            'token' => $preview->json('token'),
            'assignment_key' => $preview->json('pending_free_assignments.0.key'),
            'show' => 7,
        ])->assertStatus(422);

        $assigned = $this->actingAs($owner)->post(route('pelaporan.upload.nsc.assign-free'), [
            'token' => $preview->json('token'),
            'assignment_key' => $preview->json('pending_free_assignments.0.key'),
            'show' => 3,
        ]);

        $assigned->assertOk()->assertJsonPath('status', 'success')->assertJsonPath('pending_free_assignments', [])
            ->assertJsonPath('preview.1.ticket_name', 'BOGOF')
            ->assertJsonPath('preview.1.jam_tayang', '14:00')
            ->assertJsonPath('preview.1.jumlah', 5);
        $this->assertSame(0, DB::table('pelaporans')->count());
    }

    public function test_nsc_confirm_import_persists_paid_and_bogof_rows_once(): void
    {
        $owner = $this->createUser();
        DB::table('kategori_bioskops')->insert(['uuid' => 'nsc-category', 'name' => 'NSC']);
        DB::table('master_bioskops')->insert(['uuid' => 'nsc-cinema', 'nama_bioskop' => 'NSC TEST', 'type' => 'nsc-category', 'kota' => 'JAKARTA', 'pajak' => '10']);
        DB::table('master_films')->insert(['uuid' => 'nsc-film', 'name' => 'FILM NSC']);
        DB::table('type_tikets')->insert([
            ['uuid' => 'nsc-regular', 'name' => 'REGULAR', 'kategori' => 'nsc-category'],
            ['uuid' => 'nsc-bogof', 'name' => 'BOGOF', 'kategori' => 'nsc-category'],
        ]);
        DB::table('kapasitas')->insert([
            ['uuid' => 'nsc-regular-capacity', 'kategori' => 'nsc-category', 'nama_bioskop' => 'nsc-cinema', 'type_tiket' => 'nsc-regular', 'studio' => '1', 'kapasitas' => '100'],
            ['uuid' => 'nsc-bogof-capacity', 'kategori' => 'nsc-category', 'nama_bioskop' => 'nsc-cinema', 'type_tiket' => 'nsc-bogof', 'studio' => '1', 'kapasitas' => '100'],
        ]);

        $preview = $this->actingAs($owner)->post(route('pelaporan.upload.nsc'), ['file' => $this->makeNscFile()]);
        $preview->assertOk()->assertJsonPath('blocking_issues', []);

        $this->actingAs($owner)->post(route('pelaporan.upload.nsc.confirm'), ['token' => $preview->json('token')])
            ->assertOk()->assertJsonPath('inserted', 2);
        $this->assertDatabaseHas('pelaporans', ['type_tiket' => 'nsc-regular', 'harga' => '25000', 'jumlah' => '4', 'gross' => '100000']);
        $this->assertDatabaseHas('pelaporans', ['type_tiket' => 'nsc-bogof', 'harga' => '0', 'jumlah' => '2', 'gross' => '0']);
        $this->actingAs($owner)->post(route('pelaporan.upload.nsc.confirm'), ['token' => $preview->json('token')])->assertStatus(422);
    }

    public function test_sams_preview_maps_paid_voucher_and_free_to_their_ticket_types(): void
    {
        $owner = $this->createUser();
        DB::table('kategori_bioskops')->insert(['uuid' => 'sams-category', 'name' => 'SAMS STUDIOS']);

        $preview = $this->actingAs($owner)->post(route('pelaporan.upload.sams'), ['file' => $this->makeSamsFile()]);

        $preview->assertOk()->assertJsonPath('status', 'success')->assertJsonCount(3, 'preview');
        $this->assertSame(['REGULAR', 'BOGOF', 'FREE PASS'], array_column($preview->json('preview'), 'ticket_name'));
        $this->assertSame([5, 2, 3], array_column($preview->json('preview'), 'jumlah'));
        $this->assertSame([50000.0, 0.0, 0.0], array_map('floatval', array_column($preview->json('preview'), 'harga')));
        $this->assertSame(0, DB::table('pelaporans')->count());
    }

    public function test_sams_quick_master_capacity_persists_each_preview_ticket_type(): void
    {
        $owner = $this->createUser();
        DB::table('kategori_bioskops')->insert(['uuid' => 'sams-category', 'name' => 'SAMS STUDIOS']);
        DB::table('master_bioskops')->insert(['uuid' => 'sams-cinema', 'nama_bioskop' => 'SAMS TEST', 'type' => 'sams-category', 'kota' => 'JAKARTA', 'pajak' => '10']);
        DB::table('master_films')->insert(['uuid' => 'sams-film', 'name' => 'FILM SAMS']);
        DB::table('type_tikets')->insert([
            ['uuid' => 'sams-regular', 'name' => 'REGULAR', 'kategori' => 'sams-category'],
            ['uuid' => 'sams-bogof', 'name' => 'BOGOF', 'kategori' => 'sams-category'],
            ['uuid' => 'sams-free-pass', 'name' => 'FREE PASS', 'kategori' => 'sams-category'],
        ]);

        $preview = $this->actingAs($owner)->post(route('pelaporan.upload.sams'), ['file' => $this->makeSamsFile()]);
        $preview->assertOk()->assertJsonCount(3, 'blocking_issues');

        foreach (['REGULAR' => 'sams-regular', 'BOGOF' => 'sams-bogof', 'FREE PASS' => 'sams-free-pass'] as $ticketName => $ticketUuid) {
            $this->actingAs($owner)->post(route('pelaporan.upload.sams.quick-master'), [
                'token' => $preview->json('token'),
                'resource' => 'capacity',
                'source_row' => 2,
                'ticket_name' => $ticketName,
                'studio' => 'Studio 2',
                'kapasitas' => 120,
            ])->assertOk()->assertJsonPath('status', 'success');

            $this->assertDatabaseHas('kapasitas', [
                'kategori' => 'sams-category',
                'nama_bioskop' => 'sams-cinema',
                'type_tiket' => $ticketUuid,
                'studio' => '2',
                'kapasitas' => '120',
            ]);
        }

        $this->assertSame(3, DB::table('kapasitas')->count());
    }

    public function test_sams_preview_shows_missing_mappings_without_writing_and_quick_master_rejects_out_of_preview_values(): void
    {
        $owner = $this->createUser();
        DB::table('kategori_bioskops')->insert(['uuid' => 'sams-category', 'name' => 'SAMS STUDIOS']);
        $file = $this->makeSamsFile();

        $preview = $this->actingAs($owner)->post(route('pelaporan.upload.sams'), ['file' => $file]);
        $preview->assertOk()->assertJsonPath('status', 'success');
        $this->assertNotEmpty($preview->json('blocking_issues'));
        $this->assertSame(0, DB::table('pelaporans')->count());

        $this->actingAs($owner)->post(route('pelaporan.upload.sams.quick-master'), [
            'token' => $preview->json('token'), 'resource' => 'film', 'name' => 'FILM PALSU',
        ])->assertStatus(422);
        $this->assertDatabaseMissing('master_films', ['name' => 'FILM PALSU']);
    }

    private function seedResolvedXxiMappings(): User
    {
        $user = $this->createUser();
        DB::table('kategori_bioskops')->insert(['uuid' => 'xxi-category', 'name' => 'XXI']);
        DB::table('master_bioskops')->insert(['uuid' => 'xxi-cinema', 'nama_bioskop' => 'XXI TEST', 'type' => 'xxi-category', 'kota' => 'JAKARTA', 'pajak' => '10']);
        DB::table('master_films')->insert(['uuid' => 'xxi-film', 'name' => 'FILM TEST']);
        DB::table('type_tikets')->insert(['uuid' => 'xxi-ticket', 'name' => 'REGULAR', 'kategori' => 'xxi-category']);
        DB::table('kapasitas')->insert(['uuid' => 'xxi-capacity', 'kategori' => 'xxi-category', 'nama_bioskop' => 'xxi-cinema', 'type_tiket' => 'xxi-ticket', 'studio' => '1', 'kapasitas' => '100']);
        return $user;
    }

    private function createUser(string $uuid = 'legacy-user', string $email = 'legacy@example.test'): User
    {
        $id = DB::table('users')->insertGetId(['uuid' => $uuid, 'name' => 'Tester', 'email' => $email, 'password' => bcrypt('secret')]);
        return User::findOrFail($id);
    }

    private function makeXxiFile(): UploadedFile
    {
        return $this->makeWorkbook([
            ['Date', 'Film', 'Cinema', 'City', 'Studio', '11', '13', '15', '17', '19', '21', 'Total', 'Price', 'Free'],
            ['2026-01-01', 'FILM TEST', 'XXI TEST', 'JAKARTA', '1', '10', '-', '-', '-', '-', '-', '', '50000', ''],
        ], 'xxi.xlsx');
    }

    private function makeCgvFile(): UploadedFile
    {
        return $this->makeWorkbook([
            ['Date', 'Cinema', 'Studio', 'Film', 'Format', 'Ticket', 'Price', 'Time 1', 'Admit 1', 'Free 1', 'Time 2', 'Admit 2', 'Free 2', 'Time 3', 'Admit 3', 'Free 3', 'Time 4', 'Admit 4', 'Free 4', 'Time 5', 'Admit 5', 'Free 5', 'Time 6', 'Admit 6', 'Free 6', 'Total', 'Free Total', 'Net'],
            ['2026-01-01', 'CGV TEST', '2', 'FILM CGV', '', 'VELVET', '75000', '10:15', '3', '1', '13:30', '4', '2', '15:45', '-', '3', '', '-', '', '', '-', '', '', '-', '', '', '', ''],
        ], 'cgv.xlsx');
    }

    private function makeNscFile(): UploadedFile
    {
        return $this->makeWorkbook([
            [null, 'TICKET SALES REPORT'],
            ['Site :', 'NSC TEST'],
            ['Address :', 'JAKARTA'],
            [],
            ['Distributor :', 'SINEMAKU PICTURES'],
            ['Movie Title :', 'FILM NSC'],
            ['Show Date :', '9/25/2026'],
            [],
            ['Cinema', 'Movie Format', 'Seat Grade', 'Price', '1st Showtime', null, null, '2nd Showtime', null, null, '3rd Showtime', null, null, '4th Showtime', null, null, '5th Showtime', null, null, '6th Showtime', null, null, '7th Showtime', null, null, 'Total', null, 'Total Sales'],
            [null, null, null, null, 'Time', 'Paid', 'Free', 'Time', 'Paid', 'Free', 'Time', 'Paid', 'Free', 'Time', 'Paid', 'Free', 'Time', 'Paid', 'Free', 'Time', 'Paid', 'Free', 'Time', 'Paid', 'Free', 'Paid', 'Free'],
            ['1', '2D', 'Regular', 'Rp 25000', '10:00', 4, 2, null, null, null, null, null, null, null, null, null, null, null, null, null, null, null, null, null, null, 4, 2, 'Rp 100000'],
            ['Grand Total', null, null, null, null, null, null, null, null, null, null, null, null, null, null, null, null, null, null, null, null, null, null, null, null, 4, 2, '100000'],
        ], 'nsc.xlsx');
    }

    private function makeNscMissingFreeFile(): UploadedFile
    {
        return $this->makeWorkbook([
            [null, 'TICKET SALES REPORT'],
            ['Site :', 'NSC TEST'],
            ['Address :', 'JAKARTA'],
            [],
            ['Distributor :', 'SINEMAKU PICTURES'],
            ['Movie Title :', 'FILM NSC'],
            ['Show Date :', '24-Sep-26'],
            [],
            ['Cinema', 'Movie Format', 'Seat Grade', 'Price', '1st Showtime', null, null, '2nd Showtime', null, null, '3rd Showtime', null, null, '4th Showtime', null, null, '5th Showtime', null, null, '6th Showtime', null, null, '7th Showtime', null, null, 'Total', null, 'Total Sales'],
            [null, null, null, null, 'Time', 'Paid', 'Free', 'Time', 'Paid', 'Free', 'Time', 'Paid', 'Free', 'Time', 'Paid', 'Free', 'Time', 'Paid', 'Free', 'Time', 'Paid', 'Free', 'Time', 'Paid', 'Free', 'Paid', 'Free'],
            ['1', '2D', 'Regular', 'Rp 25000', null, null, null, null, null, null, '14:00', 4, null, null, null, null, null, null, null, null, null, null, null, null, null, 4, 5, 'Rp 100000'],
            ['Grand Total', null, null, null, null, null, null, null, null, null, null, null, null, null, null, null, null, null, null, null, null, null, null, null, null, 4, 5, '100000'],
        ], 'nsc-missing-free.xlsx');
    }

    private function makeSamsFile(): UploadedFile
    {
        return $this->makeWorkbook([
            ['Film', 'Cinema', 'Studio', 'Date', 'Time', 'Price', 'Status', 'Approval', 'Net', 'Total', 'Paid', 'Voucher', 'Free'],
            ['FILM SAMS', 'SAMS TEST', 'Studio 2', '2026-01-01', '10:00', 'Rp. 50000', '', '', '', '', '5', '2', '3'],
        ], 'sams.xlsx');
    }

    private function makeWorkbook(array $rows, string $filename): UploadedFile
    {
        $spreadsheet = new Spreadsheet();
        $spreadsheet->getActiveSheet()->fromArray($rows, null, 'A1');
        $path = tempnam(sys_get_temp_dir(), 'legacy-excel-');
        (new Xlsx($spreadsheet))->save($path);
        return new UploadedFile($path, $filename, 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);
    }
}
