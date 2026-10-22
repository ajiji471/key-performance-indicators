<?php

namespace Database\Seeders;

use App\Models\Platform;
use Illuminate\Database\Seeder;

class PlatformSeeder extends Seeder
{
    public function run(): void
    {
        $platforms = [
            'google'    => 'Google',
            'youtube'   => 'YouTube',
            'facebook'  => 'Facebook',
            'instagram' => 'Instagram',
            'x'         => 'X',
            'tiktok'    => 'TikTok',
            'pinterest' => 'Pinterest',
        ];

        $sort = 1;

        // updateOrCreate jd aman dijalankan ulang di production
        foreach ($platforms as $slug => $name) {
            Platform::updateOrCreate(
                ['slug' => $slug],
                ['name' => $name, 'is_kpi' => true, 'sort' => $sort++],
            );
        }
    }
}
