<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Pipeline extends Model
{
    protected $fillable = [
        'repository_id',
        'workflow_name',
        'status',
        'branch',
        'duration',
        'run_at',
    ];

    protected $casts = [
        'run_at' => 'datetime',
        'duration' => 'integer',
    ];

    public function repository(): BelongsTo
    {
        return $this->belongsTo(Repository::class);
    }
}
