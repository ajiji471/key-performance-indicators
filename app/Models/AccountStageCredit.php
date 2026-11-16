<?php

namespace App\Models;

use App\Enums\AccountStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['agent_id', 'platform_id', 'platform_slug', 'stage', 'inputer_id', 'achieved_at'])]
class AccountStageCredit extends Model
{
    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'stage' => AccountStatus::class,
            'achieved_at' => 'immutable_datetime',
        ];
    }

    public function inputer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'inputer_id');
    }
}
