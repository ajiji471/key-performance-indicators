<?php

namespace Tests\Feature;

use App\Enums\AccountStatus;
use App\Enums\ActivityAction;
use App\Enums\ActivitySubject;
use App\Enums\Role;
use App\Models\ActivityLog;
use App\Models\Agent;
use App\Models\Platform;
use App\Models\User;
use App\Services\KpiSummary;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class TimezoneTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['app.timezone' => 'Asia/Jakarta']);
        date_default_timezone_set('Asia/Jakarta');
    }

    protected function tearDown(): void
    {
        date_default_timezone_set('UTC');

        parent::tearDown();
    }

    private function log(string $occurredAt, string $agentName): void
    {
        $admin = User::factory()->create(['role' => Role::Admin]);

        ActivityLog::create([
            'inputer_id' => $admin->id,
            'inputer_name' => $admin->name,
            'action' => ActivityAction::Updated,
            'subject_type' => ActivitySubject::Agent,
            'subject_id' => 1,
            'agent_id' => 1,
            'agent_name' => $agentName,
            'occurred_at' => Carbon::parse($occurredAt),
        ]);
    }

    public function test_history_membagikan_timezone_dan_occurred_at_iso(): void
    {
        $this->log('2026-11-18 01:00:00', 'X');

        $this->actingAs(User::factory()->create(['role' => Role::Admin]))
            ->get('/history')
            ->assertInertia(fn (Assert $page) => $page
                ->where('timezone', 'Asia/Jakarta')
                ->where('logs.data.0.occurred_at', '2026-11-18T01:00:00+07:00'));
    }

    public function test_dashboard_today_mengikuti_batas_hari_wib(): void
    {
        $this->travelTo(Carbon::parse('2026-11-18 01:00:00'));

        $platform = Platform::factory()->create();

        $today = Agent::factory()->create()->accounts()->create([
            'platform_id' => $platform->id,
            'identifier' => 'a@example.com',
            'status' => AccountStatus::LoggedIn,
        ]);
        $yesterday = Agent::factory()->create()->accounts()->create([
            'platform_id' => $platform->id,
            'identifier' => 'b@example.com',
            'status' => AccountStatus::Registered,
        ]);

        // wall-clock WIB: 00:30 hari ini vs 23:30 kemarin (16:30 UTC kemarin)
        DB::table('agent_accounts')->where('id', $today->id)->update(['updated_at' => '2026-11-18 00:30:00']);
        DB::table('agent_accounts')->where('id', $yesterday->id)->update(['updated_at' => '2026-11-17 23:30:00']);

        $this->actingAs(User::factory()->create(['role' => Role::Admin]))
            ->get('/dashboard')
            ->assertInertia(fn (Assert $page) => $page
                ->where('stats.today.0.logged_in', 1)
                ->where('stats.today.0.registered', 0)
                ->where('stats.all.0.logged_in', 1)
                ->where('stats.all.0.registered', 1));
    }

    public function test_kpi_active_days_satu_hari_wib_meski_beda_tanggal_utc(): void
    {
        $inputer = User::factory()->create(['role' => Role::User]);
        $platform = Platform::factory()->create(['slug' => 'google']);

        // 00:30 WIB = 17 Nov 17:30 UTC; 23:30 WIB = 18 Nov 16:30 UTC → satu hari WIB
        foreach (['2026-11-18 00:30:00', '2026-11-18 23:30:00'] as $at) {
            $agent = Agent::factory()->create();

            DB::table('account_stage_credits')->insert([
                'agent_id' => $agent->id,
                'platform_id' => $platform->id,
                'platform_slug' => 'google',
                'inputer_id' => $inputer->id,
                'stage' => 'registered',
                'achieved_at' => $at,
            ]);
        }

        $summary = app(KpiSummary::class)->summarize(
            'today',
            $inputer->id,
            CarbonImmutable::parse('2026-11-18 12:00:00'),
        );

        $row = $summary['kpi']['inputers'][0];

        $this->assertSame(1, $row['active_days']);
        $this->assertSame(2, $row['agent_registered']['credits']);
        $this->assertSame('2026-11-18T00:00:00+07:00', $summary['period']['from']);
    }

    public function test_history_filter_tanggal_mengikuti_wib(): void
    {
        $this->log('2026-11-18 00:30:00', 'Today');
        $this->log('2026-11-17 23:30:00', 'Yesterday');

        $this->actingAs(User::factory()->create(['role' => Role::Admin]))
            ->get('/history?from=2026-11-18&until=2026-11-18')
            ->assertInertia(fn (Assert $page) => $page
                ->has('logs.data', 1)
                ->where('logs.data.0.agent_name', 'Today'));
    }
}
