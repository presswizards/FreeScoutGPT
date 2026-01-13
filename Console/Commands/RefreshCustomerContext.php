<?php

namespace Modules\AIAssistant\Console\Commands;

use Illuminate\Console\Command;
use App\Mailbox;
use App\Customer;
use Modules\AIAssistant\Entities\AISettings;
use Modules\AIAssistant\Entities\CustomerContext;
use Modules\AIAssistant\Services\CustomerContextService;
use Exception;

class RefreshCustomerContext extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'aiassistant:refresh-context
                            {--mailbox= : Specific mailbox ID to refresh}
                            {--customer= : Specific customer ID to refresh}
                            {--stale-only : Only refresh stale contexts}
                            {--force : Force refresh even if not stale}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Refresh AI customer context analysis for all or specific customers';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $mailboxId = $this->option('mailbox');
        $customerId = $this->option('customer');
        $staleOnly = $this->option('stale-only');
        $force = $this->option('force');

        $this->info('AI Assistant - Customer Context Refresh');
        $this->info('=========================================');

        // Get mailboxes to process
        if ($mailboxId) {
            $mailboxes = Mailbox::where('id', $mailboxId)->get();
            if ($mailboxes->isEmpty()) {
                $this->error("Mailbox ID {$mailboxId} not found.");
                return 1;
            }
        } else {
            $mailboxes = Mailbox::all();
        }

        $totalRefreshed = 0;
        $totalSkipped = 0;
        $totalFailed = 0;

        foreach ($mailboxes as $mailbox) {
            $settings = AISettings::find($mailbox->id);

            // Skip if module not enabled or customer history not enabled
            if (!$settings || !$settings->enabled || !$settings->customer_history_enabled) {
                $this->line("Skipping mailbox '{$mailbox->name}' - AI Assistant not enabled or customer history disabled");
                continue;
            }

            $this->info("Processing mailbox: {$mailbox->name}");

            // Get contexts to refresh
            $query = CustomerContext::where('mailbox_id', $mailbox->id);

            if ($customerId) {
                $query->where('customer_id', $customerId);
            }

            if ($staleOnly && !$force) {
                $interval = $settings->context_refresh_interval ?? 'weekly';
                $query->stale($interval);
            }

            $contexts = $query->get();

            // If specific customer but no context exists, create one
            if ($customerId && $contexts->isEmpty()) {
                $customer = Customer::find($customerId);
                if ($customer) {
                    $this->refreshCustomerContext($customer, $mailbox->id, $settings, $force);
                    $totalRefreshed++;
                } else {
                    $this->error("Customer ID {$customerId} not found.");
                }
                continue;
            }

            $progressBar = $this->output->createProgressBar($contexts->count());
            $progressBar->start();

            foreach ($contexts as $context) {
                try {
                    $customer = Customer::find($context->customer_id);
                    if (!$customer) {
                        $this->line("\n  Skipping deleted customer ID: {$context->customer_id}");
                        $totalSkipped++;
                        $progressBar->advance();
                        continue;
                    }

                    if (!$force && !$context->isStale($settings->context_refresh_interval ?? 'weekly')) {
                        $totalSkipped++;
                        $progressBar->advance();
                        continue;
                    }

                    $this->refreshCustomerContext($customer, $mailbox->id, $settings, $force);
                    $totalRefreshed++;

                } catch (Exception $e) {
                    $this->error("\n  Error refreshing context for customer {$context->customer_id}: {$e->getMessage()}");
                    $totalFailed++;
                }

                $progressBar->advance();
            }

            $progressBar->finish();
            $this->newLine();
        }

        $this->newLine();
        $this->info('=========================================');
        $this->info("Refresh complete!");
        $this->line("  Refreshed: {$totalRefreshed}");
        $this->line("  Skipped: {$totalSkipped}");
        $this->line("  Failed: {$totalFailed}");

        return 0;
    }

    /**
     * Refresh context for a specific customer.
     *
     * @param Customer $customer
     * @param int $mailboxId
     * @param AISettings $settings
     * @param bool $force
     */
    protected function refreshCustomerContext(Customer $customer, int $mailboxId, AISettings $settings, bool $force): void
    {
        $contextService = new CustomerContextService($settings);
        $contextService->generateAndCacheContext($customer, $mailboxId);
    }
}
