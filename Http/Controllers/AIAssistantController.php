<?php

namespace Modules\AIAssistant\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use App\Thread;
use App\Mailbox;
use App\Conversation;
use Modules\AIAssistant\Entities\AISettings;
use Modules\AIAssistant\Services\AIService;
use Modules\AIAssistant\Services\CustomerContextService;
use Exception;

class AIAssistantController extends Controller
{
    /**
     * Generate AI response for a thread.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function generate(Request $request)
    {
        if (Auth::user() === null) {
            return Response::json(["error" => "Unauthorized"], 401);
        }

        try {
            $settings = AISettings::findOrFail($request->get("mailbox_id"));

            // Get customer context if enabled
            $customerContext = [];
            if ($settings->client_data_enabled || $settings->customer_history_enabled) {
                $customerContext = $this->getCustomerContext($request, $settings);
            }

            // Check for edit command (custom prompt modification)
            $command = $request->get("command");

            // If Responses API is enabled and not an edit command, use it
            if (!empty($settings->use_responses_api) && empty($command)) {
                return $this->generateWithResponsesApi($request, $settings, $customerContext);
            }

            // Use Chat Completions API
            return $this->generateWithChatCompletions($request, $settings, $customerContext, $command);

        } catch (Exception $e) {
            \Log::error('AIAssistant generate error: ' . $e->getMessage());
            return Response::json([
                'error' => $e->getMessage(),
                'answer' => 'Error generating response: ' . $e->getMessage()
            ], 200);
        }
    }

    /**
     * Generate response using Chat Completions API.
     */
    protected function generateWithChatCompletions(
        Request $request,
        AISettings $settings,
        array $customerContext,
        ?string $command
    ) {
        $aiService = new AIService($settings);

        $userQuery = $request->get('query');

        $response = $aiService->generateResponse(
            $userQuery,
            $command, // Use command as custom prompt if provided
            $customerContext
        );

        // Save answer to thread
        $this->saveAnswerToThread($request->get('thread_id'), $response['content']);

        return Response::json([
            'query' => $userQuery,
            'answer' => $response['content'],
            'usage' => $response['usage'] ?? null
        ], 200);
    }

    /**
     * Generate response using Responses API with articles.
     */
    protected function generateWithResponsesApi(
        Request $request,
        AISettings $settings,
        array $customerContext
    ) {
        $articleUrls = array_filter(
            array_map('trim', preg_split('/\r?\n/', $settings->article_urls ?? ''))
        );

        $aiService = new AIService($settings);
        $userQuery = $request->get('query');

        // Build context string
        $contextString = '';
        if (!empty($customerContext)) {
            if (!empty($customerContext['name'])) {
                $contextString .= "Customer: {$customerContext['name']}\n";
            }
            if (!empty($customerContext['email'])) {
                $contextString .= "Email: {$customerContext['email']}\n";
            }
            if (!empty($customerContext['history_summary'])) {
                $contextString .= "History: {$customerContext['history_summary']}\n";
            }
        }

        $conversationSubject = $request->get('conversation_subject');
        if ($conversationSubject) {
            $contextString .= "Subject: {$conversationSubject}\n";
        }

        $fullQuery = $contextString . "\nCustomer Query: " . $userQuery;

        try {
            $response = $aiService->generateWithArticles(
                $fullQuery,
                $articleUrls,
                $settings->responses_api_prompt
            );

            $this->saveAnswerToThread($request->get('thread_id'), $response['content']);

            return Response::json([
                'query' => $userQuery,
                'answer' => $response['content'],
                'usage' => $response['usage'] ?? null
            ], 200);

        } catch (Exception $e) {
            return Response::json([
                'query' => $userQuery,
                'answer' => 'Error: ' . $e->getMessage()
            ], 200);
        }
    }

