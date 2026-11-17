<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\ActivityLog;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class HistoryTest extends TestCase
{
    use RefreshDatabase;

    private function viewer(Role $role = Role::Admin): User
    {
        return User::factory()->create(['role' => $role]);
    }

    private function log(array $overrides = []): ActivityLog
    {
        return ActivityLog::create([
            'inputer_id' => $overrides['inputer_id'] ?? 1,
            'inputer_name' => $overrides['inputer_name'] ?? 'Inputer QA',
            'action' => $overrides['action'] ?? 'created',
            'subject_type' => $overrides['subject_type'] ?? 'agent',
            'subject_id' => $overrides['subject_id'] ?? 1,
            'agent_id' => $overrides['agent_id'] ?? 1,
            'agent_name' => $overrides['agent_name'] ?? 'Andrew',
            'platform_id' => $overrides['platform_id'] ?? null,
            'platform_slug' => $overrides['platform_slug'] ?? null,
            'platform_name' => $overrides['platform_name'] ?? null,
            'identifier' => $overrides['identifier'] ?? null,
            'status_from' => $overrides['status_from'] ?? null,
            'status_to' => $overrides['status_to'] ?? null,
            'occurred_at' => $overrides['occurred_at'] ?? Carbon::parse('2026-11-17 00:29:00'),
        ]);
    }

    public function test_guest_redirect_ke_login(): void
    {
        $this->get('/history')->assertRedirect('/login');
    }

    public static function roles(): array
    {
        return [['admin'], ['user'], ['auditor']];
    }

    #[DataProvider('roles')]
    public function test_semua_role_bisa_akses_history(string $role): void
    {
        $this->actingAs(User::factory()->create(['role' => $role]))
            ->get('/history')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('History/Index')
                ->has('logs')
                ->has('filters')
            );
    }

    public function test_semua_role_lihat_semua_aktivitas(): void
    {
        $a = $this->viewer(Role::User);
        $b = $this->viewer(Role::User);
        $this->log(['inputer_id' => $a->id, 'inputer_name' => 'A', 'agent_name' => 'Agent A']);
        $this->log(['inputer_id' => $b->id, 'inputer_name' => 'B', 'agent_name' => 'Agent B']);

        $this->actingAs($a)->get('/history')
            ->assertInertia(fn (Assert $page) => $page->has('logs.data', 2));
    }

    public function test_filter_range_from_until_inklusif(): void
    {
        $viewer = $this->viewer();
        $this->log(['occurred_at' => Carbon::parse('2026-11-10 08:00:00'), 'agent_name' => 'Old']);
        $this->log(['occurred_at' => Carbon::parse('2026-11-15 08:00:00'), 'agent_name' => 'Mid']);
        $this->log(['occurred_at' => Carbon::parse('2026-11-20 08:00:00'), 'agent_name' => 'New']);
        $this->actingAs($viewer);

        $this->get('/history?from=2026-11-14&until=2026-11-16')
            ->assertInertia(fn (Assert $page) => $page
                ->has('logs.data', 1)
                ->where('logs.data.0.agent_name', 'Mid')
                ->where('filters.from', '2026-11-14')
                ->where('filters.until', '2026-11-16')
            );

        $this->get('/history?from=2026-11-10&until=2026-11-20')
            ->assertInertia(fn (Assert $page) => $page->has('logs.data', 3));

        $this->get('/history?from=2026-11-15&until=2026-11-15')
            ->assertInertia(fn (Assert $page) => $page->has('logs.data', 1)->where('logs.data.0.agent_name', 'Mid'));
    }

    public function test_filter_tanggal_invalid_ditolak(): void
    {
        $viewer = $this->viewer();
        $this->actingAs($viewer)->get('/history?from=not-a-date')->assertSessionHasErrors('from');
        $this->actingAs($viewer)->get('/history?until=2026-13-99')->assertSessionHasErrors('until');
    }

    public function test_pagination_dan_query_string(): void
    {
        $viewer = $this->viewer();
        for ($i = 0; $i < 20; $i++) {
            $this->log([
                'occurred_at' => Carbon::parse('2026-11-17 00:00:00')->addMinutes($i),
                'agent_name' => "A{$i}",
            ]);
        }

        $this->actingAs($viewer)->get('/history?from=2026-11-17&until=2026-11-17')
            ->assertInertia(fn (Assert $page) => $page
                ->where('logs.current_page', 1)
                ->where('logs.last_page', 2)
                ->has('logs.data', 15)
                ->where('logs.prev_page_url', null)
                ->where('logs.next_page_url', fn ($url) => str_contains($url, 'page=2')
                    && str_contains($url, 'from=2026-11-17')
                    && str_contains($url, 'until=2026-11-17'))
            );
    }

    public function test_urutan_newest_first(): void
    {
        $viewer = $this->viewer();
        $this->log(['occurred_at' => Carbon::parse('2026-11-17 01:00:00'), 'agent_name' => 'Earlier']);
        $this->log(['occurred_at' => Carbon::parse('2026-11-17 03:00:00'), 'agent_name' => 'Later']);

        $this->actingAs($viewer)->get('/history')
            ->assertInertia(fn (Assert $page) => $page
                ->where('logs.data.0.agent_name', 'Later')
                ->where('logs.data.1.agent_name', 'Earlier')
            );
    }

    public function test_props_tanpa_password(): void
    {
        $viewer = $this->viewer();
        $this->log([
            'subject_type' => 'account',
            'identifier' => 'x@example.com',
            'platform_name' => 'Google',
            'platform_slug' => 'google',
        ]);

        $this->actingAs($viewer)->get('/history')
            ->assertInertia(fn (Assert $page) => $page
                ->has('logs.data', 1)
                ->where('logs.data.0.identifier', 'x@example.com')
                ->missing('logs.data.0.password')
            );
    }

    public function test_props_status_transisi_account_updated(): void
    {
        $viewer = $this->viewer();
        $this->log([
            'action' => 'updated',
            'subject_type' => 'account',
            'identifier' => 'https://www.facebook.com/profile.php?id=123',
            'platform_name' => 'Facebook',
            'platform_slug' => 'facebook',
            'status_from' => 'registered',
            'status_to' => 'logged_in',
        ]);

        $this->actingAs($viewer)->get('/history')
            ->assertInertia(fn (Assert $page) => $page
                ->has('logs.data', 1)
                ->where('logs.data.0.action', 'updated')
                ->where('logs.data.0.status_from', 'registered')
                ->where('logs.data.0.status_to', 'logged_in')
            );
    }
}
