<?php

namespace Tests\Feature;

use App\Enums\AccountStatus;
use App\Enums\Role;
use App\Models\AccountStageCredit;
use App\Models\Agent;
use App\Models\AgentAccount;
use App\Models\Platform;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use Tests\TestCase;

class AccountStageCreditTest extends TestCase
{
    use RefreshDatabase;

    public function test_registrasi_mencatat_inputer_dan_waktu_server(): void
    {
        $this->travelTo(Carbon::parse('2026-11-16 09:30:00'));
        $inputer = User::factory()->create(['role' => Role::User]);
        $other = User::factory()->create(['role' => Role::Admin]);
        $agent = Agent::factory()->create(['created_by' => $other->id]);
        $platform = Platform::factory()->create(['slug' => 'google']);

        $this->actingAs($inputer)->post("/agents/{$agent->id}/accounts", [
            'platform_id' => $platform->id,
            'identifier' => 'credit-test@example.com',
            'inputer_id' => $other->id,
            'achieved_at' => '2000-01-01 00:00:00',
        ])->assertRedirect(route('dashboard'));

        $this->assertTrue(Schema::hasTable('account_stage_credits'), 'Ledger kredit belum tersedia.');
        $this->assertDatabaseHas('account_stage_credits', [
            'agent_id' => $agent->id,
            'platform_id' => $platform->id,
            'platform_slug' => 'google',
            'stage' => 'registered',
            'inputer_id' => $inputer->id,
            'achieved_at' => '2026-11-16 09:30:00',
        ]);
        $this->assertDatabaseCount('account_stage_credits', 1);
    }

    public static function writePaths(): array
    {
        return [['standalone'], ['nested']];
    }

    private function payload(Platform $platform, array $overrides = []): array
    {
        return [
            'platform_id' => $platform->id,
            'identifier' => 'credit-test@example.com',
            ...$overrides,
        ];
    }

    private function storeAccount(string $path, Agent $agent, array $payload): void
    {
        if ($path === 'nested') {
            $this->post('/agents', [
                'name' => 'Credit test agent',
                'email' => 'credit-agent@example.com',
                'password' => 'test-password',
                'accounts' => [$payload],
            ])->assertRedirect(route('dashboard'));
        } else {
            $this->post("/agents/{$agent->id}/accounts", $payload)
                ->assertRedirect(route('dashboard'));
        }
    }

    private function updateAccount(string $path, Agent $agent, AgentAccount $account, array $payload): void
    {
        if ($path === 'nested') {
            $this->patch("/agents/{$agent->id}", [
                'name' => $agent->name,
                'email' => $agent->email,
                'accounts' => [$payload],
            ])->assertRedirect(route('dashboard'));
        } else {
            $this->patch("/agents/{$agent->id}/accounts/{$account->id}", $payload)
                ->assertRedirect(route('dashboard'));
        }
    }

    #[DataProvider('writePaths')]
    public function test_akun_baru_langsung_login_hanya_mendapat_kredit_login(string $path): void
    {
        $inputer = User::factory()->create(['role' => Role::User]);
        $agent = Agent::factory()->create();
        $platform = Platform::factory()->create(['slug' => 'google']);
        $this->actingAs($inputer);

        $this->storeAccount($path, $agent, $this->payload($platform, ['status' => 'logged_in']));

        $account = AgentAccount::sole();
        $this->assertSame(AccountStatus::LoggedIn, $account->status);
        $this->assertDatabaseCount('account_stage_credits', 1);
        $this->assertDatabaseHas('account_stage_credits', [
            'agent_id' => $account->agent_id,
            'stage' => 'logged_in',
            'inputer_id' => $inputer->id,
        ]);
    }

