<?php

namespace Tests\Feature;

use App\Models\Agent;
use App\Models\AgentAccount;
use Database\Seeders\AgentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AgentSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeder_memetakan_agent_flat_ke_skema_project(): void
    {
        $this->seed(AgentSeeder::class);

        $this->assertDatabaseCount('agents', 118);
        $this->assertDatabaseHas('users', ['email' => 'admin@example.com']);

        $agent = Agent::with('accounts.platform')
            ->where('name', 'AS1-001 - Made Neelson')
            ->firstOrFail();

        $this->assertSame('madeneelson@gmail.com', $agent->email);
        $this->assertSame('exiting', $agent->context);
        $this->assertSame('WastuKencana1990', $agent->password);

        $statuses = $agent->accounts->mapWithKeys(
            fn ($account) => [$account->platform->slug => $account->status->value]
        );

        $this->assertSame('logged_in', $statuses['google']);
        $this->assertSame('logged_in', $statuses['youtube']);
        $this->assertSame('logged_in', $statuses['facebook']);
        $this->assertSame('logged_in', $statuses['x']);
        $this->assertSame('logged_in', $statuses['pinterest']);
        $this->assertArrayNotHasKey('instagram', $statuses);
        $this->assertArrayNotHasKey('tiktok', $statuses);
    }

    public function test_seeder_memisahkan_email_dan_multi_identifier(): void
    {
        $this->seed(AgentSeeder::class);

        $agent = Agent::with('accounts.platform')
            ->where('name', 'AS1-001 - Wilson Alung 2')
            ->firstOrFail();

        $this->assertSame('alungwilson2@gmail.com', $agent->email);
        $this->assertSame('wilsoonaloeng@gmail.com', $agent->recovery_email);

        $google = $agent->accounts->firstWhere('platform.slug', 'google');
        $this->assertNotNull($google);
        $this->assertSame('alungwilson2@gmail.com', $google->identifier);
        $this->assertSame('registered', $google->status->value);

        $rani = Agent::with('accounts.platform')
            ->where('name', 'Rani Maharani')
            ->firstOrFail();

        $facebook = $rani->accounts->firstWhere('platform.slug', 'facebook');
        $this->assertSame('trouble', $facebook->status->value);

        $mail = Agent::where('name', 'Mail Cungkring')->firstOrFail();
        $this->assertSame('1999-05-23', $mail->birth_date->toDateString());
    }

    public function test_seeder_aman_dijalankan_ulang(): void
    {
        $this->seed(AgentSeeder::class);

        $agents = Agent::count();
        $accounts = AgentAccount::count();

        $this->seed(AgentSeeder::class);

        $this->assertSame($agents, Agent::count());
        $this->assertSame($accounts, AgentAccount::count());
        $this->assertDatabaseCount('agents', 118);
    }
}
