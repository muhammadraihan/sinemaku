<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\Reports\XxiPdfParser;
use Mockery;

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

        Schema::create('cinema_ticket_prices', function ($table) {
            $table->increments('id'); $table->string('uuid')->unique(); $table->string('master_bioskop_uuid'); $table->string('type_tiket_uuid');
            $table->decimal('weekday_price', 15, 2); $table->decimal('friday_price', 15, 2); $table->decimal('weekend_holiday_price', 15, 2);
            $table->date('valid_from'); $table->date('valid_until')->nullable(); $table->boolean('active')->default(true); $table->string('created_by')->nullable(); $table->string('edited_by')->nullable(); $table->timestamps();
        });
        Schema::create('calendar_holidays', function ($table) {
            $table->increments('id'); $table->string('uuid')->unique(); $table->date('holiday_date')->unique(); $table->string('name'); $table->boolean('active')->default(true); $table->string('created_by')->nullable(); $table->string('edited_by')->nullable(); $table->timestamps();
        });

        Schema::create('report_upload_histories', function ($table) {
            $table->increments('id');
            $table->string('uuid')->unique();
            $table->string('provider');
            $table->string('original_filename');
            $table->unsignedBigInteger('file_size')->nullable();
            $table->string('status');
            $table->unsignedInteger('preview_rows')->default(0);
            $table->unsignedInteger('imported_rows')->default(0);
            $table->text('message')->nullable();
            $table->string('uploaded_by');
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });
    }

    public function test_inline_editor_is_shared_by_pdf_and_excel_datatables(): void
    {
        $view = file_get_contents(resource_path('views/pelaporan/index.blade.php'));
        $script = file_exists(public_path('js/import-preview-editor.js')) ? file_get_contents(public_path('js/import-preview-editor.js')) : '';
        $this->assertStringContainsString('ImportPreviewEditor.mount', $view);
        $this->assertStringContainsString('DataTable(', $script);
        $this->assertStringContainsString('Simpan koreksi', $script);
        $this->assertStringContainsString('Batal', $script);
    }

    public function test_preview_ui_has_a_modal_transition_fallback(): void
    {
        $view = file_get_contents(resource_path('views/pelaporan/index.blade.php'));

        $this->assertStringContainsString('function escapeHtml(value)', $view);
        $this->assertStringContainsString('function legacyRowForIssue(preview, issue)', $view);
        $this->assertStringContainsString('function openLegacyQuickMaster(button)', $view);
        $this->assertStringContainsString('History upload', $view);
        $this->assertStringContainsString('function loadUploadHistory()', $view);
        $this->assertStringContainsString("$.get(uploadHistoryUrl, { provider: bioskop })", $view);
        $this->assertStringContainsString('Belum ada file provider ini yang berhasil diimport.', $view);
        $this->assertStringContainsString('#upload-history-table-wrap { max-height:280px; overflow-y:auto; overflow-x:auto; }', $view);
        $this->assertStringContainsString('#upload-history-table thead th { position:sticky; top:0;', $view);
        $this->assertStringContainsString("route('pelaporan.upload-history')", $view);
        $this->assertStringContainsString('.swal2-container { z-index: 3000 !important; }', $view);
        $this->assertStringContainsString("$('#legacy-preview-issues .legacy-quick-master').off('click').on('click'", $view);
        $this->assertStringContainsString('function openPreviewAfterUploadModal(callback)', $view);
        $this->assertStringContainsString("window.setTimeout(finish, 450);", $view);
        $this->assertStringContainsString("openPreviewAfterUploadModal(function () { showLegacyPreview(res, bioskop, legacyUrls[bioskop]); });", $view);
        $this->assertLessThan(
            strpos($view, "$('#uploadForm').on('submit'"),
            strpos($view, 'var pdfEndpoints = {')
        );
    }

    public function test_inline_correction_is_remapped_audited_and_confirmed_without_preview_writes(): void
    {
        $owner = $this->seedResolvedXxiMappings();
        $preview = $this->actingAs($owner)->post(route('pelaporan.upload.xxi'), ['file' => $this->makeXxiFile()])->assertOk();
        $id = $preview->json('preview.0.row_id');
        $this->assertNotEmpty($id);
        $edited = $this->postJson(route('pelaporan.import-preview.correct'), [
            'provider' => 'XXI', 'token' => $preview->json('token'), 'row_id' => $id,
            'changes' => ['jam_tayang' => '12:15'], 'reason' => 'Koreksi jam dari sumber',
        ])->assertOk()->assertJsonPath('preview.0.jam_tayang', '12:15')->assertJsonPath('preview.0.row_id', $id);
        $cached = Cache::get('legacy_excel_preview:'.$preview->json('token'));
        $this->assertSame('11:00', $cached['rows'][0]['original_row']['jam_tayang']);
        $this->assertCount(1, $cached['corrections']);
        $this->assertSame(0, DB::table('pelaporans')->count());
        $this->assertSame(0, DB::table('report_upload_histories')->count());
        $this->postJson(route('pelaporan.upload.xxi.confirm'), ['token' => $preview->json('token')])->assertOk();
        $this->assertDatabaseHas('pelaporans', ['jam_tayang' => '12:15', 'gross' => '500000', 'net' => '450000']);
    }

    public function test_operator_can_exclude_and_restore_a_preview_row_with_reason_and_confirm_imports_only_included_rows(): void
    {
        $owner = $this->seedResolvedXxiMappings();
        $preview = $this->actingAs($owner)->post(route('pelaporan.upload.xxi'), ['file' => $this->makeXxiFile()])->assertOk();
        $id = $preview->json('preview.0.row_id');
        $this->postJson(route('pelaporan.import-preview.exclude'), ['provider'=>'XXI','token'=>$preview->json('token'),'row_id'=>$id,'reason'=>'Baris tidak valid'])->assertOk()->assertJsonPath('preview.0.excluded', true)->assertJsonPath('summary.imported_rows', 0);
        $cached = Cache::get('legacy_excel_preview:'.$preview->json('token'));
        $this->assertSame('Baris tidak valid', $cached['exclusions'][0]['reason']);
        $this->postJson(route('pelaporan.upload.xxi.confirm'), ['token'=>$preview->json('token')])->assertOk()->assertJsonPath('inserted', 0);
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

    public function test_xxi_pdf_preview_uses_weekend_master_price_and_confirm_persists_show_seven(): void
    {
        $owner = $this->seedResolvedXxiMappings();
        DB::table('cinema_ticket_prices')->insert([
            'uuid' => 'xxi-price', 'master_bioskop_uuid' => 'xxi-cinema', 'type_tiket_uuid' => 'xxi-ticket',
            'weekday_price' => 40000, 'friday_price' => 45000, 'weekend_holiday_price' => 50000,
            'valid_from' => '2026-01-01', 'valid_until' => null, 'active' => true,
        ]);
        $parser = Mockery::mock(XxiPdfParser::class);
        $parser->shouldReceive('parse')->once()->andReturn([
            'rows' => [[
                'source_row'=>10, 'tgl_tayang'=>'2026-09-26', 'nama_film'=>'FILM TEST', 'source_cinema'=>'XXI TEST', 'source_city'=>'JAKARTA',
                'studio'=>'1', 'capacity'=>100, 'ticket_name'=>'REGULAR', 'jam_tayang'=>'23:00', 'show'=>'7', 'jumlah'=>3, 'harga'=>0.0, 'tax'=>0.0, 'net'=>0.0,
            ]],
            'pending_free_assignments' => [], 'source_totals' => ['ptn'=>3, 'fp'=>0],
        ]);
        $this->app->instance(XxiPdfParser::class, $parser);
        $file = UploadedFile::fake()->create('xxi.pdf', 10, 'application/pdf');

        $preview = $this->actingAs($owner)->post(route('pelaporan.upload.xxi'), ['file' => $file]);
        $preview->assertOk()->assertJsonPath('source_type', 'pdf')->assertJsonPath('preview.0.show', '7')
            ->assertJsonPath('preview.0.jam_tayang', '23:00')->assertJsonPath('preview.0.harga', 50000)
            ->assertJsonPath('preview.0.price_day_group', 'weekend_holiday')->assertJsonPath('blocking_issues', []);
        $this->assertSame(0, DB::table('pelaporans')->count());
        $this->actingAs($owner)->postJson(route('pelaporan.import-preview.correct'), [
            'provider'=>'XXI','token'=>$preview->json('token'),'row_id'=>$preview->json('preview.0.row_id'),
            'changes'=>['harga'=>1],'reason'=>'Harga palsu',
        ])->assertUnprocessable();
        $this->postJson(route('pelaporan.import-preview.correct'), [
            'provider'=>'XXI','token'=>$preview->json('token'),'row_id'=>$preview->json('preview.0.row_id'),
            'changes'=>['jam_tayang'=>'23:15'],'reason'=>'Jam dikoreksi',
        ])->assertOk()->assertJsonPath('preview.0.harga', 50000)->assertJsonPath('source_type', 'pdf');
        $this->actingAs($owner)->post(route('pelaporan.upload.xxi.confirm'), ['token'=>$preview->json('token')])->assertOk()->assertJsonPath('inserted', 1);
        $this->assertDatabaseHas('pelaporans', ['show'=>'7','jam_tayang'=>'23:15','harga'=>'50000','jumlah'=>'3','gross'=>'150000']);
    }

    public function test_xxi_free_pass_requires_a_valid_show_then_persists_at_zero_value(): void
    {
        $owner = $this->seedResolvedXxiMappings();
        DB::table('type_tikets')->insert(['uuid' => 'xxi-free-pass', 'name' => 'FREE PASS', 'kategori' => 'xxi-category']);
        DB::table('kapasitas')->insert(['uuid' => 'xxi-free-capacity', 'kategori' => 'xxi-category', 'nama_bioskop' => 'xxi-cinema', 'type_tiket' => 'xxi-free-pass', 'studio' => '1', 'kapasitas' => '100']);

        $preview = $this->actingAs($owner)->post(route('pelaporan.upload.xxi'), ['file' => $this->makeXxiFreePassFile()]);
        $preview->assertOk()->assertJsonPath('status', 'success')
            ->assertJsonPath('pending_free_assignments.0.jumlah', 2)
            ->assertJsonPath('pending_free_assignments.0.candidate_shows.0.show', 1);
        $this->assertSame(0, DB::table('pelaporans')->count());
        $this->actingAs($owner)->post(route('pelaporan.upload.xxi.confirm'), ['token' => $preview->json('token')])->assertStatus(422);
        $this->actingAs($owner)->post(route('pelaporan.upload.xxi.assign-free'), [
            'token' => $preview->json('token'), 'assignment_key' => $preview->json('pending_free_assignments.0.key'), 'show' => 2,
        ])->assertStatus(422);

        $assigned = $this->actingAs($owner)->post(route('pelaporan.upload.xxi.assign-free'), [
            'token' => $preview->json('token'), 'assignment_key' => $preview->json('pending_free_assignments.0.key'), 'show' => 1,
        ]);
        $assigned->assertOk()->assertJsonPath('pending_free_assignments', [])
            ->assertJsonPath('preview.1.ticket_name', 'FREE PASS')
            ->assertJsonPath('preview.1.jumlah', 2)
            ->assertJsonPath('preview.1.harga', 0);

        $this->actingAs($owner)->post(route('pelaporan.upload.xxi.confirm'), ['token' => $preview->json('token')])
            ->assertOk()->assertJsonPath('inserted', 2);
        $this->assertDatabaseHas('pelaporans', ['type_tiket' => 'xxi-free-pass', 'show' => '1', 'jumlah' => '2', 'harga' => '0', 'gross' => '0', 'net' => '0']);
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

    public function test_cgv_preview_merges_duplicate_free_pass_from_separate_ticket_rows(): void
    {
        $owner = $this->createUser();
        DB::table('kategori_bioskops')->insert(['uuid' => 'cgv-category', 'name' => 'CGV']);

        $preview = $this->actingAs($owner)->post(route('pelaporan.upload.cgv'), [
            'file' => $this->makeCgvDuplicateFreeFile(),
        ]);

        $preview->assertOk()->assertJsonPath('status', 'success');
        $this->assertNotContains(
            'Detail preview duplikat setelah mapping/koreksi. Periksa studio, show, jam, dan tipe tiket sebelum import.',
            $preview->json('blocking_issues')
        );
        $free = collect($preview->json('preview'))->where('ticket_name', 'FREE PASS')->values();
        $this->assertCount(1, $free);
        $this->assertSame(3, $free[0]['jumlah']);
        $this->assertSame([2, 3], $free[0]['source_rows']);
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

    public function test_successful_confirm_records_original_file_uploader_and_upload_time(): void
    {
        $owner = $this->seedResolvedXxiMappings();

        $preview = $this->actingAs($owner)->post(route('pelaporan.upload.xxi'), ['file' => $this->makeXxiFile()]);

        $preview->assertOk()->assertJsonPath('status', 'success');
        $this->assertDatabaseCount('report_upload_histories', 0);

        $this->actingAs($owner)->post(route('pelaporan.upload.xxi.confirm'), ['token' => $preview->json('token')])
            ->assertOk()->assertJsonPath('inserted', 1);

        $this->assertDatabaseHas('report_upload_histories', [
            'original_filename' => 'xxi.xlsx',
            'status' => 'Berhasil diimport',
            'imported_rows' => 1,
        ]);

        DB::table('report_upload_histories')->insert([
            'uuid' => (string) \Illuminate\Support\Str::uuid(),
            'provider' => 'CGV',
            'original_filename' => 'cgv.xlsx',
            'file_size' => 100,
            'status' => 'Berhasil diimport',
            'preview_rows' => 1,
            'imported_rows' => 1,
            'uploaded_by' => $owner->uuid,
            'completed_at' => now(),
            'created_at' => now()->addSecond(),
            'updated_at' => now()->addSecond(),
        ]);

        $this->actingAs($owner)->get(route('pelaporan.upload-history', ['provider' => 'XXI']))
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.original_filename', 'xxi.xlsx')
            ->assertJsonPath('data.0.provider', 'XXI');
        $this->actingAs($owner)->get(route('pelaporan.upload-history', ['provider' => 'CGV']))
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.original_filename', 'cgv.xlsx');

        /* replay remains blocked */
        $this->actingAs($owner)->post(route('pelaporan.upload.xxi.confirm'), ['token' => $preview->json('token')])
            ->assertStatus(422);
        $this->assertNotEmpty($this->actingAs($owner)->get(route('pelaporan.upload-history'))->json('data.0.uploaded_at'));
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

    public function test_inline_corrections_reject_foreign_expired_invalid_and_canonical_payloads(): void
    {
        $owner = $this->seedResolvedXxiMappings();
        $preview = $this->actingAs($owner)->post(route('pelaporan.upload.xxi'), ['file' => $this->makeXxiFile()])->assertOk();
        $payload = ['provider'=>'XXI','token'=>$preview->json('token'),'row_id'=>$preview->json('preview.0.row_id'),'changes'=>['jam_tayang'=>'12:15'],'reason'=>'Koreksi sumber'];
        $other = $this->createUser('foreign', 'foreign@example.test');
        $this->actingAs($other)->postJson(route('pelaporan.import-preview.correct'), $payload)->assertForbidden();
        $this->actingAs($owner);
        foreach ([['gross'=>1], ['jumlah'=>-1], ['jumlah'=>1.5], ['tgl_tayang'=>'2026-02-30'], ['jam_tayang'=>'25:00'], ['harga'=>'NaN'], ['ticket_name'=>'FREE PASS'], ['studio'=>'<script>']] as $changes) {
            $this->postJson(route('pelaporan.import-preview.correct'), array_replace($payload, ['changes'=>$changes]))->assertUnprocessable();
        }
        $this->travel(31)->minutes();
        $this->postJson(route('pelaporan.import-preview.correct'), $payload)->assertUnprocessable();
        $this->assertSame(0, DB::table('pelaporans')->count());
    }

    public function test_inline_financial_correction_remains_blocked_through_free_remap_and_can_be_restored(): void
    {
        $owner = $this->seedResolvedXxiMappings();
        DB::table('type_tikets')->insert(['uuid'=>'free-ticket','name'=>'FREE PASS','kategori'=>'xxi-category']);
        DB::table('kapasitas')->insert(['uuid'=>'free-capacity','kategori'=>'xxi-category','nama_bioskop'=>'xxi-cinema','type_tiket'=>'free-ticket','studio'=>'1']);
        $preview = $this->actingAs($owner)->post(route('pelaporan.upload.xxi'), ['file'=>$this->makeXxiFreePassFile()])->assertOk();
        $payload = ['provider'=>'XXI','token'=>$preview->json('token'),'row_id'=>$preview->json('preview.0.row_id'),'changes'=>['jumlah'=>11],'reason'=>'Koreksi sumber'];
        $edited = $this->postJson(route('pelaporan.import-preview.correct'), $payload)->assertOk();
        $this->assertStringContainsString('Rekonsiliasi sumber', implode(' ', $edited->json('blocking_issues')));
        $assigned = $this->postJson(route('pelaporan.upload.xxi.assign-free'), ['token'=>$payload['token'],'assignment_key'=>$preview->json('pending_free_assignments.0.key'),'show'=>1])->assertOk();
        $this->assertNotSame($assigned->json('preview.0.row_id'), $assigned->json('preview.1.row_id'));
        $this->postJson(route('pelaporan.upload.xxi.confirm'), ['token'=>$payload['token']])->assertUnprocessable();
        $this->postJson(route('pelaporan.import-preview.correct'), array_replace($payload, ['changes'=>['jumlah'=>10]]))->assertOk()->assertJsonPath('blocking_issues', []);
        $this->postJson(route('pelaporan.upload.xxi.confirm'), ['token'=>$payload['token']])->assertOk();
        $this->assertSame(2, DB::table('pelaporans')->count());
        $this->assertStringContainsString('Koreksi sumber', DB::table('report_upload_histories')->value('message'));
    }

    public function test_inline_corrections_work_for_cgv_sams_and_nsc_and_survive_quick_master(): void
    {
        $owner = $this->createUser();
        foreach (['cgv'=>['CGV','makeCgvFile'], 'sams'=>['SAMS STUDIOS','makeSamsFile'], 'nsc'=>['NSC','makeNscFile']] as $slug => [$provider,$factory]) {
            DB::table('kategori_bioskops')->insert(['uuid'=>$slug,'name'=>$provider]);
            $preview = $this->actingAs($owner)->post(route('pelaporan.upload.'.$slug), ['file'=>$this->$factory()])->assertOk();
            $first = $preview->json('preview.0');
            DB::table('master_bioskops')->insert(['uuid'=>$slug.'-cinema','type'=>$slug,'nama_bioskop'=>$first['source_cinema'],'kota'=>'JAKARTA','pajak'=>'10']);
            DB::table('master_films')->insert(['uuid'=>$slug.'-film','name'=>$first['nama_film']]);
            foreach (array_unique(array_column($preview->json('preview'),'ticket_name')) as $i=>$ticket) {
                DB::table('type_tikets')->insert(['uuid'=>$slug.'-ticket-'.$i,'kategori'=>$slug,'name'=>$ticket]);
                DB::table('kapasitas')->insert(['uuid'=>$slug.'-capacity-'.$i,'kategori'=>$slug,'nama_bioskop'=>$slug.'-cinema','type_tiket'=>$slug.'-ticket-'.$i,'studio'=>preg_replace('/[^0-9]/','',$first['studio'])]);
            }
            $payload = ['provider'=>$provider,'token'=>$preview->json('token'),'row_id'=>$first['row_id'],'changes'=>['jam_tayang'=>'09:15'],'reason'=>'Koreksi jam sumber'];
            $this->postJson(route('pelaporan.import-preview.correct'), $payload)->assertOk()->assertJsonPath('preview.0.jam_tayang','09:15')->assertJsonPath('blocking_issues',[]);
            $this->postJson(route('pelaporan.upload.'.$slug.'.quick-master'), ['token'=>$payload['token'],'resource'=>'capacity','source_row'=>$first['source_row'],'studio'=>$first['studio'],'ticket_name'=>$first['ticket_name'],'kapasitas'=>100])->assertOk()->assertJsonPath('preview.0.jam_tayang','09:15')->assertJsonPath('preview.0.row_id',$first['row_id']);
            $this->postJson(route('pelaporan.upload.'.$slug.'.confirm'), ['token'=>$payload['token']])->assertOk();
            $this->assertDatabaseHas('pelaporans',['kategori'=>$slug,'jam_tayang'=>'09:15']);
        }
    }

    public function test_kcm_preview_blocks_missing_category_and_writes_nothing(): void
    {
        $owner = $this->createUser();
        $preview = $this->actingAs($owner)->post(route('pelaporan.upload.kcm'), ['file'=>$this->makeKcmFile()]);
        $preview->assertOk()->assertJsonPath('status','success')->assertJsonPath('source_profile','EXTERNAL');
        $this->assertStringContainsString('Kategori KCM belum tersedia', implode(' ', $preview->json('blocking_issues')));
        $this->assertSame(0, DB::table('pelaporans')->count());
        $this->assertSame(0, DB::table('report_upload_histories')->count());
    }

    public function test_kcm_confirm_persists_paid_and_free_rows_and_upload_history(): void
    {
        $owner = $this->createUser();
        DB::table('kategori_bioskops')->insert(['uuid'=>'kcm-category','name'=>'KCM']);
        DB::table('master_bioskops')->insert(['uuid'=>'kcm-cinema','nama_bioskop'=>'KCM TEST','type'=>'kcm-category','kota'=>'JAKARTA','pajak'=>'10']);
        DB::table('master_films')->insert(['uuid'=>'kcm-film','name'=>'FILM KCM']);
        DB::table('type_tikets')->insert([['uuid'=>'kcm-regular','name'=>'REGULAR','kategori'=>'kcm-category'],['uuid'=>'kcm-free','name'=>'FREE PASS','kategori'=>'kcm-category']]);
        DB::table('kapasitas')->insert([['uuid'=>'kcm-reg-cap','kategori'=>'kcm-category','nama_bioskop'=>'kcm-cinema','type_tiket'=>'kcm-regular','studio'=>'1','kapasitas'=>'100'],['uuid'=>'kcm-free-cap','kategori'=>'kcm-category','nama_bioskop'=>'kcm-cinema','type_tiket'=>'kcm-free','studio'=>'1','kapasitas'=>'100']]);
        $preview=$this->actingAs($owner)->post(route('pelaporan.upload.kcm'),['file'=>$this->makeKcmFile()]);
        $preview->assertOk()->assertJsonPath('blocking_issues',[])->assertJsonCount(2,'preview');
        $this->assertSame(0,DB::table('pelaporans')->count());
        $this->actingAs($owner)->post(route('pelaporan.upload.kcm.confirm'),['token'=>$preview->json('token')])->assertOk()->assertJsonPath('inserted',2);
        $this->assertDatabaseHas('pelaporans',['type_tiket'=>'kcm-regular','jumlah'=>'4','harga'=>'25000','gross'=>'100000']);
        $this->assertDatabaseHas('pelaporans',['type_tiket'=>'kcm-free','jumlah'=>'2','harga'=>'0','gross'=>'0']);
        $this->assertDatabaseHas('report_upload_histories',['provider'=>'KCM','original_filename'=>'kcm.xlsx','imported_rows'=>2]);
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

    private function makeXxiFreePassFile(): UploadedFile
    {
        return $this->makeWorkbook([
            ['Date', 'Film', 'Cinema', 'City', 'Studio', '11', '13', '15', '17', '19', '21', 'Total', 'Price', 'Free'],
            ['2026-01-01', 'FILM TEST', 'XXI TEST', 'JAKARTA', '1', '10', '-', '-', '-', '-', '-', '10', '50000', '2'],
        ], 'xxi-free-pass.xlsx');
    }

    private function makeCgvFile(): UploadedFile
    {
        return $this->makeWorkbook([
            ['Date', 'Cinema', 'Studio', 'Film', 'Format', 'Ticket', 'Price', 'Time 1', 'Admit 1', 'Free 1', 'Time 2', 'Admit 2', 'Free 2', 'Time 3', 'Admit 3', 'Free 3', 'Time 4', 'Admit 4', 'Free 4', 'Time 5', 'Admit 5', 'Free 5', 'Time 6', 'Admit 6', 'Free 6', 'Total', 'Free Total', 'Net'],
            ['2026-01-01', 'CGV TEST', '2', 'FILM CGV', '', 'VELVET', '75000', '10:15', '3', '1', '13:30', '4', '2', '15:45', '-', '3', '', '-', '', '', '-', '', '', '-', '', '', '', ''],
        ], 'cgv.xlsx');
    }

    private function makeCgvDuplicateFreeFile(): UploadedFile
    {
        return $this->makeWorkbook([
            ['Date', 'Cinema', 'Studio', 'Film', 'Format', 'Ticket', 'Price', 'Time 1', 'Admit 1', 'Free 1', 'Time 2', 'Admit 2', 'Free 2', 'Time 3', 'Admit 3', 'Free 3', 'Time 4', 'Admit 4', 'Free 4', 'Time 5', 'Admit 5', 'Free 5', 'Time 6', 'Admit 6', 'Free 6'],
            ['2026-09-28', 'CGV TEST', '2', 'FILM CGV', '', 'REGULAR', '35000', '15:40', '4', '1'],
            ['2026-09-28', 'CGV TEST', '2', 'FILM CGV', '', 'SWEETBOX', '45000', '15:40', '2', '2'],
        ], 'cgv-duplicate-free.xlsx');
    }

    private function makeKcmFile(): UploadedFile
    {
        return $this->makeWorkbook([
            ['Cinema','KCM TEST'], ['City','JAKARTA'], ['Report Date','2026-09-25'], [],
            ['Tanggal','ST','Judul Film','KP','Show 1',null,'Show 2',null,'TOTAL',null,'HTM','TOTAL'],
            [null,null,null,null,'SO','FP','SO','FP','SO','FP'],
            ['2026-09-25','1','FILM KCM','100',4,2,0,0,4,2,'25000','100000'],
        ], 'kcm.xlsx');
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