    #[DataProvider('writePaths')]
    public function test_status_kosong_default_registered_dan_update_menjaga_status_lama(string $path): void
    {
        $inputer = User::factory()->create(['role' => Role::User]);
        $agent = Agent::factory()->create();
        $platform = Platform::factory()->create();
        $this->actingAs($inputer);
        $this->storeAccount($path, $agent, $this->payload($platform, ['status' => null]));
        $account = AgentAccount::sole();
        $agent = $account->agent;

        $this->assertSame(AccountStatus::Registered, $account->status);
        $this->assertDatabaseHas('account_stage_credits', ['stage' => 'registered']);
        $this->updateAccount($path, $agent, $account, $this->payload($platform, ['status' => 'logged_in']));
        $this->updateAccount($path, $agent, $account, $this->payload($platform, ['status' => null]));

        $this->assertSame(AccountStatus::LoggedIn, $account->refresh()->status);
        $this->assertDatabaseCount('account_stage_credits', 2);
    }

    #[DataProvider('writePaths')]
    public function test_kredit_tahap_terpisah_milik_inputer_pelaksana(string $path): void
    {
        $registrar = User::factory()->create(['role' => Role::User]);
        $loginInputer = User::factory()->create(['role' => Role::Admin]);
        $agent = Agent::factory()->create(['created_by' => $loginInputer->id]);
        $platform = Platform::factory()->create(['slug' => 'google']);
        $this->travelTo(Carbon::parse('2026-11-14 09:00:00'));
        $this->actingAs($registrar);
        $this->storeAccount($path, $agent, $this->payload($platform));
        $account = AgentAccount::sole();
        $agent = $account->agent;

        $this->travelTo(Carbon::parse('2026-11-15 10:00:00'));
        $this->actingAs($loginInputer);
        $this->updateAccount($path, $agent, $account, $this->payload($platform, [
            'status' => 'logged_in',
            'inputer_id' => $registrar->id,
            'achieved_at' => '2000-01-01 00:00:00',
        ]));

        $this->assertDatabaseCount('account_stage_credits', 2);
        $this->assertDatabaseHas('account_stage_credits', [
            'agent_id' => $agent->id,
            'platform_id' => $platform->id,
            'stage' => 'registered',
            'inputer_id' => $registrar->id,
            'achieved_at' => '2026-11-14 09:00:00',
        ]);
        $this->assertDatabaseHas('account_stage_credits', [
            'agent_id' => $agent->id,
            'platform_id' => $platform->id,
            'stage' => 'logged_in',
            'inputer_id' => $loginInputer->id,
            'achieved_at' => '2026-11-15 10:00:00',
        ]);
    }

    public function test_tujuh_platform_menghasilkan_tujuh_kredit_per_tahap(): void
    {
        $registrar = User::factory()->create(['role' => Role::User]);
        $loginInputer = User::factory()->create(['role' => Role::User]);
        $platforms = Platform::factory()->count(7)->create();
        $accounts = $platforms->map(fn ($platform) => $this->payload($platform))->all();
        $agentPayload = ['name' => 'Seven platforms', 'email' => 'seven@example.com', 'password' => 'test-password'];

        $this->actingAs($registrar)->post('/agents', [...$agentPayload, 'accounts' => $accounts])
            ->assertRedirect(route('dashboard'));
        $agent = Agent::sole();
        $this->assertSame(7, AccountStageCredit::where('inputer_id', $registrar->id)->where('stage', 'registered')->count());

        $accounts = $platforms->map(fn ($platform) => $this->payload($platform, ['status' => 'logged_in']))->all();
        $this->actingAs($loginInputer)->patch("/agents/{$agent->id}", [...$agentPayload, 'accounts' => $accounts])
            ->assertRedirect(route('dashboard'));
        $this->assertSame(7, AccountStageCredit::where('inputer_id', $loginInputer->id)->where('stage', 'logged_in')->count());
        $this->assertDatabaseCount('account_stage_credits', 14);
    }

