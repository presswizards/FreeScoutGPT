<?php

namespace Modules\AIAssistant\Jobs;

use App\Conversation;
use App\Thread;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Modules\AIAssistant\Entities\AISettings;
use Modules\AIAssistant\Services\AIService;
use Modules\AIAssistant\Services\CustomerContextService;
use Exception;

class GenerateAutoDraft implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The conversation ID.
     */
    protected $conversationId;

    /**
     * The thread ID that triggered the auto-draft.
     */
    protected $threadId;

    /**
     * Number of retry attempts.
     */
    public $tries = 3;

    /**
     * Timeout in seconds.
     */
    public $timeout = 120;

    /**
     * Create a new job instance.
     *
     * @param int $conversationId
     * @param int $threadId
     */
    public function __construct(int $conversationId, int $threadId)
    {
        $this->conversationId = $conversationId;
        $this->threadId = $threadId;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        try {
            $conversation = Conversation::find($this->conversationId);
            if (!$conversation) {
                \Log::warning('GenerateAutoDraft: Conversation not found', [
                    'conversation_id' => $this->conversationId
                ]);
                return;
            }

            $thread = Thread::find($this->threadId);
            if (!$thread) {
                \Log::warning('GenerateAutoDraft: Thread not found', [
                    'thread_id' => $this->threadId
                ]);
                return;
            }

            // Only generate for customer messages
            if ($thread->type != Thread::TYPE_CUSTOMER) {
                return;
            }

            $settings = AISettings::find($conversation->mailbox_id);
            if (!$settings || !$settings->enabled || !$settings->auto_draft_enabled) {
                return;
            }

            // Rate limiting: Check if we recently generated a draft for this conversation
            $cacheKey = 'ai_auto_draft_' . $this->conversationId;
            if (\Cache::has($cacheKey)) {
                \Log::info('GenerateAutoDraft: Rate limited', [
                    'conversation_id' => $this->conversationId
                ]);
                return;
            }

            // Set rate limit (1 draft per 5 minutes per conversation)
            \Cache::put($cacheKey, true, now()->addMinutes(5));

            // Get customer context
            $customerContext = [];
            if ($settings->customer_history_enabled || $settings->client_data_enabled) {
                $contextService = new CustomerContextService($settings);
                $customerContext = $contextService->getContext($conversation);
            }

            // Get the customer message
            $customerMessage = $this->cleanThreadBody($thread->body);
            if (empty($customerMessage)) {
                return;
            }

            // Generate AI response
            $aiService = new AIService($settings);
            $response = $aiService->generateResponse(
                $customerMessage,
                null,
                $customerContext
            );

            // Store the draft
            $this->storeDraft($thread, $response['content'], $settings);

            \Log::info('GenerateAutoDraft: Draft generated successfully', [
                'conversation_id' => $this->conversationId,
                'thread_id' => $this->threadId
            ]);

        } catch (Exception $e) {
            \Log::error('GenerateAutoDraft: Failed to generate draft', [
                'conversation_id' => $this->conversationId,
                'thread_id' => $this->threadId,
                'error' => $e->getMessage()
            ]);

            // Re-throw to trigger retry
            throw $e;
        }
    }

    /**
     * Store the generated draft.
     *
     * @param Thread $thread
     * @param string $draft
     * @param AISettings $settings
     */
    protected function storeDraft(Thread $thread, string $draft, AISettings $settings): void
    {
        // Store draft in thread's chatgpt column (same as manual generations)
        $answers = $thread->chatgpt ? json_decode($thread->chatgpt, true) : [];
        if ($answers === null) {
            $answers = [];
        }

        // Mark as auto-draft
        $draftData = [
            'content' => trim($draft, "\n"),
            'auto_draft' => true,
            'generated_at' => now()->toDateTimeString()
        ];

        $answers[] = $draft;
        $thread->chatgpt = json_encode($answers, JSON_UNESCAPED_UNICODE);
        $thread->save();

        // Store auto-draft indicator in cache for UI notification
        $notificationKey = 'ai_draft_notification_' . $thread->conversation_id;
        \Cache::put($notificationKey, [
            'thread_id' => $thread->id,
            'draft' => $draft,
            'notification_type' => $settings->draft_notification_type,
            'created_at' => now()->toDateTimeString()
        ], now()->addHours(24));
    }

    /**
     * Clean thread body for AI processing.
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

        return $text;
    }

    /**
     * Handle a job failure.
     *
     * @param Exception $exception
     * @return void
     */
    public function failed(Exception $exception)
    {
        \Log::error('GenerateAutoDraft: Job failed permanently', [
            'conversation_id' => $this->conversationId,
            'thread_id' => $this->threadId,
            'error' => $exception->getMessage()
        ]);
    }
}
