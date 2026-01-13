<?php

namespace Modules\AIAssistant\Services;

use App\Conversation;
use App\Customer;
use App\Thread;
use Modules\AIAssistant\Entities\AISettings;
use Modules\AIAssistant\Entities\CustomerContext;
use Carbon\Carbon;
use Exception;

class CustomerContextService
{
    /**
     * The AI service instance.
     */
    protected $aiService;

    /**
     * The settings instance.
     */
    protected $settings;

    /**
     * Create a new CustomerContextService instance.
     *
     * @param AISettings $settings
     */
    public function __construct(AISettings $settings)
    {
        $this->settings = $settings;
    }

    /**
     * Get or generate customer context for a conversation.
     *
     * @param Conversation $conversation
     * @param bool $forceRefresh Force regeneration of context
     * @return array Customer context data
     */
    public function getContext(Conversation $conversation, bool $forceRefresh = false): array
    {
        // Check if customer history learning is enabled
        if (!$this->settings->customer_history_enabled) {
            return $this->getBasicContext($conversation);
        }

        $customer = $conversation->customer;
        if (!$customer) {
            return $this->getBasicContext($conversation);
        }

        // Check for existing cached context
        $cachedContext = CustomerContext::where('customer_id', $customer->id)
            ->where('mailbox_id', $conversation->mailbox_id)
            ->first();

        // Determine if refresh is needed
        $needsRefresh = $forceRefresh ||
            !$cachedContext ||
            $this->isContextStale($cachedContext);

        if ($needsRefresh) {
            return $this->generateAndCacheContext($customer, $conversation->mailbox_id);
        }

        return $this->formatCachedContext($cachedContext, $customer);
    }

    /**
     * Generate and cache customer context.
     *
     * @param Customer $customer
     * @param int $mailboxId
     * @return array
     */
    public function generateAndCacheContext(Customer $customer, int $mailboxId): array
    {
        try {
            // Fetch previous conversations
            $conversations = $this->fetchCustomerConversations($customer, $mailboxId);

            if (empty($conversations)) {
                return $this->getBasicContextFromCustomer($customer);
            }

            // Generate AI summary
            $this->aiService = new AIService($this->settings);
            $analysis = $this->aiService->summarizeCustomerHistory($conversations);

            // Save to cache
            CustomerContext::updateOrCreate(
                [
                    'customer_id' => $customer->id,
                    'mailbox_id' => $mailboxId,
                ],
                [
                    'context_summary' => $analysis['summary'] ?? '',
                    'common_issues' => json_encode($analysis['common_issues'] ?? []),
                    'communication_style' => $analysis['communication_style'] ?? 'unknown',
                    'preferences' => json_encode(['notes' => $analysis['notes'] ?? '']),
                    'last_analyzed_at' => Carbon::now(),
                ]
            );

            return [
                'name' => $customer->getFullName(),
                'email' => $customer->getMainEmail(),
                'history_summary' => $analysis['summary'] ?? '',
                'common_issues' => $analysis['common_issues'] ?? [],
                'communication_style' => $analysis['communication_style'] ?? 'unknown',
                'notes' => $analysis['notes'] ?? '',
                'has_history' => true,
            ];
        } catch (Exception $e) {
            \Log::error('CustomerContextService: Failed to generate context', [
                'customer_id' => $customer->id,
                'error' => $e->getMessage()
            ]);

            return $this->getBasicContextFromCustomer($customer);
        }
    }

    /**
     * Fetch customer's previous conversations.
     *
     * @param Customer $customer
     * @param int $mailboxId
     * @return array
     */
    protected function fetchCustomerConversations(Customer $customer, int $mailboxId): array
    {
        $depth = $this->settings->history_depth ?? 10;

        $conversations = Conversation::where('customer_id', $customer->id)
            ->where('mailbox_id', $mailboxId)
            ->where('status', Conversation::STATUS_CLOSED)
            ->orderBy('created_at', 'desc')
            ->limit($depth)
            ->get();

        $result = [];

        foreach ($conversations as $conv) {
            $threads = Thread::where('conversation_id', $conv->id)
                ->whereIn('type', [Thread::TYPE_CUSTOMER, Thread::TYPE_MESSAGE])
                ->orderBy('created_at', 'asc')
                ->limit(10) // Limit threads per conversation
                ->get();

            $messages = [];
            foreach ($threads as $thread) {
                $messages[] = [
                    'type' => $thread->type == Thread::TYPE_CUSTOMER ? 'customer' : 'agent',
                    'body' => $this->cleanThreadBody($thread->body),
                    'created_at' => $thread->created_at->toDateTimeString(),
                ];
            }

            $result[] = [
                'id' => $conv->id,
                'subject' => $conv->subject,
                'status' => $this->getStatusLabel($conv->status),
                'created_at' => $conv->created_at->toDateTimeString(),
                'messages' => $messages,
            ];
        }

        return $result;
    }