    public function test_nested_update_mencatat_platform_baru_tanpa_identifier_kosong(): void
    {
        $inputer = User::factory()->create(['role' => Role::User]);
        $agent = Agent::factory()->create();
        $platforms = Platform::factory()->count(2)->create();
        $this->actingAs($inputer)->patch("/agents/{$agent->id}", [
            'name' => $agent->name,
            'email' => $agent->email,
            'accounts' => [
                $this->payload($platforms[0]),
                $this->payload($platforms[1], ['identifier' => '']),
            ],
        ])->assertRedirect(route('dashboard'));

        $this->assertDatabaseCount('agent_accounts', 1);
        $this->assertDatabaseCount('account_stage_credits', 1);
        $this->assertDatabaseHas('account_stage_credits', ['platform_id' => $platforms[0]->id, 'inputer_id' => $inputer->id]);
    }

    #[DataProvider('writePaths')]
    public function test_akun_lama_edit_biasa_tidak_backfill_tapi_transisi_baru_dicatat(string $path): void
    {
        $inputer = User::factory()->create(['role' => Role::User]);
        $agent = Agent::factory()->create();
        $platform = Platform::factory()->create();
        $account = $agent->accounts()->create($this->payload($platform, ['status' => 'registered']));
        $this->actingAs($inputer);
        $this->updateAccount($path, $agent, $account, $this->payload($platform, [
            'identifier' => 'edited@example.com', 'password' => 'new-password', 'status' => 'registered',
        ]));
        $this->assertDatabaseEmpty('account_stage_credits');

        $this->updateAccount($path, $agent, $account, $this->payload($platform, ['status' => 'logged_in']));
        $this->assertDatabaseCount('account_stage_credits', 1);
        $this->assertDatabaseHas('account_stage_credits', ['stage' => 'logged_in', 'inputer_id' => $inputer->id]);
    }

    #[DataProvider('writePaths')]
    public function test_status_ulang_tidak_menggandakan_atau_memindahkan_kredit(string $path): void
    {
        $first = User::factory()->create(['role' => Role::User]);
        $other = User::factory()->create(['role' => Role::User]);
        $agent = Agent::factory()->create();
        $platform = Platform::factory()->create();
        $this->freezeTime();
        $this->actingAs($first);
        $this->storeAccount($path, $agent, $this->payload($platform));
        $account = AgentAccount::sole();
        $agent = $account->agent;
        $this->updateAccount($path, $agent, $account, $this->payload($platform, ['status' => 'logged_in']));
        $original = DB::table('account_stage_credits')->orderBy('id')->get()->toJson();
        $this->assertDatabaseCount('account_stage_credits', 2);

        $this->travel(2)->days();
        $this->actingAs($other);
        foreach (['logged_in', 'trouble', 'recovered', 'registered', 'logged_in'] as $status) {
            $this->updateAccount($path, $agent, $account, $this->payload($platform, ['status' => $status]));
        }
        $this->assertSame($original, DB::table('account_stage_credits')->orderBy('id')->get()->toJson());
    }

    #[DataProvider('writePaths')]
    public function test_trouble_dan_recovered_tidak_memberi_kredit(string $path): void
    {
        $inputer = User::factory()->create(['role' => Role::User]);
        $agent = Agent::factory()->create();
        $platform = Platform::factory()->create();
        $this->actingAs($inputer);
        $this->storeAccount($path, $agent, $this->payload($platform, ['status' => 'trouble']));
        $account = AgentAccount::sole();
        $this->assertSame(AccountStatus::Trouble, $account->status);
        $this->updateAccount($path, $account->agent, $account, $this->payload($platform, ['status' => 'recovered']));
        $this->assertSame(AccountStatus::Recovered, $account->refresh()->status);
        $this->assertDatabaseEmpty('account_stage_credits');
    }