    /**
     * Adjust the tone of text.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function adjustTone(Request $request)
    {
        if (Auth::user() === null) {
            return Response::json(["error" => "Unauthorized"], 401);
        }

        try {
            $settings = AISettings::findOrFail($request->get("mailbox_id"));
            $aiService = new AIService($settings);

            $text = $request->get('text');
            $tone = $request->get('tone', 'formal');

            $adjustedText = $aiService->adjustTone($text, $tone);

            return Response::json([
                'original' => $text,
                'adjusted' => $adjustedText,
                'tone' => $tone
            ], 200);

        } catch (Exception $e) {
            return Response::json([
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Refresh customer context.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function refreshCustomerContext(Request $request)
    {
        if (Auth::user() === null) {
            return Response::json(["error" => "Unauthorized"], 401);
        }

        try {
            $settings = AISettings::findOrFail($request->get("mailbox_id"));
            $contextService = new CustomerContextService($settings);

            $context = $contextService->refreshContext(
                $request->get('customer_id'),
                $request->get('mailbox_id')
            );

            return Response::json([
                'success' => true,
                'context' => $context
            ], 200);

        } catch (Exception $e) {
            return Response::json([
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get customer context for the current conversation.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getCustomerContextApi(Request $request)
    {
        if (Auth::user() === null) {
            return Response::json(["error" => "Unauthorized"], 401);
        }

        try {
            $conversationId = $request->get('conversation_id');
            $conversation = Conversation::findOrFail($conversationId);
            $settings = AISettings::find($conversation->mailbox_id);

            if (!$settings) {
                return Response::json(['context' => []], 200);
            }

            $contextService = new CustomerContextService($settings);
            $context = $contextService->getContext($conversation);

            return Response::json([
                'context' => $context
            ], 200);

        } catch (Exception $e) {
            return Response::json([
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get all answers for a conversation.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function answers(Request $request)
    {
        if (Auth::user() === null) {
            return Response::json(["error" => "Unauthorized"], 401);
        }

        $conversation = $request->query('conversation');
        $threads = Thread::where("conversation_id", $conversation)->get();
        $result = [];

        foreach ($threads as $thread) {
            if ($thread->chatgpt !== "{}" && $thread->chatgpt !== null) {
                $answers_text = json_decode($thread->chatgpt, true);
                if ($answers_text === null) continue;

                $result[] = [
                    "thread" => $thread->id,
                    "answers" => $answers_text
                ];
            }
        }

        return Response::json(["answers" => $result], 200);
    }

    /**
     * Display the settings page.
     *
     * @param int $mailbox_id
     * @return \Illuminate\View\View
     */
    public function settings($mailbox_id)
    {
        $mailbox = Mailbox::findOrFail($mailbox_id);
        $settings = AISettings::getWithDefaults($mailbox_id);

        return view('aiassistant::settings', [
            'mailbox' => $mailbox,
            'settings' => $settings
        ]);
    }

