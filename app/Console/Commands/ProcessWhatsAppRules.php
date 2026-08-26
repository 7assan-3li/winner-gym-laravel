<?php

namespace App\Console\Commands;

use App\Services\WhatsAppService;
use Illuminate\Console\Command;

class ProcessWhatsAppRules extends Command
{
    protected $signature = 'winner-gym:whatsapp-process';

    protected $description = 'Process enabled automatic WhatsApp rules';

    public function handle(WhatsAppService $whatsApp): int
    {
        if (! $whatsApp->configured()) {
            $this->warn('WhatsApp Cloud API is not configured.');

            return self::SUCCESS;
        }

        $sent = $whatsApp->runAutoRules();
        $this->info("Messages sent: {$sent}");

        return self::SUCCESS;
    }
}
