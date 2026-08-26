<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WhatsappRule extends Model
{
    protected $fillable = [
        'name', 'type', 'days_offset', 'message_template', 'is_enabled',
        'template_name', 'template_language', 'mode', 'audience',
        'duplicate_window_days', 'last_run_at', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'is_enabled' => 'boolean',
            'last_run_at' => 'datetime',
        ];
    }
}