    #[DataProvider('writePaths')]
    public function test_hapus_dan_buat_ulang_akun_tidak_mereset_kredit(string $path): void
    {
        $first = User::factory()->create(['role' => Role::User]);
        $other = User::factory()->create(['role' => Role::Admin]);
        $agent = Agent::factory()->create();
        $platform = Platform::factory()->create();
        $this->actingAs($first);
        $this->storeAccount('standalone', $agent, $this->payload($platform));
        $account = AgentAccount::sole();
        $original = DB::table('account_stage_credits')->get()->toJson();
        $this->assertDatabaseCount('account_stage_credits', 1);
        $this->actingAs($other);

        if ($path === 'nested') {
            $this->updateAccount($path, $agent, $account, $this->payload($platform, ['identifier' => '']));
        } else {
            $this->delete("/agents/{$agent->id}/accounts/{$account->id}")->assertRedirect(route('dashboard'));
        }
        $this->assertDatabaseEmpty('agent_accounts');
        $this->assertSame($original, DB::table('account_stage_credits')->get()->toJson());
        $this->storeAccount('standalone', $agent, $this->payload($platform));
        $this->assertSame($original, DB::table('account_stage_credits')->get()->toJson());
        $this->assertDatabaseCount('agent_accounts', 1);
    }

    public function test_ganti_platform_tanpa_transisi_status_tidak_memberi_kredit_baru(): void
    {
        $inputer = User::factory()->create(['role' => Role::User]);
        $agent = Agent::factory()->create();
        $platforms = Platform::factory()->count(2)->create();
        $this->actingAs($inputer);
        $this->storeAccount('standalone', $agent, $this->payload($platforms[0]));
        $account = AgentAccount::sole();
        $this->updateAccount('standalone', $agent, $account, $this->payload($platforms[1], ['status' => 'registered']));

        $this->assertSame($platforms[1]->id, $account->refresh()->platform_id);
        $this->assertDatabaseCount('account_stage_credits', 1);
        $this->assertDatabaseHas('account_stage_credits', ['platform_id' => $platforms[0]->id]);
    }

    public function test_status_invalid_standalone_ditolak_tanpa_kredit(): void
    {
        $inputer = User::factory()->create(['role' => Role::User]);
        $agent = Agent::factory()->create();
        $platform = Platform::factory()->create();
        $payload = $this->payload($platform, ['status' => 'invalid']);
        $this->actingAs($inputer)->post("/agents/{$agent->id}/accounts", $payload)->assertSessionHasErrors('status');
        $this->assertDatabaseEmpty('agent_accounts');

        $account = $agent->accounts()->create($this->payload($platform, ['status' => 'registered']));
        $this->patch("/agents/{$agent->id}/accounts/{$account->id}", $payload)->assertSessionHasErrors('status');
        $this->assertSame(AccountStatus::Registered, $account->refresh()->status);
        $this->assertDatabaseEmpty('account_stage_credits');
    }

    public function test_hapus_agent_user_dan_platform_tetap_menyimpan_identitas_kredit(): void
    {
        $inputer = User::factory()->create(['role' => Role::User]);
        $agent = Agent::factory()->create();
        $platform = Platform::factory()->create(['slug' => 'google']);
        $this->actingAs($inputer);
        $this->storeAccount('standalone', $agent, $this->payload($platform));
        $credit = AccountStageCredit::sole();
        $original = $credit->getRawOriginal();
        $this->assertSame($inputer->id, $credit->inputer->id);

        $this->delete("/agents/{$agent->id}")->assertRedirect(route('dashboard'));
        $inputer->delete();
        $platform->delete();

        $this->assertDatabaseEmpty('agent_accounts');
        $this->assertSame($original, $credit->fresh()->getRawOriginal());
        $this->assertNull($credit->fresh()->inputer);
    }

    public function test_unique_constraint_menolak_kredit_tahap_duplikat(): void
    {
        $inputer = User::factory()->create(['role' => Role::User]);
        $agent = Agent::factory()->create();
        $platform = Platform::factory()->create();
        $this->actingAs($inputer);
        $this->storeAccount('standalone', $agent, $this->payload($platform));
        $row = (array) DB::table('account_stage_credits')->sole();
        unset($row['id']);

        $this->expectException(UniqueConstraintViolationException::class);
        DB::table('account_stage_credits')->insert($row);
    }

