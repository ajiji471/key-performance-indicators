<?php

namespace App\Models;

use App\Enums\AccountStatus;
use App\Enums\ActivityAction;
use App\Enums\ActivitySubject;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'inputer_id', 'inputer_name', 'action', 'subject_type', 'subject_id',
    'agent_id', 'agent_name', 'platform_id', 'platform_slug', 'platform_name',
    'identifier', 'status_from', 'status_to', 'occurred_at',
])]
class ActivityLog extends Model
{
    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'action' => ActivityAction::class,
            'subject_type' => ActivitySubject::class,
            'status_from' => AccountStatus::class,
            'status_to' => AccountStatus::class,
            'occurred_at' => 'immutable_datetime',
        ];
    }

    public function inputer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'inputer_id');
    }
}
