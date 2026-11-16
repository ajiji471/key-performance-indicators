<?php

namespace Tests\Feature;

use App\Models\AccountStageCredit;
use App\Models\Agent;
use App\Models\Platform;
use App\Models\User;
use App\Services\KpiSummary;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class KpiSummaryTest extends TestCase
{
    use RefreshDatabase;

    public function test_registered_dan_login_milik_pelaksana_masing_masing(): void
    {
        $a = User::factory()->create(['name' => 'A']);
        $b = User::factory()->create(['name' => 'B']);
        $this->credit($a->id, 'google', 'registered', '2026-11-14 09:00:00', 1);
        $this->credit($b->id, 'google', 'logged_in', '2026-11-15 10:00:00', 1);

        $report = $this->report('all');
        $rows = $this->rows($report);
        $this->assertTrue($report['kpi']['has_activity']);
        $this->assertSame(1, $rows[$a->id]['email_registered']['total']);
        $this->assertSame(0, $rows[$a->id]['email_login']['total']);
        $this->assertEqualsWithDelta(1 / 7, $rows[$a->id]['agent_registered']['total'], 1e-12);
        $this->assertSame(1, $rows[$b->id]['email_login']['total']);
        $this->assertSame(0, $rows[$b->id]['agent_registered']['credits']);
        $this->assertEqualsWithDelta(1 / 7, $rows[$b->id]['agent_login']['average'], 1e-12);
        $this->assertArrayNotHasKey('category', $rows[$b->id]['agent_login']);
        $this->assertArrayNotHasKey('category', $rows[$b->id]['email_login']);
    }

    public function test_tujuh_platform_dibagi_menurut_inputer_bukan_pemilik_agent(): void
    {
        $a = User::factory()->create();
        $b = User::factory()->create();
        $slugs = ['google', 'facebook', 'youtube', 'instagram', 'x', 'tiktok', 'pinterest'];
        foreach ($slugs as $index => $slug) {
            $this->credit($index < 4 ? $a->id : $b->id, $slug, 'registered', '2026-11-14 09:00:00', 1);
        }
        $rows = $this->rows($this->report('all'));

        $this->assertEqualsWithDelta(4 / 7, $rows[$a->id]['agent_registered']['total'], 1e-12);
        $this->assertEqualsWithDelta(3 / 7, $rows[$b->id]['agent_registered']['total'], 1e-12);
        $this->assertSame(1, $rows[$a->id]['email_registered']['total']);
        $this->assertSame(0, $rows[$b->id]['email_registered']['total']);
    }

    public function test_hari_aktif_gabungan_tahap_akhir_pekan_dan_total_boleh_melebihi_satu(): void
    {
        $user = User::factory()->create();
        $slugs = ['google', 'facebook', 'youtube', 'instagram', 'x', 'tiktok', 'pinterest'];
        foreach ([1, 2, 3] as $agentId) {
            foreach ($slugs as $slug) {
                $this->credit($user->id, $slug, 'registered', '2026-11-14 09:00:00', $agentId);
            }
        }
        $this->credit($user->id, 'google', 'logged_in', '2026-11-14 20:00:00', 1);
        $this->credit($user->id, 'facebook', 'logged_in', '2026-11-15 09:00:00', 1);
        $row = $this->rows($this->report('all'))[$user->id];

        $this->assertSame(2, $row['active_days']);
        $this->assertEquals(3, $row['agent_registered']['total']);
        $this->assertSame(1.5, $row['agent_registered']['average']);
        $this->assertSame('excellent', $row['agent_registered']['category']);
        $this->assertSame(1.5, $row['email_registered']['average']);
        $this->assertSame('below_target', $row['email_registered']['category']);
        $this->assertEqualsWithDelta(1 / 7, $row['agent_login']['average'], 1e-12);
        $this->assertSame(0.5, $row['email_login']['average']);
    }

    public function test_user_tanpa_kredit_dan_akun_legacy_tidak_mendapat_poin(): void
    {
        $user = User::factory()->create();
        Agent::factory()->create()->accounts()->create([
            'platform_id' => Platform::factory()->create(['slug' => 'google'])->id,
            'identifier' => 'legacy@example.com',
            'status' => 'registered',
        ]);
        $report = $this->report('all');
        $rows = $this->rows($report);
        $row = $rows[$user->id];

        $this->assertFalse($report['kpi']['has_activity']);
        $this->assertSame(0, $row['active_days']);
        foreach (['email_registered', 'email_login', 'agent_registered', 'agent_login'] as $metric) {
            $this->assertEquals(0, $row[$metric]['total']);
            $this->assertEquals(0, $row[$metric]['average']);
        }
        $this->assertSame('below_baseline', $row['agent_registered']['category']);
        $this->assertSame('below_target', $row['email_registered']['category']);
    }

    #[DataProvider('agentThresholds')]
    public function test_kategori_agent_memakai_nilai_asli(int $credits, int $days, string $category): void
    {
        $user = User::factory()->create();
        $this->batchCredits($user->id, $credits, $days, 'facebook');
        $row = $this->rows($this->report('all'))[$user->id];

        $this->assertSame($days, $row['active_days']);
        $this->assertSame($credits, $row['agent_registered']['credits']);
        $this->assertSame($category, $row['agent_registered']['category']);
        $this->assertEqualsWithDelta($credits / ($days * 7), $row['agent_registered']['average'], 1e-12);
    }

    public static function agentThresholds(): array
    {
        return [[6, 1, 'below_baseline'], [7, 1, 'baseline'], [34, 4, 'baseline'],
            [35, 4, 'better'], [93, 10, 'better'], [931, 100, 'excellent']];
    }

    #[DataProvider('emailThresholds')]
    public function test_target_email_hanya_registered(int $credits, int $days, string $category): void
    {
        $user = User::factory()->create();
        $this->batchCredits($user->id, $credits, $days, 'google');
        $row = $this->rows($this->report('all'))[$user->id];

        $this->assertSame($credits, $row['email_registered']['total']);
        $this->assertSame($category, $row['email_registered']['category']);
        $this->assertEqualsWithDelta($credits / $days, $row['email_registered']['average'], 1e-12);
        $this->assertArrayNotHasKey('category', $row['email_login']);
    }

    public static function emailThresholds(): array
    {
        return [[5, 2, 'below_target'], [3, 1, 'within_target'], [4, 1, 'within_target'], [5, 1, 'above_target']];
    }

    public function test_filter_memakai_tanggal_pencapaian_dan_batas_eksklusif(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-11-16 12:00:00', 'UTC'));
        $user = User::factory()->create();
        foreach (['2026-10-31 23:59:59', '2026-11-01 00:00:00', '2026-11-15 23:59:59',
            '2026-11-16 00:00:00', '2026-11-16 23:59:59', '2026-11-17 00:00:00',
            '2026-11-30 23:59:59', '2026-12-01 00:00:00'] as $date) {
            $this->credit($user->id, 'google', 'registered', $date);
        }
        foreach (['today' => [2, 1], 'month' => [6, 5], 'all' => [8, 7]] as $period => [$total, $days]) {
            $report = $this->report($period);
            $row = $this->rows($report)[$user->id];
            $this->assertSame($total, $row['email_registered']['total']);
            $this->assertSame($days, $row['active_days']);
        }
        $month = $this->report();
        $this->assertSame('2026-11-01T00:00:00+00:00', $month['period']['from']);
        $this->assertSame('2026-12-01T00:00:00+00:00', $month['period']['until']);
        $this->assertNull($this->report('all')['period']['from']);
        $this->assertNull($this->report('all')['period']['until']);
    }

    #[DataProvider('calendarMonths')]
    public function test_batas_bulan_kabisat_dan_tahun(string $now, string $last, string $next): void
    {
        $this->travelTo(CarbonImmutable::parse($now, 'UTC'));
        $user = User::factory()->create();
        $this->credit($user->id, 'google', 'registered', $last);
        $this->credit($user->id, 'google', 'registered', $next);
        $this->assertSame(1, $this->rows($this->report('month'))[$user->id]['email_registered']['total']);
    }

    public static function calendarMonths(): array
    {
        return [
            ['2028-02-15 12:00:00', '2028-02-29 23:59:59', '2028-03-01 00:00:00'],
            ['2026-12-15 12:00:00', '2026-12-31 23:59:59', '2027-01-01 00:00:00'],
        ];
    }

    public function test_today_menggunakan_timezone_aplikasi(): void
    {
        config(['app.timezone' => 'Asia/Jakarta']);
        $this->travelTo(CarbonImmutable::parse('2026-11-15 18:00:00', 'UTC'));
        $user = User::factory()->create();
        $this->credit($user->id, 'google', 'registered', '2026-11-15 23:59:59');
        $this->credit($user->id, 'google', 'registered', '2026-11-16 00:00:00');
        $this->credit($user->id, 'google', 'registered', '2026-11-17 00:00:00');
        $report = $this->report('today');

        $this->assertSame('Asia/Jakarta', $report['period']['timezone']);
        $this->assertSame('2026-11-16T00:00:00+07:00', $report['period']['from']);
        $this->assertSame('2026-11-17T00:00:00+07:00', $report['period']['until']);
        $this->assertSame(1, $this->rows($report)[$user->id]['email_registered']['total']);
    }

    public function test_histori_user_terhapus_tetap_terpisah_dan_tidak_memerlukan_sumber_hidup(): void
    {
        $viewer = User::factory()->create(['name' => 'Viewer']);
        $a = User::factory()->create();
        $b = User::factory()->create();
        $this->credit($a->id, 'google', 'registered', '2026-11-14 09:00:00');
        $this->credit($b->id, 'facebook', 'registered', '2026-11-14 09:00:00');
        $a->delete();
        $b->delete();
        $rows = $this->rows($this->report('all'));

        $this->assertCount(3, $rows);
        foreach ([$a->id, $b->id] as $id) {
            $this->assertTrue($rows[$id]['is_deleted']);
            $this->assertSame('Inputer tidak tersedia', $rows[$id]['name']);
            $this->assertSame(1, $rows[$id]['agent_registered']['credits']);
        }
        $this->assertSame(1, $rows[$a->id]['email_registered']['total']);
        $this->assertSame(0, $rows[$b->id]['email_registered']['total']);
        $this->assertFalse($rows[$viewer->id]['is_deleted']);
        $this->assertDatabaseEmpty('agents');
        $this->assertDatabaseEmpty('platforms');
        $this->assertDatabaseEmpty('agent_accounts');
    }

    public function test_filter_kosong_tidak_membawa_hari_atau_user_historis_dari_periode_lain(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-11-16 12:00:00', 'UTC'));
        $viewer = User::factory()->create();
        $deleted = User::factory()->create();
        $this->credit($viewer->id, 'google', 'registered', '2026-10-31 09:00:00');
        $this->credit($deleted->id, 'google', 'registered', '2026-10-31 09:00:00');
        $deleted->delete();
        $report = $this->report('today');

        $this->assertFalse($report['kpi']['has_activity']);
        $this->assertCount(1, $report['kpi']['inputers']);
        $this->assertSame(0, $report['kpi']['inputers'][0]['active_days']);
    }

    public function test_platform_di_luar_tujuh_tidak_menambah_kpi(): void
    {
        $user = User::factory()->create();
        $this->credit($user->id, 'other', 'registered', '2026-11-14 09:00:00');
        $report = $this->report('all');
        $this->assertFalse($report['kpi']['has_activity']);
        $this->assertSame(0, $report['kpi']['inputers'][0]['active_days']);
    }

    public function test_props_kpi_hanya_data_ringkasan_dan_urut_nama(): void
    {
        $z = User::factory()->create(['name' => 'Z', 'email' => 'private@example.com']);
        $a = User::factory()->create(['name' => 'A', 'is_active' => false]);
        $this->credit($z->id, 'google', 'registered', '2026-11-14 09:00:00');
        $report = $this->report('all');
        $rows = $report['kpi']['inputers'];
        $this->assertSame([$a->id, $z->id], array_column($rows, 'inputer_id'));
        $this->assertEqualsCanonicalizing(['inputer_id', 'name', 'is_deleted', 'active_days',
            'email_registered', 'email_login', 'agent_registered', 'agent_login'], array_keys($rows[1]));
        $this->assertStringNotContainsString('private@example.com', json_encode($report['kpi']));
        $this->assertStringNotContainsString('password', json_encode($report['kpi']));
        $this->assertDatabaseCount('account_stage_credits', 1);
    }

    #[DataProvider('calendarWeeks')]
    public function test_week_dimulai_senin_dan_berakhir_sebelum_senin_berikutnya(
        string $now, string $before, string $from, string $last, string $until,
    ): void {
        $this->travelTo(CarbonImmutable::parse($now, 'UTC'));
        $user = User::factory()->create();
        foreach ([$before, $from, $last, $until] as $date) {
            $this->credit($user->id, 'google', 'registered', $date);
        }

        $report = $this->report('week', $user->id);
        $row = $this->rows($report)[$user->id];
        $this->assertSame(2, $row['email_registered']['total']);
        $this->assertSame(2, $row['active_days']);
        $this->assertSame(str_replace(' ', 'T', $from).'+00:00', $report['period']['from']);
        $this->assertSame(str_replace(' ', 'T', $until).'+00:00', $report['period']['until']);
    }

    public static function calendarWeeks(): array
    {
        return [
            'november' => ['2026-11-18 12:00:00', '2026-11-15 23:59:59', '2026-11-16 00:00:00', '2026-11-22 23:59:59', '2026-11-23 00:00:00'],
            'year boundary' => ['2027-01-01 12:00:00', '2026-12-27 23:59:59', '2026-12-28 00:00:00', '2027-01-03 23:59:59', '2027-01-04 00:00:00'],
        ];
    }

    #[DataProvider('localPeriods')]
    public function test_week_dan_month_menggunakan_boundary_lokal(
        string $period, string $now, string $before, string $from, string $last, string $until,
    ): void {
        config(['app.timezone' => 'Asia/Jakarta']);
        $this->travelTo(CarbonImmutable::parse($now, 'UTC'));
        $user = User::factory()->create();
        foreach ([$before, $from, $last, $until] as $date) {
            $this->credit($user->id, 'google', 'registered', $date);
        }

        $report = $this->report($period, $user->id);
        $this->assertSame('Asia/Jakarta', $report['period']['timezone']);
        $this->assertSame(str_replace(' ', 'T', $from).'+07:00', $report['period']['from']);
        $this->assertSame(str_replace(' ', 'T', $until).'+07:00', $report['period']['until']);
        $this->assertSame(2, $this->rows($report)[$user->id]['email_registered']['total']);
        $this->assertSame(2, $this->rows($report)[$user->id]['active_days']);
    }

    public static function localPeriods(): array
    {
        return [
            'week' => ['week', '2026-11-15 18:00:00', '2026-11-15 23:59:59', '2026-11-16 00:00:00', '2026-11-22 23:59:59', '2026-11-23 00:00:00'],
            'month' => ['month', '2026-10-31 18:00:00', '2026-10-31 23:59:59', '2026-11-01 00:00:00', '2026-11-30 23:59:59', '2026-12-01 00:00:00'],
        ];
    }

    public function test_waktu_referensi_dikonversi_ke_timezone_aplikasi(): void
    {
        config(['app.timezone' => 'Asia/Jakarta']);
        $this->travelTo(CarbonImmutable::parse('2026-12-15 12:00:00', 'UTC'));
        $user = User::factory()->create();
        $this->credit($user->id, 'google', 'registered', '2026-11-01 00:00:00');

        $report = app(KpiSummary::class)->summarize('month', $user->id, CarbonImmutable::parse('2026-10-31 18:00:00', 'UTC'));

        $this->assertSame('2026-11-01T00:00:00+07:00', $report['period']['from']);
        $this->assertSame('2026-12-01T00:00:00+07:00', $report['period']['until']);
        $this->assertSame(1, $this->rows($report)[$user->id]['email_registered']['total']);
    }

    #[DataProvider('periods')]
    public function test_scope_membatasi_query_ledger_dan_user_sebelum_agregasi(string $period): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-11-18 12:00:00', 'UTC'));
        $other = User::factory()->create();
        $viewer = User::factory()->create();
        $deleted = User::factory()->create();
        $this->credit($other->id, 'google', 'registered', '2026-11-18 09:00:00');
        $this->credit($deleted->id, 'google', 'registered', '2026-11-18 09:00:00');
        $deleted->delete();
        DB::enableQueryLog();
        DB::flushQueryLog();
        try {
            $report = $this->report($period, $viewer->id);
            $queries = DB::getQueryLog();
        } finally {
            DB::disableQueryLog();
            DB::flushQueryLog();
        }

        $this->assertSame([$viewer->id], array_keys($this->rows($report)));
        $this->assertFalse($report['kpi']['has_activity']);
        $this->assertSame(0, $this->rows($report)[$viewer->id]['email_registered']['total']);
        $this->assertCount(2, $queries);
        $this->assertMatchesRegularExpression('/where .*"inputer_id" = \?/', $queries[0]['query']);
        $this->assertContains($viewer->id, $queries[0]['bindings']);
        $this->assertMatchesRegularExpression('/where "users"\."id" = \?/', $queries[1]['query']);
        $this->assertSame([$viewer->id], $queries[1]['bindings']);

        $this->credit($viewer->id, 'google', 'logged_in', '2026-11-18 10:00:00');
        $own = $this->report($period, $viewer->id);
        $this->assertTrue($own['kpi']['has_activity']);
        $this->assertSame([$viewer->id], array_keys($this->rows($own)));
        $this->assertSame(0, $this->rows($own)[$viewer->id]['email_registered']['total']);
        $this->assertSame(1, $this->rows($own)[$viewer->id]['email_login']['total']);
    }

    public static function periods(): array
    {
        return [['today'], ['week'], ['month'], ['all']];
    }

    public function test_card_menggabungkan_empat_periode_dan_memisahkan_login(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-11-18 12:00:00', 'UTC'));
        $user = User::factory()->create();
        foreach (['2026-10-31 09:00:00', '2026-11-01 09:00:00', '2026-11-16 09:00:00', '2026-11-18 09:00:00'] as $date) {
            $this->credit($user->id, 'google', 'registered', $date);
        }
        $this->credit($user->id, 'google', 'logged_in', '2026-11-18 10:00:00');
        $kpi = app(KpiSummary::class)->profileCards($user->id)['kpi'];

        $this->assertSame('self', $kpi['scope']);
        $this->assertTrue($kpi['has_activity']);
        $this->assertSame(['today', 'week', 'month', 'all'], array_keys($kpi['periods']));
        foreach (['today' => 1, 'week' => 2, 'month' => 3, 'all' => 4] as $period => $total) {
            $row = $kpi['inputers'][0]['periods'][$period];
            $this->assertSame($total, $row['email_registered']);
            $this->assertSame($total, $row['registered_credits']);
            $this->assertEqualsWithDelta($total / 7, $row['agent_registered'], 1e-12);
            $this->assertSame(1, $row['email_login']);
            $this->assertSame(1, $row['login_credits']);
            $this->assertEqualsWithDelta(1 / 7, $row['agent_login'], 1e-12);
        }
    }

    public function test_card_menangkap_satu_waktu_meski_melewati_tengah_malam(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-11-30 23:59:59', 'UTC'));
        $user = User::factory()->create();
        $this->credit($user->id, 'google', 'registered', '2026-11-30 09:00:00');
        $this->credit($user->id, 'google', 'registered', '2026-12-01 09:00:00');
        DB::listen(function () {
            CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-12-01 00:00:01', 'UTC'));
        });

        $kpi = app(KpiSummary::class)->profileCards($user->id)['kpi'];

        $this->assertSame('2026-11-30T00:00:00+00:00', $kpi['periods']['today']['from']);
        $this->assertSame('2026-11-30T00:00:00+00:00', $kpi['periods']['week']['from']);
        $this->assertSame('2026-11-01T00:00:00+00:00', $kpi['periods']['month']['from']);
        $this->assertSame('2026-12-01T00:00:00+00:00', $kpi['periods']['month']['until']);
        $this->assertSame(1, $kpi['inputers'][0]['periods']['month']['email_registered']);
        $this->assertSame(2, $kpi['inputers'][0]['periods']['all']['email_registered']);
    }

    public function test_card_histori_memakai_identitas_all_dan_nol_untuk_periode_lain(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-11-18 12:00:00', 'UTC'));
        $deleted = User::factory()->create();
        $this->credit($deleted->id, 'google', 'registered', '2026-10-31 09:00:00');
        $deleted->delete();
        $kpi = app(KpiSummary::class)->profileCards(null)['kpi'];

        $this->assertTrue($kpi['has_activity']);
        $this->assertCount(1, $kpi['inputers']);
        $row = $kpi['inputers'][0];
        $this->assertSame($deleted->id, $row['inputer_id']);
        $this->assertSame('Inputer tidak tersedia', $row['name']);
        $this->assertTrue($row['is_deleted']);
        $this->assertSame(1, $row['periods']['all']['email_registered']);
        foreach (['today', 'week', 'month'] as $period) {
            $this->assertSame([
                'email_registered' => 0, 'email_login' => 0, 'agent_registered' => 0,
                'agent_login' => 0, 'registered_credits' => 0, 'login_credits' => 0,
            ], $row['periods'][$period]);
        }
    }

    public function test_card_tanpa_profil_dan_kredit(): void
    {
        $kpi = app(KpiSummary::class)->profileCards(null)['kpi'];

        $this->assertSame('all', $kpi['scope']);
        $this->assertFalse($kpi['has_activity']);
        $this->assertSame([], $kpi['inputers']);
        $this->assertSame(['today', 'week', 'month', 'all'], array_keys($kpi['periods']));
    }

    public function test_query_card_tetap_delapan_saat_profil_bertambah(): void
    {
        $user = User::factory()->create();
        $summary = app(KpiSummary::class);
        foreach ([0, 10] as $additional) {
            if ($additional > 0) {
                foreach (User::factory()->count($additional)->create() as $other) {
                    $this->credit($other->id, 'google', 'registered', '2026-11-18 09:00:00');
                }
            }
            foreach ([null, $user->id] as $inputerId) {
                DB::enableQueryLog();
                DB::flushQueryLog();
                try {
                    $kpi = $summary->profileCards($inputerId)['kpi'];
                    $queries = DB::getQueryLog();
                } finally {
                    DB::disableQueryLog();
                    DB::flushQueryLog();
                }
                $this->assertCount(8, $queries);
                $this->assertCount($inputerId === null ? 1 + $additional : 1, $kpi['inputers']);
            }
        }
    }

    private function report(string $period = 'month', ?int $inputerId = null): array
    {
        return app(KpiSummary::class)->summarize($period, $inputerId);
    }

    private function rows(array $report): array
    {
        return collect($report['kpi']['inputers'])->keyBy('inputer_id')->all();
    }

    private function credit(int $inputerId, string $slug, string $stage, string $date, ?int $agentId = null): void
    {
        $slugs = ['google', 'facebook', 'youtube', 'instagram', 'x', 'tiktok', 'pinterest', 'other'];
        AccountStageCredit::create([
            'agent_id' => $agentId ?? (AccountStageCredit::max('agent_id') + 1),
            'platform_id' => array_search($slug, $slugs, true) + 1,
            'platform_slug' => $slug,
            'stage' => $stage,
            'inputer_id' => $inputerId,
            'achieved_at' => $date,
        ]);
    }

    private function batchCredits(int $inputerId, int $credits, int $days, string $slug): void
    {
        $rows = [];
        for ($i = 0; $i < $credits; $i++) {
            $rows[] = [
                'agent_id' => $i + 1,
                'platform_id' => 1,
                'platform_slug' => $slug,
                'stage' => 'registered',
                'inputer_id' => $inputerId,
                'achieved_at' => CarbonImmutable::parse('2026-01-01 09:00:00')->addDays($i % $days)->toDateTimeString(),
            ];
        }
        foreach (array_chunk($rows, 100) as $chunk) {
            DB::table('account_stage_credits')->insert($chunk);
        }
    }
}