    /**
     * Save settings.
     *
     * @param int $mailbox_id
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function saveSettings($mailbox_id, Request $request)
    {
        AISettings::updateOrCreate(
            ['mailbox_id' => $mailbox_id],
            [
                'api_key' => $request->get("api_key"),
                'enabled' => $request->has('gpt_enabled'),
                'token_limit' => $request->get('token_limit'),
                'start_message' => $request->get('start_message'),
                'model' => $request->get('model'),
                'client_data_enabled' => $request->has('show_client_data_enabled'),
                'use_responses_api' => $request->has('use_responses_api'),
                'article_urls' => $request->get('article_urls'),
                'responses_api_prompt' => $request->get('responses_api_prompt'),
                // New customer history settings
                'customer_history_enabled' => $request->has('customer_history_enabled'),
                'history_depth' => $request->get('history_depth', 10),
                'context_refresh_interval' => $request->get('context_refresh_interval', 'weekly'),
                'analysis_model' => $request->get('analysis_model', 'gpt-4o-mini'),
                'context_prompt_template' => $request->get('context_prompt_template'),
                'auto_draft_enabled' => $request->has('auto_draft_enabled'),
                'draft_notification_type' => $request->get('draft_notification_type', 'banner'),
            ]
        );

        \Session::flash('flash_success_floating', __('Settings updated'));
        return redirect()->route('aiassistant.settings', ['mailbox_id' => $mailbox_id]);
    }

    /**
     * Check if module is enabled for a mailbox.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function checkIsEnabled(Request $request)
    {
        $settings = AISettings::find($request->query("mailbox"));

        if (empty($settings)) {
            return Response::json(['enabled' => false], 200);
        }

        return Response::json([
            'enabled' => (bool) $settings->enabled,
            'customer_history_enabled' => (bool) ($settings->customer_history_enabled ?? false),
            'auto_draft_enabled' => (bool) ($settings->auto_draft_enabled ?? false)
        ], 200);
    }

    /**
     * Get available OpenAI models.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getAvailableModels(Request $request)
    {
        $apiKey = $request->input('api_key');

        if (!$apiKey) {
            return Response::json(['error' => 'API key is required'], 400);
        }

        $cacheKey = 'openai_models_' . md5($apiKey);

        // Check cache first
        if (Cache::has($cacheKey)) {
            return Response::json(['data' => Cache::get($cacheKey)]);
        }

        try {
            $client = new \GuzzleHttp\Client();
            $response = $client->get('https://api.openai.com/v1/models', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $apiKey,
                    'Accept' => 'application/json',
                ],
            ]);

            $models = json_decode($response->getBody(), true);

            // Filter to only chat-capable models
            $filteredModels = array_filter($models['data'], function ($model) {
                $nonChatPatterns = [
                    'search', 'transcribe', 'realtime', 'whisper', 'babbage',
                    'davinci', 'curie', 'text-to-speech', 'dall-e', '-audio',
                    'tts', 'embedding', '2024', '2025'
                ];

                foreach ($nonChatPatterns as $pattern) {
                    if (strpos($model['id'], $pattern) !== false) {
                        return false;
                    }
                }

                // Include modern GPT and O-series models
                if (preg_match('/^(gpt-4o|gpt-4\.5|gpt-4\.1|gpt-5|o[1-4])/', $model['id'])) {
                    return true;
                }

                return false;
            });

            // Sort alphabetically
            usort($filteredModels, function($a, $b) {
                return strcmp($a['id'], $b['id']);
            });

            // Cache for 10 minutes
            Cache::put($cacheKey, $filteredModels, now()->addMinutes(10));

            return Response::json(['data' => $filteredModels]);

        } catch (Exception $e) {
            return Response::json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Get customer context from request.
     */
    protected function getCustomerContext(Request $request, AISettings $settings): array
    {
        $context = [];

        // Basic client data
        if ($settings->client_data_enabled) {
            $context['name'] = $request->get('customer_name', '');
            $context['email'] = $request->get('customer_email', '');
        }

        // Customer history context
        if ($settings->customer_history_enabled) {
            $conversationId = $request->get('conversation_id');
            if ($conversationId) {
                try {
                    $conversation = Conversation::find($conversationId);
                    if ($conversation) {
                        $contextService = new CustomerContextService($settings);
                        $historyContext = $contextService->getContext($conversation);
                        $context = array_merge($context, $historyContext);
                    }
                } catch (Exception $e) {
                    \Log::warning('Failed to get customer context: ' . $e->getMessage());
                }
            }
        }

        return $context;
    }

    /**
     * Save answer to thread.
     */
    protected function saveAnswerToThread(int $threadId, string $answer): void
    {
        $thread = Thread::find($threadId);
        if (!$thread) {
            return;
        }

        $answers = $thread->chatgpt ? json_decode($thread->chatgpt, true) : [];
        if ($answers === null) {
            $answers = [];
        }

        $answers[] = trim($answer, "\n");
        $thread->chatgpt = json_encode($answers, JSON_UNESCAPED_UNICODE);
        $thread->save();
    }
}
