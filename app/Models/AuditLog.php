<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'user',
        'action',
        'entity',
        'entity_id',
        'changes',
    ];

    protected $casts = [
        'changes' => 'array',
    ];
}