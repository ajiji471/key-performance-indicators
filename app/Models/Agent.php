<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'name', 'avatar_path', 'email', 'recovery_email', 'password',
    'birth_date', 'address', 'latitude', 'longitude', 'job', 'hobby', 'context',
    'device_user', 'device_number', 'device_released_at', 'created_by',
])]
#[Hidden(['password'])]
class Agent extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'password'           => 'encrypted', 
            'birth_date'         => 'date:Y-m-d',
            'device_released_at' => 'date:Y-m-d',
            'latitude'           => 'decimal:7',
            'longitude'          => 'decimal:7',
        ];
    }

    public function accounts(): HasMany
    {
        return $this->hasMany(AgentAccount::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }
}