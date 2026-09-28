<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class MasterBioskopPriceManagementTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'database.default' => 'master_bioskop_price_test',
            'database.connections.master_bioskop_price_test' => [
                'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '', 'foreign_key_constraints' => true,
            ],
        ]);
        DB::purge('master_bioskop_price_test');
        DB::setDefaultConnection('master_bioskop_price_test');

        Schema::create('users', function ($table) {
            $table->increments('id'); $table->string('uuid')->unique(); $table->string('name')->nullable();
            $table->string('email')->nullable(); $table->string('password')->nullable(); $table->rememberToken(); $table->timestamps();
        });
        Schema::create('kategori_bioskops', function ($table) {
            $table->increments('id'); $table->string('uuid')->unique(); $table->string('name');
            $table->string('created_by')->nullable(); $table->string('edited_by')->nullable(); $table->timestamps();
        });
        Schema::create('master_bioskops', function ($table) {
            $table->increments('id'); $table->string('uuid')->unique(); $table->string('type');
            $table->string('nama_bioskop'); $table->string('kota'); $table->decimal('pajak', 5, 2)->nullable();
            $table->string('no_telephone')->nullable(); $table->string('created_by')->nullable(); $table->string('edited_by')->nullable(); $table->timestamps();
        });
        Schema::create('type_tikets', function ($table) {
            $table->increments('id'); $table->string('uuid')->unique(); $table->string('name'); $table->string('kategori');
            $table->string('created_by')->nullable(); $table->string('edited_by')->nullable(); $table->timestamps();
        });
        Schema::create('kapasitas', function ($table) {
            $table->increments('id'); $table->string('uuid')->nullable()->unique(); $table->string('kategori');
            $table->string('kota'); $table->string('nama_bioskop'); $table->string('type_tiket');
            $table->string('studio'); $table->unsignedInteger('kapasitas'); $table->timestamps();
        });
        Schema::create('cinema_ticket_prices', function ($table) {
            $table->increments('id'); $table->string('uuid')->unique(); $table->string('master_bioskop_uuid'); $table->string('type_tiket_uuid');
            $table->decimal('weekday_price', 15, 2); $table->decimal('friday_price', 15, 2); $table->decimal('weekend_holiday_price', 15, 2);
            $table->date('valid_from'); $table->date('valid_until')->nullable(); $table->boolean('active')->default(true);
            $table->string('created_by')->nullable(); $table->string('edited_by')->nullable(); $table->timestamps();
        });
        Schema::create('calendar_holidays', function ($table) {
            $table->increments('id'); $table->string('uuid')->unique(); $table->date('holiday_date')->unique(); $table->string('name');
            $table->boolean('active')->default(true); $table->string('created_by')->nullable(); $table->string('edited_by')->nullable(); $table->timestamps();
        });
    }

    public function test_xxi_creation_saves_regular_price_in_the_same_flow(): void
    {
        $user = $this->user();
        DB::table('kategori_bioskops')->insert(['uuid'=>'xxi-category','name'=>'XXI']);
        DB::table('type_tikets')->insert(['uuid'=>'regular-ticket','name'=>'REGULAR','kategori'=>'xxi-category']);

        $response = $this->actingAs($user)->post(route('masterbioskop.store'), [
            'type'=>'xxi-category', 'nama_bioskop'=>'Cinema Test', 'kota'=>'Jakarta', 'pajak'=>10,
            'ticket_price'=>[
                'weekday_price'=>40000, 'friday_price'=>45000, 'weekend_holiday_price'=>50000,
                'valid_from'=>'2026-09-01', 'valid_until'=>null,
            ],
        ]);

        $response->assertRedirect(route('masterbioskop.index'));
        $cinemaUuid = DB::table('master_bioskops')->value('uuid');
        $this->assertDatabaseHas('cinema_ticket_prices', [
            'master_bioskop_uuid'=>$cinemaUuid, 'type_tiket_uuid'=>'regular-ticket',
            'weekday_price'=>40000, 'friday_price'=>45000, 'weekend_holiday_price'=>50000,
            'active'=>1,
        ]);
    }

    public function test_xxi_creation_accepts_indonesian_price_format(): void
    {
        $user = $this->user();
        DB::table('kategori_bioskops')->insert(['uuid'=>'xxi-category','name'=>'XXI']);
        DB::table('type_tikets')->insert(['uuid'=>'regular-ticket','name'=>'REGULAR','kategori'=>'xxi-category']);

        $this->actingAs($user)->post(route('masterbioskop.store'), [
            'type'=>'xxi-category', 'nama_bioskop'=>'Cinema Format', 'kota'=>'Jakarta',
            'ticket_price'=>[
                'weekday_price'=>'40.000,00', 'friday_price'=>'45.000,00', 'weekend_holiday_price'=>'50.000,00',
                'valid_from'=>'2026-09-01',
            ],
        ])->assertRedirect(route('masterbioskop.index'));

        $this->assertDatabaseHas('cinema_ticket_prices', [
            'weekday_price'=>40000, 'friday_price'=>45000, 'weekend_holiday_price'=>50000,
        ]);
    }

    public function test_non_xxi_creation_does_not_accept_hidden_xxi_price_payload(): void
    {
        $user = $this->user();
        DB::table('kategori_bioskops')->insert(['uuid'=>'cgv-category','name'=>'CGV']);
        DB::table('type_tikets')->insert(['uuid'=>'regular-ticket','name'=>'REGULAR','kategori'=>'cgv-category']);

        $this->actingAs($user)->post(route('masterbioskop.store'), [
            'type'=>'cgv-category', 'nama_bioskop'=>'CGV Test', 'kota'=>'Jakarta',
            'ticket_price'=>[
                'weekday_price'=>40000, 'friday_price'=>45000, 'weekend_holiday_price'=>50000, 'valid_from'=>'2026-09-01',
            ],
        ])->assertRedirect(route('masterbioskop.index'));

        $this->assertSame(0, DB::table('cinema_ticket_prices')->count());
    }

    public function test_datatable_exposes_active_regular_price_bands(): void
    {
        $user = $this->user();
        DB::table('kategori_bioskops')->insert(['uuid'=>'xxi-category','name'=>'XXI']);
        DB::table('type_tikets')->insert(['uuid'=>'regular-ticket','name'=>'REGULAR','kategori'=>'xxi-category']);
        DB::table('master_bioskops')->insert(['uuid'=>'cinema-1','type'=>'xxi-category','nama_bioskop'=>'XXI TEST','kota'=>'JAKARTA']);
        DB::table('cinema_ticket_prices')->insert([
            'uuid'=>'price-1','master_bioskop_uuid'=>'cinema-1','type_tiket_uuid'=>'regular-ticket',
            'weekday_price'=>40000,'friday_price'=>45000,'weekend_holiday_price'=>50000,
            'valid_from'=>'2026-01-01','valid_until'=>null,'active'=>1,
        ]);

        $response = $this->actingAs($user)->getJson(route('masterbioskop.index'), ['X-Requested-With'=>'XMLHttpRequest']);

        $response->assertOk();
        $html = $response->json('data.0.ticket_price_summary');
        $this->assertStringContainsString('Rp40.000,00', $html);
        $this->assertStringContainsString('Rp45.000,00', $html);
        $this->assertStringContainsString('Rp50.000,00', $html);
    }

    public function test_create_and_edit_views_define_the_requested_wizard_contract(): void
    {
        $create = file_get_contents(resource_path('views/masterbioskop/create.blade.php'));
        $edit = file_get_contents(resource_path('views/masterbioskop/edit.blade.php'));

        $this->assertStringContainsString('data-price-step', $create);
        $this->assertStringContainsString("toUpperCase()==='XXI'", $create);
        $this->assertStringContainsString("name=\"ticket_price[weekday_price]\"", $create);

        // The step bar must not be allowed to collapse inside the flex column: with
        // height:780px on the panel and no flex-basis, the auto minimum size of this
        // `overflow:auto` row is 0, so it was squeezed to a 1px sliver and the step
        // labels vanished. Regression guard for that layout collapse.
        $this->assertMatchesRegularExpression(
            '/\.wizard-steps\{[^}]*flex:0 0 auto[^}]*\}/',
            $create
        );
        $this->assertMatchesRegularExpression(
            '/\.wizard-steps\{[^}]*min-height:64px[^}]*\}/',
            $create
        );

        // Choosing a category must re-evaluate the step list, otherwise the XXI-only
        // price step never appears after the category select changes.
        $this->assertStringContainsString(
            "const last=steps()-1;showStep(current>last?last:current)",
            $create
        );
        // The stage must take the leftover space and the footer must keep its own
        // height, so the action buttons stay inside the viewport instead of sinking
        // below the fold when the panel is shorter than its fixed 780px default.
        $this->assertStringContainsString('#cinema-wizard-form{flex:1 1 auto;min-height:0;display:flex;flex-direction:column}', $create);
        $this->assertStringContainsString('.wizard-pane-stage{flex:1 1 auto', $create);
        $this->assertStringContainsString('justify-content:space-between;gap:12px;flex:0 0 auto}', $create);
        $this->assertStringContainsString('function fitWizard()', $create);
        $this->assertStringContainsString("$(window).on('resize',fitWizard)", $create);
        $this->assertStringContainsString('fitWizard();$(window).on', $create);
        $this->assertStringNotContainsString('height:780px;display:flex', $create);

        $this->assertStringContainsString('id="cinema-edit-wizard"', $edit);
        $this->assertStringContainsString('data-edit-pane="identity"', $edit);
        $this->assertStringContainsString('data-edit-pane="prices"', $edit);
        $this->assertStringContainsString('data-edit-pane="holidays"', $edit);
        $this->assertStringContainsString('class="edit-pane-stage"', $edit);

        // The edit wizard must size itself to the viewport, not to the price
        // step. Locking the panel height to the price pane clipped the taller
        // identity pane so its "Simpan identitas" button was unreachable on
        // short viewports (verified 1366x640 and 390x844).
        $this->assertStringContainsString('function fitEditWizard()', $edit);
        $this->assertStringContainsString("$(window).on('resize.edit-wizard',fitEditWizard)", $edit);
        $this->assertStringNotContainsString('lockEditWizardToPriceStep', $edit);
        $this->assertStringNotContainsString('const panelHeight=Math.ceil($panel.outerHeight(true))', $edit);
    }

    private function user(): User
    {
        $uuid = 'user-'.uniqid();
        DB::table('users')->insert(['uuid'=>$uuid, 'name'=>'Admin', 'email'=>uniqid().'@example.test', 'password'=>'secret']);
        return User::where('uuid', $uuid)->first();
    }
}
