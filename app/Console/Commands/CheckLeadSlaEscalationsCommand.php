<?php

namespace App\Console\Commands;

use App\Services\LeadSlaService;
use Illuminate\Console\Command;

class CheckLeadSlaEscalationsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sla:check-escalations';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check active leads for First Contact and Follow-up SLA breaches and escalate to Admins.';

    /**
     * Execute the console command.
     */
    public function handle(LeadSlaService $slaService): int
    {
        $this->info('Starting Lead SLA breach check and escalation...');

        $results = $slaService->checkAndEscalateBreaches();

        $this->info("First Contact Breaches: {$results['first_contact_breaches']}");
        $this->info("Follow-up Breaches: {$results['followup_breaches']}");
        $this->info("Repeated Missed Follow-ups: {$results['repeated_missed']}");

        $this->info('Lead SLA check completed successfully.');

        return self::SUCCESS;
    }
}