    #[DataProvider('writePaths')]
    public function test_gagal_kredit_membatalkan_pembuatan_akun(string $path): void
    {
        $this->withoutExceptionHandling();
        $inputer = User::factory()->create(['role' => Role::User]);
        $agent = Agent::factory()->create();
        $platform = Platform::factory()->create();
        $agentCount = Agent::count();
        $this->actingAs($inputer);
        AccountStageCredit::creating(fn () => throw new RuntimeException('Credit write failed'));

        try {
            $this->storeAccount($path, $agent, $this->payload($platform));
            $this->fail('Account write should roll back when credit creation fails.');
        } catch (RuntimeException $e) {
            $this->assertSame('Credit write failed', $e->getMessage());
        } finally {
            AccountStageCredit::flushEventListeners();
        }

        $this->assertSame($agentCount, Agent::count());
        $this->assertDatabaseEmpty('agent_accounts');
        $this->assertDatabaseEmpty('account_stage_credits');
    }

    #[DataProvider('writePaths')]
    public function test_gagal_kredit_membatalkan_update_akun(string $path): void
    {
        $this->withoutExceptionHandling();
        $inputer = User::factory()->create(['role' => Role::User]);
        $agent = Agent::factory()->create();
        $platform = Platform::factory()->create();
        $account = $agent->accounts()->create($this->payload($platform, ['status' => 'registered']));
        $this->actingAs($inputer);
        AccountStageCredit::creating(fn () => throw new RuntimeException('Credit write failed'));

        try {
            $this->updateAccount($path, $agent, $account, $this->payload($platform, [
                'status' => 'logged_in', 'identifier' => 'must-rollback@example.com',
            ]));
            $this->fail('Account update should roll back when credit creation fails.');
        } catch (RuntimeException $e) {
            $this->assertSame('Credit write failed', $e->getMessage());
        } finally {
            AccountStageCredit::flushEventListeners();
        }

        $this->assertSame(AccountStatus::Registered, $account->refresh()->status);
        $this->assertSame('credit-test@example.com', $account->identifier);
        $this->assertDatabaseEmpty('account_stage_credits');
    }

    public function test_kegagalan_platform_kedua_membatalkan_agent_dan_kredit_pertama(): void
    {
        $this->withoutExceptionHandling();
        $inputer = User::factory()->create(['role' => Role::User]);
        $platforms = Platform::factory()->count(2)->create();
        AccountStageCredit::creating(function ($credit) use ($platforms) {
            if ($credit->platform_id === $platforms[1]->id) {
                throw new RuntimeException('Second credit failed');
            }
        });

        try {
            $this->actingAs($inputer)->post('/agents', [
                'name' => 'Rollback agent', 'email' => 'rollback@example.com', 'password' => 'test-password',
                'accounts' => $platforms->map(fn ($platform) => $this->payload($platform))->all(),
            ]);
            $this->fail('The entire batch must roll back.');
        } catch (RuntimeException $e) {
            $this->assertSame('Second credit failed', $e->getMessage());
        } finally {
            AccountStageCredit::flushEventListeners();
        }

        $this->assertDatabaseEmpty('agents');
        $this->assertDatabaseEmpty('agent_accounts');
        $this->assertDatabaseEmpty('account_stage_credits');
    }

    public function test_guest_auditor_dan_agent_lain_tidak_dapat_menghasilkan_kredit(): void
    {
        $agent = Agent::factory()->create();
        $other = Agent::factory()->create();
        $platform = Platform::factory()->create();
        $account = $agent->accounts()->create($this->payload($platform, ['status' => 'registered']));
        $payload = $this->payload($platform, ['status' => 'logged_in']);

        $this->patch("/agents/{$agent->id}/accounts/{$account->id}", $payload)->assertRedirect('/login');
        $auditor = User::factory()->create(['role' => Role::Auditor]);
        $this->actingAs($auditor)->patch("/agents/{$agent->id}/accounts/{$account->id}", $payload)->assertForbidden();
        $user = User::factory()->create(['role' => Role::User]);
        $this->actingAs($user)->patch("/agents/{$other->id}/accounts/{$account->id}", $payload)->assertNotFound();
        $this->assertSame(AccountStatus::Registered, $account->refresh()->status);
        $this->assertDatabaseEmpty('account_stage_credits');
    }
}
