<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['slug', 'name', 'is_kpi', 'sort'])]
class Platform extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return ['is_kpi' => 'boolean'];
    }
}
