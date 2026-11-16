<?php

namespace App\Services;

use App\Enums\AccountStatus;
use App\Models\AccountStageCredit;
use App\Models\AgentAccount;
use App\Models\User;

class AccountStageCreditRecorder
{
    public function record(AgentAccount $account, User $inputer, ?AccountStatus $previousStatus): void
    {
        $stage = $account->status;

        if ($stage === $previousStatus || ! in_array($stage, [AccountStatus::Registered, AccountStatus::LoggedIn], true)) {
            return;
        }

        AccountStageCredit::firstOrCreate([
            'agent_id' => $account->agent_id,
            'platform_id' => $account->platform_id,
            'stage' => $stage->value,
        ], [
            'platform_slug' => $account->platform->slug,
            'inputer_id' => $inputer->id,
            'achieved_at' => now(),
        ]);
    }
}
