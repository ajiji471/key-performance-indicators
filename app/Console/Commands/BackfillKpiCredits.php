<?php

namespace App\Console\Commands;

use App\Enums\AccountStatus;
use App\Models\AccountStageCredit;
use App\Models\Agent;
use Illuminate\Console\Command;

class BackfillKpiCredits extends Command
{
    protected $signature = 'kpi:backfill-credits';

    protected $description = 'Backfill account stage credits from current agent account statuses';

    public function handle(): int
    {
        $created = 0;

        Agent::with('accounts.platform')->chunkById(200, function ($agents) use (&$created) {
            foreach ($agents as $agent) {
                foreach ($agent->accounts as $account) {
                    foreach ($this->stages($account->status) as $stage) {
                        $credit = AccountStageCredit::firstOrCreate([
                            'agent_id' => $account->agent_id,
                            'platform_id' => $account->platform_id,
                            'stage' => $stage,
                        ], [
                            'platform_slug' => $account->platform->slug,
                            'inputer_id' => $agent->created_by,
                            'achieved_at' => $agent->created_at,
                        ]);

                        if ($credit->wasRecentlyCreated) {
                            $created++;
                        }
                    }
                }
            }
        });

        $this->info("Backfilled {$created} new account stage credits.");

        return self::SUCCESS;
    }

    /**
     * @return array<int, string>
     */
    private function stages(?AccountStatus $status): array
    {
        return match ($status) {
            AccountStatus::Registered => ['registered'],
            AccountStatus::LoggedIn => ['registered', 'logged_in'],
            default => [],
        };
    }
}