    /**
     * Check if cached context is stale.
     *
     * @param CustomerContext $context
     * @return bool
     */
    protected function isContextStale(CustomerContext $context): bool
    {
        if (!$context->last_analyzed_at) {
            return true;
        }

        $interval = $this->settings->context_refresh_interval ?? 'weekly';
        $lastAnalyzed = Carbon::parse($context->last_analyzed_at);

        switch ($interval) {
            case 'daily':
                return $lastAnalyzed->diffInDays(Carbon::now()) >= 1;
            case 'weekly':
                return $lastAnalyzed->diffInWeeks(Carbon::now()) >= 1;
            case 'monthly':
                return $lastAnalyzed->diffInMonths(Carbon::now()) >= 1;
            default:
                return $lastAnalyzed->diffInWeeks(Carbon::now()) >= 1;
        }
    }

    /**
     * Format cached context for use.
     *
     * @param CustomerContext $context
     * @param Customer $customer
     * @return array
     */
    protected function formatCachedContext(CustomerContext $context, Customer $customer): array
    {
        $commonIssues = json_decode($context->common_issues, true) ?? [];
        $preferences = json_decode($context->preferences, true) ?? [];

        return [
            'name' => $customer->getFullName(),
            'email' => $customer->getMainEmail(),
            'history_summary' => $context->context_summary ?? '',
            'common_issues' => $commonIssues,
            'communication_style' => $context->communication_style ?? 'unknown',
            'notes' => $preferences['notes'] ?? '',
            'has_history' => true,
            'last_analyzed' => $context->last_analyzed_at,
        ];
    }

    /**
     * Get basic context without history analysis.
     *
     * @param Conversation $conversation
     * @return array
     */
    protected function getBasicContext(Conversation $conversation): array
    {
        $customer = $conversation->customer;

        if (!$customer) {
            return [
                'name' => '',
                'email' => '',
                'has_history' => false,
            ];
        }

        return $this->getBasicContextFromCustomer($customer);
    }

    /**
     * Get basic context from customer model.
     *
     * @param Customer $customer
     * @return array
     */
    protected function getBasicContextFromCustomer(Customer $customer): array
    {
        return [
            'name' => $customer->getFullName(),
            'email' => $customer->getMainEmail(),
            'has_history' => false,
        ];
    }

    /**
     * Clean thread body for analysis.
     *
     * @param string|null $body
     * @return string
     */
    protected function cleanThreadBody(?string $body): string
    {
        if (empty($body)) {
            return '';
        }

        // Remove HTML tags
        $text = strip_tags($body);

        // Remove quoted replies
        $text = preg_replace('/On .* wrote:[\s\S]*/i', '', $text);
        $text = preg_replace('/-----Original Message-----[\s\S]*/i', '', $text);

        // Clean up whitespace
        $text = preg_replace('/\s+/', ' ', $text);
        $text = trim($text);

        // Truncate if too long
        if (strlen($text) > 2000) {
            $text = substr($text, 0, 2000) . '...';
        }

        return $text;
    }

    /**
     * Get human-readable status label.
     *
     * @param int $status
     * @return string
     */
    protected function getStatusLabel(int $status): string
    {
        $labels = [
            Conversation::STATUS_ACTIVE => 'Active',
            Conversation::STATUS_PENDING => 'Pending',
            Conversation::STATUS_CLOSED => 'Closed',
            Conversation::STATUS_SPAM => 'Spam',
        ];

        return $labels[$status] ?? 'Unknown';
    }

    /**
     * Refresh context for a specific customer.
     *
     * @param int $customerId
     * @param int $mailboxId
     * @return array
     */
    public function refreshContext(int $customerId, int $mailboxId): array
    {
        $customer = Customer::find($customerId);

        if (!$customer) {
            throw new Exception('Customer not found');
        }

        return $this->generateAndCacheContext($customer, $mailboxId);
    }

    /**
     * Delete cached context for a customer.
     *
     * @param int $customerId
     * @param int|null $mailboxId
     * @return bool
     */
    public function deleteContext(int $customerId, ?int $mailboxId = null): bool
    {
        $query = CustomerContext::where('customer_id', $customerId);

        if ($mailboxId) {
            $query->where('mailbox_id', $mailboxId);
        }

        return $query->delete() > 0;
    }

    /**
     * Get context statistics for a mailbox.
     *
     * @param int $mailboxId
     * @return array
     */
    public function getStats(int $mailboxId): array
    {
        $total = CustomerContext::where('mailbox_id', $mailboxId)->count();

        $stale = CustomerContext::where('mailbox_id', $mailboxId)
            ->where('last_analyzed_at', '<', Carbon::now()->subWeek())
            ->count();

        return [
            'total_contexts' => $total,
            'stale_contexts' => $stale,
            'fresh_contexts' => $total - $stale,
        ];
    }
}
