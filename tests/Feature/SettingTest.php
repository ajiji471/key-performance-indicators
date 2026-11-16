<?php

namespace Tests\Feature;

use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SettingTest extends TestCase
{
    use RefreshDatabase;

    public function test_get_default_bila_key_belum_ada(): void
    {
        $this->assertSame(200, Setting::get('agent_target', 200));
        $this->assertNull(Setting::get('missing'));
    }

    public function test_put_lalu_get(): void
    {
        Setting::put('agent_target', 350);

        $this->assertSame('350', Setting::get('agent_target'));
        $this->assertDatabaseHas('settings', ['key' => 'agent_target', 'value' => '350']);
    }

    public function test_put_dua_kali_update_baris_yang_sama(): void
    {
        Setting::put('agent_target', 350);
        Setting::put('agent_target', 500);

        $this->assertSame('500', Setting::get('agent_target'));
        $this->assertDatabaseCount('settings', 1);
    }
}
