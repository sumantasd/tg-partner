<?php

namespace App\Console\Commands;

use App\Services\InactivityService;
use Illuminate\Console\Command;

class CheckUserInactivityCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'inactivity:check-thresholds';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Scan partner and staff accounts for inactivity thresholds (30d, 60d, 90d) and dispatch deduplicated notifications.';

    /**
     * Execute the console command.
     */
    public function handle(InactivityService $inactivityService): int
    {
        $this->info('Starting Partner and Staff inactivity evaluation...');

        $results = $inactivityService->processInactivityEvaluations();

        $this->info("Total Accounts Evaluated: {$results['evaluated']}");
        $this->info("30-Day Warnings Dispatched: {$results['warnings_sent']}");
        $this->info("60-Day Inactivity Alerts Dispatched: {$results['alerts_sent']}");
        $this->info("90-Day Critical Notices Dispatched: {$results['critical_sent']}");

        $this->info('Inactivity threshold evaluation completed successfully.');

        return self::SUCCESS;
    }
}
