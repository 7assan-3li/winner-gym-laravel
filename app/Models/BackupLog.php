<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BackupLog extends Model
{
    protected $fillable = [
        'file_name',
        'filename',
        'storage_path',
        'disk',
        'path',
        'size_bytes',
        'status',
        'started_at',
        'completed_at',
        'initiated_by',
        'created_by',
        'restored_by',
        'restored_at',
        'error_message',
    ];

    protected function casts(): array
    {
        return [
            'size_bytes' => 'integer',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'restored_at' => 'datetime',
        ];
    }
}
