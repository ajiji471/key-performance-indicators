<?php

namespace App\Services;

use App\Enums\ActivityAction;
use App\Enums\ActivitySubject;
use App\Models\ActivityLog;
use App\Models\Agent;
use App\Models\AgentAccount;
use App\Models\User;

class ActivityRecorder
{
    public function agent(ActivityAction $action, Agent $agent, User $inputer): void
    {
        ActivityLog::create([
            'inputer_id' => $inputer->id,
            'inputer_name' => $inputer->name,
            'action' => $action,
            'subject_type' => ActivitySubject::Agent,
            'subject_id' => $agent->id,
            'agent_id' => $agent->id,
            'agent_name' => $agent->name,
            'occurred_at' => now(),
        ]);
    }

    public function account(ActivityAction $action, AgentAccount $account, Agent $agent, User $inputer): void
    {
        $account->loadMissing('platform');

        $statusFrom = null;
        $statusTo = null;

        if ($action === ActivityAction::Updated && $account->wasChanged('status')) {
            $statusFrom = $account->getPrevious()['status'] ?? null;
            $statusTo = $account->status?->value;
        }

        ActivityLog::create([
            'inputer_id' => $inputer->id,
            'inputer_name' => $inputer->name,
            'action' => $action,
            'subject_type' => ActivitySubject::Account,
            'subject_id' => $account->id,
            'agent_id' => $agent->id,
            'agent_name' => $agent->name,
            'platform_id' => $account->platform_id,
            'platform_slug' => $account->platform?->slug,
            'platform_name' => $account->platform?->name,
            'identifier' => $account->identifier,
            'status_from' => $statusFrom,
            'status_to' => $statusTo,
            'occurred_at' => now(),
        ]);
    }
}
