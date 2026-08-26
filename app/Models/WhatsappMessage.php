<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WhatsappMessage extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'rule_id', 'member_id', 'phone', 'message', 'status', 'mode',
        'provider_message_id', 'error_message', 'dedupe_key',
        'sent_by', 'sent_at', 'created_at',
    ];

    protected function casts(): array
    {
        return ['sent_at' => 'datetime', 'created_at' => 'datetime'];
    }
}
