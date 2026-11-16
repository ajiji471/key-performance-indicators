<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\AccountStageCredit;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\Fluent\AssertableJson;
use Illuminate\Testing\TestResponse;
use Inertia\Testing\AssertableInertia as Assert;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class KpiTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_diarahkan_ke_login(): void
    {
        $this->get('/kpi')->assertRedirect('/login');
    }

    #[DataProvider('responseModes')]
    public function test_user_hanya_menerima_card_sendiri_meski_memalsukan_scope(bool $partial): void
    {
        $viewer = User::factory()->create(['role' => Role::User]);
        $other = User::factory()->create(['role' => Role::User, 'name' => 'Private Other', 'email' => 'other-private@example.com']);
        $deleted = User::factory()->create(['name' => 'Private Deleted']);
        $this->credit($other->id);
        $this->credit($deleted->id);
        $deleted->delete();

        $response = $this->actingAs($viewer)->get(
            '/kpi?inputer_id='.$other->id.'&user_id='.$other->id.'&scope=all&period=all',
            $this->headers($partial),
        )->assertOk();
        $props = $this->props($response, $partial);
        AssertableJson::fromArray($props)
            ->where('kpi.scope', 'self')
            ->where('kpi.has_activity', false)
            ->has('kpi.inputers', 1)
            ->where('kpi.inputers.0.inputer_id', $viewer->id)
            ->where('kpi.inputers.0.name', $viewer->name)
            ->where('kpi.inputers.0.is_deleted', false)
            ->where('kpi.inputers.0.periods.all.email_registered', 0)
            ->where('kpi.inputers.0.periods.today.agent_registered', 0)
            ->missing('filters')
            ->missing('period');

        $kpi = $props['kpi'];
        $this->assertSame([$viewer->id], array_column($kpi['inputers'], 'inputer_id'));
        foreach ([$other, $deleted] as $hidden) {
            $this->assertStringNotContainsString($hidden->name, json_encode($kpi));
            $this->assertStringNotContainsString($hidden->email, json_encode($kpi));
            $this->assertStringNotContainsString('"inputer_id":'.$hidden->id, json_encode($kpi));
        }
        foreach (['today', 'week', 'month', 'all'] as $period) {
            $this->assertEquals([
                'email_registered' => 0, 'email_login' => 0, 'agent_registered' => 0,
                'agent_login' => 0, 'registered_credits' => 0, 'login_credits' => 0,
            ], $kpi['inputers'][0]['periods'][$period]);
        }
    }

    #[DataProvider('responseModes')]
    public function test_user_menerima_kredit_sendiri_bukan_kredit_user_lain(bool $partial): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-11-18 12:00:00', 'UTC'));
        $viewer = User::factory()->create(['role' => Role::User]);
        $other = User::factory()->create(['role' => Role::Admin]);
        $this->credit($viewer->id);
        $this->credit($viewer->id, 'logged_in');
        $this->credit($other->id);

        $response = $this->actingAs($viewer)->get('/kpi?scope=all&inputer_id='.$other->id, $this->headers($partial))->assertOk();
        AssertableJson::fromArray($this->props($response, $partial))
            ->where('kpi.scope', 'self')
            ->where('kpi.has_activity', true)
            ->has('kpi.inputers', 1)
            ->where('kpi.inputers.0.inputer_id', $viewer->id)
            ->where('kpi.inputers.0.periods.today.email_registered', 1)
            ->where('kpi.inputers.0.periods.today.email_login', 1)
            ->where('kpi.inputers.0.periods.all.registered_credits', 1)
            ->where('kpi.inputers.0.periods.all.login_credits', 1);
    }

    public static function responseModes(): array
    {
        return ['normal' => [false], 'partial reload' => [true]];
    }

    #[DataProvider('privilegedRoles')]
    public function test_admin_dan_auditor_menerima_semua_profil_termasuk_diri_dan_user_nol(Role $role): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-11-18 12:00:00', 'UTC'));
        $viewer = User::factory()->create(['role' => $role, 'name' => 'B Viewer']);
        $inputer = User::factory()->create(['role' => Role::User, 'name' => 'A Inputer']);
        $zero = User::factory()->create(['role' => Role::User, 'name' => 'C Zero', 'is_active' => false]);
        $this->credit($viewer->id);
        $this->credit($inputer->id);

        $this->actingAs($viewer)->get('/kpi?scope=self&inputer_id='.$zero->id.'&user_id='.$zero->id)
            ->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('Kpi/Index')
            ->where('kpi.scope', 'all')
            ->where('kpi.has_activity', true)
            ->has('kpi.inputers', 3)
            ->where('kpi.inputers.0.inputer_id', $inputer->id)
            ->where('kpi.inputers.0.periods.all.email_registered', 1)
            ->where('kpi.inputers.1.inputer_id', $viewer->id)
            ->where('kpi.inputers.1.periods.all.email_registered', 1)
            ->where('kpi.inputers.2.inputer_id', $zero->id)
            ->where('kpi.inputers.2.periods.all.email_registered', 0)
            ->where('kpi.inputers.2.is_deleted', false));
    }

    #[DataProvider('privilegedRoles')]
    public function test_admin_dan_auditor_menerima_histori_lama_meski_periode_lain_kosong(Role $role): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-11-18 12:00:00', 'UTC'));
        $viewer = User::factory()->create(['role' => $role, 'name' => 'A Viewer']);
        $deleted = User::factory()->create();
        $this->credit($deleted->id, 'registered', '2026-10-31 09:00:00');
        $deleted->delete();

        $this->actingAs($viewer)->get('/kpi')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->where('kpi.scope', 'all')
            ->where('kpi.has_activity', true)
            ->has('kpi.inputers', 2)
            ->where('kpi.inputers.1.inputer_id', $deleted->id)
            ->where('kpi.inputers.1.name', 'Inputer tidak tersedia')
            ->where('kpi.inputers.1.is_deleted', true)
            ->where('kpi.inputers.1.periods.today.email_registered', 0)
            ->where('kpi.inputers.1.periods.week.email_registered', 0)
            ->where('kpi.inputers.1.periods.month.email_registered', 0)
            ->where('kpi.inputers.1.periods.all.email_registered', 1));
    }

    public static function privilegedRoles(): array
    {
        return ['admin' => [Role::Admin], 'auditor' => [Role::Auditor]];
    }

    public function test_props_card_memakai_allowlist_dan_empat_periode_berurutan(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-11-18 12:00:00', 'UTC'));
        $viewer = User::factory()->create(['role' => Role::Admin]);
        $response = $this->actingAs($viewer)->get('/kpi')->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Kpi/Index')->has('kpi')->missing('filters')->missing('period'));
        $kpi = $response->viewData('page')['props']['kpi'];

        $this->assertSame(['scope', 'timezone', 'has_activity', 'periods', 'inputers'], array_keys($kpi));
        $this->assertSame('all', $kpi['scope']);
        $this->assertSame('UTC', $kpi['timezone']);
        $this->assertFalse($kpi['has_activity']);
        $this->assertSame([
            'today' => ['label' => 'Hari ini', 'from' => '2026-11-18T00:00:00+00:00', 'until' => '2026-11-19T00:00:00+00:00'],
            'week' => ['label' => 'Minggu ini', 'from' => '2026-11-16T00:00:00+00:00', 'until' => '2026-11-23T00:00:00+00:00'],
            'month' => ['label' => 'Bulan ini', 'from' => '2026-11-01T00:00:00+00:00', 'until' => '2026-12-01T00:00:00+00:00'],
            'all' => ['label' => 'Total', 'from' => null, 'until' => null],
        ], $kpi['periods']);
        $this->assertSame(['today', 'week', 'month', 'all'], array_keys($kpi['periods']));
        $this->assertCount(1, $kpi['inputers']);
        $row = $kpi['inputers'][0];
        $this->assertSame(['inputer_id', 'name', 'is_deleted', 'periods'], array_keys($row));
        $this->assertSame(['today', 'week', 'month', 'all'], array_keys($row['periods']));
        foreach ($row['periods'] as $counts) {
            $this->assertSame(['email_registered', 'email_login', 'agent_registered', 'agent_login', 'registered_credits', 'login_credits'], array_keys($counts));
            $this->assertIsInt($counts['email_registered']);
            $this->assertIsInt($counts['email_login']);
        }
        foreach ([$viewer->email, 'password', 'identifier', 'average', 'category', 'active_days'] as $private) {
            $this->assertStringNotContainsString($private, json_encode($kpi));
        }
    }

    public function test_query_periode_dan_identitas_bukan_input_halaman(): void
    {
        $viewer = User::factory()->create(['role' => Role::User]);
        foreach (['period=week', 'period=invalid', 'period[]=today&inputer_id[]=999&user_id=999&scope[all]=true'] as $query) {
            $this->actingAs($viewer)->getJson('/kpi?'.$query)->assertOk()->assertInertia(fn (Assert $page) => $page
                ->where('kpi.scope', 'self')
                ->has('kpi.periods', 4)
                ->has('kpi.inputers', 1)
                ->where('kpi.inputers.0.inputer_id', $viewer->id)
                ->missing('filters')
                ->missing('period'));
        }
    }

    private function props(TestResponse $response, bool $partial): array
    {
        if ($partial) {
            $response->assertHeader('X-Inertia', 'true')->assertJsonPath('component', 'Kpi/Index');

            return $response->json('props');
        }

        $response->assertInertia(fn (Assert $page) => $page->component('Kpi/Index'));

        return $response->viewData('page')['props'];
    }

    private function headers(bool $partial): array
    {
        return $partial ? [
            'X-Inertia' => 'true',
            'X-Inertia-Version' => $this->get('/kpi')->assertOk()->viewData('page')['version'],
            'X-Inertia-Partial-Component' => 'Kpi/Index',
            'X-Inertia-Partial-Data' => 'kpi',
        ] : [];
    }

    private function credit(int $inputerId, string $stage = 'registered', ?string $date = null): void
    {
        AccountStageCredit::create([
            'agent_id' => AccountStageCredit::max('agent_id') + 1,
            'platform_id' => 1,
            'platform_slug' => 'google',
            'stage' => $stage,
            'inputer_id' => $inputerId,
            'achieved_at' => $date ?? now(),
        ]);
    }
}
