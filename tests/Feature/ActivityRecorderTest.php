<?php

namespace Tests\Feature;

use App\Enums\AccountStatus;
use App\Enums\Role;
use App\Models\ActivityLog;
use App\Models\Agent;
use App\Models\AgentAccount;
use App\Models\Platform;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Tests\TestCase;

class ActivityRecorderTest extends TestCase
{
    use RefreshDatabase;

    private function inputer(string $name = 'Inputer QA'): User
    {
        return User::factory()->create(['role' => Role::User, 'name' => $name]);
    }

    public function test_store_agent_mencatat_agent_dan_account_created(): void
    {
        $this->travelTo(Carbon::parse('2026-11-17 00:29:00'));
        $inputer = $this->inputer();
        $platform = Platform::factory()->create(['slug' => 'google', 'name' => 'Google']);

        $this->actingAs($inputer)->post('/agents', [
            'name' => 'Andrew',
            'email' => 'andrew@example.com',
            'password' => 'secret-agent',
            'accounts' => [
                ['platform_id' => $platform->id, 'identifier' => 'andrew@gmail.com', 'password' => 'secret-account'],
            ],
        ])->assertRedirect(route('dashboard'));

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'created',
            'subject_type' => 'agent',
            'agent_name' => 'Andrew',
            'inputer_id' => $inputer->id,
            'inputer_name' => 'Inputer QA',
            'occurred_at' => '2026-11-17 00:29:00',
        ]);
        $this->assertDatabaseHas('activity_logs', [
            'action' => 'created',
            'subject_type' => 'account',
            'agent_name' => 'Andrew',
            'platform_slug' => 'google',
            'platform_name' => 'Google',
            'identifier' => 'andrew@gmail.com',
            'inputer_id' => $inputer->id,
        ]);
        $this->assertDatabaseCount('activity_logs', 2);
        $this->assertStringNotContainsString('secret', DB::table('activity_logs')->get()->toJson());
    }

    public function test_update_agent_berubah_mencatat_updated(): void
    {
        $inputer = $this->inputer();
        $agent = Agent::factory()->create(['name' => 'Old Name']);

        $this->actingAs($inputer)->patch("/agents/{$agent->id}", [
            'name' => 'New Name',
            'email' => $agent->email,
        ])->assertRedirect(route('dashboard'));

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'updated',
            'subject_type' => 'agent',
            'subject_id' => $agent->id,
            'agent_name' => 'New Name',
            'inputer_id' => $inputer->id,
        ]);
        $this->assertDatabaseCount('activity_logs', 1);
    }

    public function test_update_agent_tanpa_perubahan_tidak_mencatat(): void
    {
        $inputer = $this->inputer();
        $agent = Agent::factory()->create(['name' => 'Same Name']);

        $this->actingAs($inputer)->patch("/agents/{$agent->id}", [
            'name' => 'Same Name',
            'email' => $agent->email,
        ])->assertRedirect(route('dashboard'));

        $this->assertDatabaseCount('activity_logs', 0);
    }

    public function test_update_agent_inline_tambah_dan_edit_account(): void
    {
        $inputer = $this->inputer();
        $agent = Agent::factory()->create();
        $platforms = Platform::factory()->count(2)->create();
        $agent->accounts()->create([
            'platform_id' => $platforms[0]->id,
            'identifier' => 'old@example.com',
            'status' => AccountStatus::Registered,
        ]);

        $this->actingAs($inputer)->patch("/agents/{$agent->id}", [
            'name' => $agent->name,
            'email' => $agent->email,
            'accounts' => [
                ['platform_id' => $platforms[0]->id, 'identifier' => 'new@example.com', 'password' => '', 'status' => ''],
                ['platform_id' => $platforms[1]->id, 'identifier' => 'added@example.com', 'password' => '', 'status' => ''],
            ],
        ])->assertRedirect(route('dashboard'));

        $this->assertDatabaseHas('activity_logs', ['action' => 'updated', 'subject_type' => 'account', 'identifier' => 'new@example.com']);
        $this->assertDatabaseHas('activity_logs', ['action' => 'created', 'subject_type' => 'account', 'identifier' => 'added@example.com']);
        $this->assertDatabaseMissing('activity_logs', ['subject_type' => 'agent']);
        $this->assertDatabaseCount('activity_logs', 2);
    }

    public function test_update_agent_inline_hapus_account(): void
    {
        $inputer = $this->inputer();
        $agent = Agent::factory()->create();
        $platform = Platform::factory()->create();
        $account = $agent->accounts()->create([
            'platform_id' => $platform->id,
            'identifier' => 'del@example.com',
            'status' => AccountStatus::Registered,
        ]);

        $this->actingAs($inputer)->patch("/agents/{$agent->id}", [
            'name' => $agent->name,
            'email' => $agent->email,
            'accounts' => [
                ['platform_id' => $platform->id, 'identifier' => '', 'password' => '', 'status' => ''],
            ],
        ])->assertRedirect(route('dashboard'));

        $this->assertDatabaseHas('activity_logs', ['action' => 'deleted', 'subject_type' => 'account', 'identifier' => 'del@example.com']);
        $this->assertDatabaseMissing('agent_accounts', ['id' => $account->id]);
        $this->assertDatabaseCount('activity_logs', 1);
    }

    public function test_hapus_agent_mencatat_deleted_dan_snapshot_tahan(): void
    {
        $inputer = $this->inputer();
        $agent = Agent::factory()->create(['name' => 'Doomed']);
        $platform = Platform::factory()->create(['slug' => 'google']);
        $agent->accounts()->create(['platform_id' => $platform->id, 'identifier' => 'x@example.com']);

        $this->actingAs($inputer)->delete("/agents/{$agent->id}")->assertRedirect(route('dashboard'));

        $this->assertDatabaseHas('activity_logs', ['action' => 'deleted', 'subject_type' => 'agent', 'agent_name' => 'Doomed']);
        // cascade account tidak di-log terpisah
        $this->assertDatabaseCount('activity_logs', 1);
        $this->assertDatabaseMissing('agents', ['id' => $agent->id]);
        $this->assertDatabaseEmpty('agent_accounts');

        $log = ActivityLog::sole();
        $original = $log->getRawOriginal();
        $inputer->delete();
        $platform->delete();

        $this->assertSame($original, $log->fresh()->getRawOriginal());
        $this->assertSame('Doomed', $log->fresh()->agent_name);
        $this->assertNull($log->fresh()->inputer);
    }

    public function test_account_standalone_store_update_destroy(): void
    {
        $inputer = $this->inputer();
        $agent = Agent::factory()->create(['name' => 'Host Agent']);
        $platform = Platform::factory()->create(['slug' => 'google', 'name' => 'Google']);
        $this->actingAs($inputer);

        $this->post("/agents/{$agent->id}/accounts", [
            'platform_id' => $platform->id,
            'identifier' => 'sa@example.com',
        ])->assertRedirect(route('dashboard'));
        $account = AgentAccount::sole();
        $this->assertDatabaseHas('activity_logs', [
            'action' => 'created', 'subject_type' => 'account', 'subject_id' => $account->id,
            'agent_name' => 'Host Agent', 'platform_name' => 'Google', 'identifier' => 'sa@example.com',
        ]);

        $this->patch("/agents/{$agent->id}/accounts/{$account->id}", [
            'platform_id' => $platform->id,
            'identifier' => 'sa2@example.com',
        ])->assertRedirect(route('dashboard'));
        $this->assertDatabaseHas('activity_logs', ['action' => 'updated', 'subject_type' => 'account', 'identifier' => 'sa2@example.com']);

        $this->delete("/agents/{$agent->id}/accounts/{$account->id}")->assertRedirect(route('dashboard'));
        $this->assertDatabaseHas('activity_logs', ['action' => 'deleted', 'subject_type' => 'account', 'identifier' => 'sa2@example.com']);
        $this->assertDatabaseCount('activity_logs', 3);
    }

    public function test_inputer_atribusi_dari_sesi_bukan_client(): void
    {
        $inputer = $this->inputer('Real Inputer');
        $other = User::factory()->create(['name' => 'Fake Actor']);
        $platform = Platform::factory()->create();

        $this->actingAs($inputer)->post('/agents', [
            'name' => 'Attributed',
            'email' => 'attributed@example.com',
            'password' => 'secret-agent',
            'created_by' => $other->id,
            'inputer_id' => $other->id,
            'accounts' => [['platform_id' => $platform->id, 'identifier' => 'attr@example.com']],
        ])->assertRedirect(route('dashboard'));

        $this->assertDatabaseHas('activity_logs', ['inputer_id' => $inputer->id, 'inputer_name' => 'Real Inputer']);
        $this->assertDatabaseMissing('activity_logs', ['inputer_id' => $other->id]);
    }

    public function test_gagal_log_membatalkan_transaksi(): void
    {
        $this->withoutExceptionHandling();
        $inputer = $this->inputer();
        $platform = Platform::factory()->create();
        $this->actingAs($inputer);
        ActivityLog::creating(fn () => throw new RuntimeException('Log write failed'));

        try {
            $this->post('/agents', [
                'name' => 'Rollback agent',
                'email' => 'rollback@example.com',
                'password' => 'secret-agent',
                'accounts' => [['platform_id' => $platform->id, 'identifier' => 'rb@example.com']],
            ]);
            $this->fail('Transaksi harus rollback saat log gagal.');
        } catch (RuntimeException $e) {
            $this->assertSame('Log write failed', $e->getMessage());
        } finally {
            ActivityLog::flushEventListeners();
        }

        $this->assertDatabaseEmpty('agents');
        $this->assertDatabaseEmpty('agent_accounts');
        $this->assertDatabaseEmpty('account_stage_credits');
        $this->assertDatabaseEmpty('activity_logs');
    }
}
