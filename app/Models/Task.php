<?php

namespace App\Models;

use App\Enums\TaskProgress;
use App\Enums\TaskStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['task_date', 'user_id', 'agent_id', 'platform_id', 'status', 'task_status', 'note'])]
class Task extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'task_date'   => 'date:Y-m-d',
            'status'      => TaskStatus::class,
            'task_status' => TaskProgress::class,
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class);
    }

    public function platform(): BelongsTo
    {
        return $this->belongsTo(Platform::class);
    }
}
