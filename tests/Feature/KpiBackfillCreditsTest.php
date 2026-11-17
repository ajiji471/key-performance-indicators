<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\AccountStageCredit;
use App\Models\Agent;
use App\Models\AgentAccount;
use App\Models\Platform;
use App\Models\User;
use App\Services\KpiSummary;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class KpiBackfillCreditsTest extends TestCase
{
    use RefreshDatabase;

    public function test_registered_menghasilkan_satu_kredit_atribusi_creator_dan_created_at(): void
    {
        $creator = User::factory()->create(['role' => Role::User]);
        $agent = $this->agent($creator, '2026-10-05 00:00:00');
        $platform = Platform::factory()->create(['slug' => 'google']);
        $this->account($agent, $platform, 'registered');

        $this->artisan('kpi:backfill-credits')->assertExitCode(0);

        $this->assertDatabaseCount('account_stage_credits', 1);
        $this->assertDatabaseHas('account_stage_credits', [
            'agent_id' => $agent->id,
            'platform_id' => $platform->id,
            'platform_slug' => 'google',
            'stage' => 'registered',
            'inputer_id' => $creator->id,
            'achieved_at' => '2026-10-05 00:00:00',
        ]);
    }

    public function test_logged_in_menghasilkan_kredit_registered_dan_login(): void
    {
        $creator = User::factory()->create(['role' => Role::User]);
        $agent = $this->agent($creator, '2026-10-05 00:00:00');
        $platform = Platform::factory()->create(['slug' => 'google']);
        $this->account($agent, $platform, 'logged_in');

        $this->artisan('kpi:backfill-credits')->assertExitCode(0);

        $this->assertDatabaseCount('account_stage_credits', 2);
        foreach (['registered', 'logged_in'] as $stage) {
            $this->assertDatabaseHas('account_stage_credits', [
                'agent_id' => $agent->id,
                'platform_id' => $platform->id,
                'stage' => $stage,
                'inputer_id' => $creator->id,
                'achieved_at' => '2026-10-05 00:00:00',
            ]);
        }
    }

    public function test_trouble_dan_recovered_tidak_memberi_kredit(): void
    {
        $creator = User::factory()->create(['role' => Role::User]);
        $agent = $this->agent($creator, '2026-10-05 00:00:00');
        $this->account($agent, Platform::factory()->create(['slug' => 'google']), 'trouble');
        $this->account($agent, Platform::factory()->create(['slug' => 'facebook']), 'recovered');

        $this->artisan('kpi:backfill-credits')->assertExitCode(0);

        $this->assertDatabaseEmpty('account_stage_credits');
    }

    public function test_atribusi_terpisah_per_creator_dan_tanggal_created_at(): void
    {
        $a = User::factory()->create(['role' => Role::User]);
        $b = User::factory()->create(['role' => Role::User]);
        $google = Platform::factory()->create(['slug' => 'google']);
        $agentA = $this->agent($a, '2026-10-01 00:00:00');
        $agentB = $this->agent($b, '2026-10-02 00:00:00');
        $this->account($agentA, $google, 'registered');
        $this->account($agentB, $google, 'logged_in');

        $this->artisan('kpi:backfill-credits')->assertExitCode(0);

        $this->assertDatabaseHas('account_stage_credits', [
            'agent_id' => $agentA->id, 'stage' => 'registered',
            'inputer_id' => $a->id, 'achieved_at' => '2026-10-01 00:00:00',
        ]);
        foreach (['registered', 'logged_in'] as $stage) {
            $this->assertDatabaseHas('account_stage_credits', [
                'agent_id' => $agentB->id, 'stage' => $stage,
                'inputer_id' => $b->id, 'achieved_at' => '2026-10-02 00:00:00',
            ]);
        }
        $this->assertSame(0, AccountStageCredit::where('inputer_id', $a->id)->where('stage', 'logged_in')->count());
    }

    public function test_google_logged_in_terhitung_sebagai_email_di_kpi(): void
    {
        $creator = User::factory()->create(['role' => Role::User]);
        $agent = $this->agent($creator, '2026-10-05 00:00:00');
        $this->account($agent, Platform::factory()->create(['slug' => 'google']), 'logged_in');

        $this->artisan('kpi:backfill-credits')->assertExitCode(0);

        $row = $this->rows(app(KpiSummary::class)->summarize('all'))[$creator->id];
        $this->assertSame(1, $row['email_registered']['total']);
        $this->assertSame(1, $row['email_login']['total']);
        $this->assertSame(1, $row['agent_registered']['credits']);
        $this->assertSame(1, $row['agent_login']['credits']);
    }

    public function test_run_kedua_idempotent_tidak_menggandakan_atau_mengubah(): void
    {
        $creator = User::factory()->create(['role' => Role::User]);
        $agent = $this->agent($creator, '2026-10-05 00:00:00');
        $this->account($agent, Platform::factory()->create(['slug' => 'google']), 'logged_in');

        $this->artisan('kpi:backfill-credits')->assertExitCode(0);
        $original = DB::table('account_stage_credits')->orderBy('id')->get()->toJson();
        $this->assertDatabaseCount('account_stage_credits', 2);

        $this->travel(3)->days();
        $this->artisan('kpi:backfill-credits')->assertExitCode(0);

        $this->assertSame($original, DB::table('account_stage_credits')->orderBy('id')->get()->toJson());
        $this->assertDatabaseCount('account_stage_credits', 2);
    }

    public function test_kredit_yatim_tidak_terhapus_atau_terubah(): void
    {
        $creator = User::factory()->create(['role' => Role::User]);
        AccountStageCredit::create([
            'agent_id' => 999999, 'platform_id' => 1, 'platform_slug' => 'google',
            'stage' => 'registered', 'inputer_id' => $creator->id, 'achieved_at' => '2020-01-01 00:00:00',
        ]);
        $orphan = DB::table('account_stage_credits')->where('agent_id', 999999)->sole();
        $agent = $this->agent($creator, '2026-10-05 00:00:00');
        $this->account($agent, Platform::factory()->create(['slug' => 'facebook']), 'registered');

        $this->artisan('kpi:backfill-credits')->assertExitCode(0);

        $this->assertEquals($orphan, DB::table('account_stage_credits')->where('agent_id', 999999)->sole());
        $this->assertDatabaseCount('account_stage_credits', 2);
    }

    public function test_kpi_summary_mencerminkan_backfill_split_per_creator(): void
    {
        $a = User::factory()->create(['role' => Role::User, 'name' => 'A']);
        $b = User::factory()->create(['role' => Role::User, 'name' => 'B']);
        $agentA = $this->agent($a, '2026-10-01 00:00:00');
        $agentB = $this->agent($b, '2026-10-02 00:00:00');
        $this->account($agentA, Platform::factory()->create(['slug' => 'google']), 'registered');
        $this->account($agentB, Platform::factory()->create(['slug' => 'facebook']), 'logged_in');

        $this->artisan('kpi:backfill-credits')->assertExitCode(0);

        $report = app(KpiSummary::class)->summarize('all');
        $this->assertTrue($report['kpi']['has_activity']);
        $rows = $this->rows($report);
        $this->assertSame(1, $rows[$a->id]['agent_registered']['credits']);
        $this->assertSame(0, $rows[$a->id]['agent_login']['credits']);
        $this->assertSame(1, $rows[$b->id]['agent_registered']['credits']);
        $this->assertSame(1, $rows[$b->id]['agent_login']['credits']);
        $this->assertEqualsWithDelta(1 / 7, $rows[$a->id]['agent_registered']['total'], 1e-12);
    }

    public function test_platform_non_kpi_tersimpan_tapi_tidak_masuk_kpi(): void
    {
        $creator = User::factory()->create(['role' => Role::User]);
        $agent = $this->agent($creator, '2026-10-05 00:00:00');
        $this->account($agent, Platform::factory()->create(['slug' => 'other']), 'registered');

        $this->artisan('kpi:backfill-credits')->assertExitCode(0);

        $this->assertDatabaseCount('account_stage_credits', 1);
        $this->assertFalse(app(KpiSummary::class)->summarize('all')['kpi']['has_activity']);
    }

    public function test_tujuh_platform_registered_menghasilkan_tujuh_kredit_satu_ekuivalen_agent(): void
    {
        $creator = User::factory()->create(['role' => Role::User]);
        $agent = $this->agent($creator, '2026-10-05 00:00:00');
        foreach (['google', 'facebook', 'youtube', 'instagram', 'x', 'tiktok', 'pinterest'] as $slug) {
            $this->account($agent, Platform::factory()->create(['slug' => $slug]), 'registered');
        }

        $this->artisan('kpi:backfill-credits')->assertExitCode(0);

        $this->assertDatabaseCount('account_stage_credits', 7);
        $row = $this->rows(app(KpiSummary::class)->summarize('all'))[$creator->id];
        $this->assertSame(7, $row['agent_registered']['credits']);
        $this->assertEqualsWithDelta(1.0, $row['agent_registered']['total'], 1e-12);
    }

    public function test_tanpa_agent_command_sukses_nol_kredit(): void
    {
        $this->artisan('kpi:backfill-credits')->assertExitCode(0);
        $this->assertDatabaseEmpty('account_stage_credits');
    }

    private function agent(User $creator, string $createdAt): Agent
    {
        $agent = Agent::factory()->create(['created_by' => $creator->id]);
        $agent->created_at = Carbon::parse($createdAt);
        $agent->save();

        return $agent;
    }

    private function account(Agent $agent, Platform $platform, string $status): AgentAccount
    {
        return $agent->accounts()->create([
            'platform_id' => $platform->id,
            'identifier' => 'backfill@example.com',
            'status' => $status,
        ]);
    }

    private function rows(array $report): array
    {
        return collect($report['kpi']['inputers'])->keyBy('inputer_id')->all();
    }
}
