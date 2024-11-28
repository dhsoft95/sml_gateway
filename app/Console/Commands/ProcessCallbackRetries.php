<?php

namespace App\Console\Commands;

use App\Services\NotificationService;
use Illuminate\Console\Command;

class ProcessCallbackRetries extends Command
{
    protected $signature = 'callbacks:process-retries';
    protected $description = 'Process pending callback retries';

    protected $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        parent::__construct();
        $this->notificationService = $notificationService;
    }

    public function handle()
    {
        $this->info('Processing callback retries...');
        $this->notificationService->processRetries();
        $this->info('Finished processing callback retries.');
    }
}
