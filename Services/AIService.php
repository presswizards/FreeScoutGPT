<?php

namespace Modules\AIAssistant\Services;

use Modules\AIAssistant\Entities\AISettings;
use Tectalic\OpenAi\Client;
use Tectalic\OpenAi\Manager;
use GuzzleHttp\Client as GuzzleClient;
use Exception;

class AIService
{
    /**
     * The AI settings for the current mailbox.
     */
    protected $settings;

    /**
     * The OpenAI client instance.
     */
    protected $client;

    /**
     * Create a new AIService instance.
     *
     * @param AISettings $settings
     */
    public function __construct(AISettings $settings)
    {
        $this->settings = $settings;
        $this->initializeClient();
    }

    /**
     * Initialize the OpenAI client.
     */
    protected function initializeClient()
    {
        if (!$this->settings->api_key) {
            throw new Exception('OpenAI API key is not configured');
        }

        $this->client = Manager::build(
            new GuzzleClient(),
            new \Tectalic\OpenAi\Authentication($this->settings->api_key)
        );
    }

    /**
     * Generate a response using Chat Completions API.
     *
     * @param string $userMessage The user/customer message
     * @param string|null $systemPrompt Custom system prompt (optional)
     * @param array $customerContext Additional customer context
     * @param string|null $editCommand Command for editing/improving existing text
     * @return array Response with 'content' and 'usage' keys
     */
    public function generateResponse(
        string $userMessage,
        ?string $systemPrompt = null,
        array $customerContext = [],
        ?string $editCommand = null
    ): array {
        $model = $this->settings->model ?? 'gpt-4o-mini';
        $tokenLimit = $this->settings->token_limit ?? 1024;

        // Build the system prompt
        $finalSystemPrompt = $this->buildSystemPrompt($systemPrompt, $customerContext);

        // Determine role based on model type (o1/o3 models use 'user' role)
        $systemRole = $this->isReasoningModel($model) ? 'user' : 'developer';

        $messages = [];

        // Add system/developer message
        if ($finalSystemPrompt) {
            $messages[] = [
                'role' => $systemRole,
                'content' => $finalSystemPrompt
            ];
        }

        // Add edit command if provided
        if ($editCommand) {
            $messages[] = [
                'role' => 'user',
                'content' => $editCommand . "\n\n" . $userMessage
            ];
        } else {
            $messages[] = [
                'role' => 'user',
                'content' => $userMessage
            ];
        }

        try {
            $response = $this->client->chatCompletions()->create(
                new \Tectalic\OpenAi\Models\ChatCompletions\CreateRequest([
                    'model' => $model,
                    'max_completion_tokens' => $tokenLimit,
                    'messages' => $messages,
                ])
            )->toModel();

            return [
                'content' => $response->choices[0]->message->content ?? '',
                'usage' => [
                    'prompt_tokens' => $response->usage->prompt_tokens ?? 0,
                    'completion_tokens' => $response->usage->completion_tokens ?? 0,
                    'total_tokens' => $response->usage->total_tokens ?? 0,
                ],
                'model' => $model,
            ];
        } catch (Exception $e) {
            throw new Exception('AI generation failed: ' . $e->getMessage());
        }
    }

    /**
     * Generate a response using the Responses API with web search.
     *
     * @param string $userMessage
     * @param array $articleUrls URLs to fetch for context
     * @param string|null $customPrompt
     * @return array
     */
    public function generateWithArticles(
        string $userMessage,
        array $articleUrls = [],
        ?string $customPrompt = null
    ): array {
        $model = $this->settings->model ?? 'gpt-4o-mini';

        // Fetch and process articles
        $articlesContext = $this->fetchArticlesContent($articleUrls);

        // Build the full prompt
        $prompt = $customPrompt ?? $this->settings->responses_api_prompt ?? $this->getDefaultResponsesApiPrompt();

        $fullPrompt = "Articles for reference:\n\n" . $articlesContext . "\n\n" . $prompt;

        return $this->generateResponse($userMessage, $fullPrompt);
    }

    /**
     * Summarize customer conversation history.
     *
     * @param array $conversations Array of conversation data
     * @return array JSON decoded summary
     */
    public function summarizeCustomerHistory(array $conversations): array
    {
        $analysisModel = $this->settings->analysis_model ?? 'gpt-4o-mini';

        $prompt = $this->getHistorySummarizationPrompt();

        $conversationText = $this->formatConversationsForAnalysis($conversations);

        try {
            // Temporarily override model for analysis
            $originalModel = $this->settings->model;
            $this->settings->model = $analysisModel;

            $response = $this->generateResponse(
                $conversationText,
                $prompt
            );

            $this->settings->model = $originalModel;

            // Parse JSON response
            $content = $response['content'];

            // Try to extract JSON from the response
            if (preg_match('/\{[\s\S]*\}/', $content, $matches)) {
                $parsed = json_decode($matches[0], true);
                if ($parsed) {
                    return $parsed;
                }
            }

            // Fallback if JSON parsing fails
            return [
                'summary' => $content,
                'common_issues' => [],
                'communication_style' => 'unknown',
                'notes' => ''
            ];
        } catch (Exception $e) {
            return [
                'summary' => 'Unable to analyze customer history',
                'common_issues' => [],
                'communication_style' => 'unknown',
                'notes' => 'Error: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Adjust the tone of existing text.
     *
     * @param string $text The text to adjust
     * @param string $tone The desired tone (formal, casual, empathetic, shorter, longer)
     * @return string
     */
    public function adjustTone(string $text, string $tone): string
    {
        $tonePrompts = [
            'formal' => 'Rewrite the following text to be more formal and professional, maintaining the same meaning:',
            'casual' => 'Rewrite the following text to be more casual and friendly, maintaining the same meaning:',
            'empathetic' => 'Rewrite the following text to be more empathetic and understanding, showing care for the customer\'s situation:',
            'shorter' => 'Make the following text more concise while keeping the key information:',
            'longer' => 'Expand the following text with more detail and explanation while maintaining the same tone:',
        ];

        $prompt = $tonePrompts[$tone] ?? $tonePrompts['formal'];

        $response = $this->generateResponse($text, $prompt);

        return $response['content'];
    }

    /**
     * Get available models from OpenAI API.
     *
     * @return array
     */
    public function getAvailableModels(): array
    {
        try {
            $response = $this->client->models()->list()->toModel();

            $models = [];
            $allowedPrefixes = ['gpt-4', 'gpt-3.5', 'gpt-5', 'o1', 'o3', 'chatgpt'];
            $excludedPatterns = ['whisper', 'embedding', 'dall-e', 'tts', 'audio', 'moderation', 'davinci', 'babbage', 'curie'];

            foreach ($response->data as $model) {
                $id = $model->id;

                // Check if model matches allowed prefixes
                $allowed = false;
                foreach ($allowedPrefixes as $prefix) {
                    if (stripos($id, $prefix) === 0) {
                        $allowed = true;
                        break;
                    }
                }

                if (!$allowed) {
                    continue;
                }

                // Check if model matches excluded patterns
                foreach ($excludedPatterns as $pattern) {
                    if (stripos($id, $pattern) !== false) {
                        $allowed = false;
                        break;
                    }
                }

                if ($allowed) {
                    $models[] = $id;
                }
            }

            sort($models);
            return $models;
        } catch (Exception $e) {
            // Return default models on error
            return ['gpt-4o', 'gpt-4o-mini', 'gpt-4-turbo', 'gpt-3.5-turbo'];
        }
    }

    /**
     * Build the complete system prompt.
     */
    protected function buildSystemPrompt(?string $customPrompt, array $customerContext): string
    {
        $basePrompt = $customPrompt ?? $this->settings->start_message ?? '';

        if (empty($customerContext)) {
            return $basePrompt;
        }

        $contextSection = "\n\nCUSTOMER CONTEXT:\n";

        if (!empty($customerContext['name'])) {
            $contextSection .= "- Customer Name: {$customerContext['name']}\n";
        }

        if (!empty($customerContext['email'])) {
            $contextSection .= "- Email: {$customerContext['email']}\n";
        }

        if (!empty($customerContext['history_summary'])) {
            $contextSection .= "\nCUSTOMER HISTORY:\n{$customerContext['history_summary']}\n";
        }

        if (!empty($customerContext['common_issues'])) {
            $issues = is_array($customerContext['common_issues'])
                ? implode(', ', $customerContext['common_issues'])
                : $customerContext['common_issues'];
            $contextSection .= "- Previous Issues: {$issues}\n";
        }

        if (!empty($customerContext['communication_style'])) {
            $contextSection .= "- Communication Style: {$customerContext['communication_style']}\n";
        }

        if (!empty($customerContext['notes'])) {
            $contextSection .= "- Special Notes: {$customerContext['notes']}\n";
        }

        return $basePrompt . $contextSection;
    }

    /**
     * Check if model is a reasoning model (o1/o3 series).
     */
    protected function isReasoningModel(string $model): bool
    {
        return preg_match('/^o[13]/', $model) === 1;
    }

    /**
     * Fetch content from article URLs.
     */
    protected function fetchArticlesContent(array $urls): string
    {
        $content = '';
        $maxCharsPerUrl = 12000;

        foreach ($urls as $url) {
            $url = trim($url);
            if (empty($url)) {
                continue;
            }

            try {
                $html = $this->fetchUrl($url);
                $text = $this->htmlToText($html);
                $text = substr($text, 0, $maxCharsPerUrl);
                $content .= "--- Article: {$url} ---\n{$text}\n\n";
            } catch (Exception $e) {
                $content .= "--- Article: {$url} (failed to fetch) ---\n\n";
            }
        }

        return $content;
    }

    /**
     * Fetch URL content.
     */
    protected function fetchUrl(string $url): string
    {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_USERAGENT => 'Mozilla/5.0 (compatible; AIAssistant/1.0)',
        ]);
        $response = curl_exec($ch);
        curl_close($ch);

        return $response ?: '';
    }

    /**
     * Convert HTML to plain text.
     */
    protected function htmlToText(string $html): string
    {
        // Remove script and style elements
        $html = preg_replace('/<script[^>]*>.*?<\/script>/is', '', $html);
        $html = preg_replace('/<style[^>]*>.*?<\/style>/is', '', $html);

        // Convert common HTML entities
        $html = html_entity_decode($html);

        // Strip remaining tags
        $text = strip_tags($html);

        // Clean up whitespace
        $text = preg_replace('/\s+/', ' ', $text);
        $text = trim($text);

        return $text;
    }

    /**
     * Get the default Responses API prompt.
     */
    protected function getDefaultResponsesApiPrompt(): string
    {
        return "If relevant given the customer's query, and the articles included, find the single article that best answers the user's question. Summarize the relevant part of that article as a support answer, and provide the article URL. If no article is relevant, reply with a concise best attempt to answer their concerns.";
    }

    /**
     * Get the prompt for summarizing customer history.
     */
    protected function getHistorySummarizationPrompt(): string
    {
        $customTemplate = $this->settings->context_prompt_template ?? '';

        if (!empty($customTemplate)) {
            return $customTemplate;
        }

        return <<<PROMPT
Analyze these customer support conversations and provide:
1. A brief summary of the customer's history (2-3 sentences)
2. Common issues they've had (bullet points)
3. Their communication style (formal/casual/technical/frustrated)
4. Any preferences or special notes worth remembering

Respond in JSON format:
{
  "summary": "...",
  "common_issues": ["...", "..."],
  "communication_style": "...",
  "notes": "..."
}
PROMPT;
    }

    /**
     * Format conversations for analysis.
     */
    protected function formatConversationsForAnalysis(array $conversations): string
    {
        $text = "Customer Support Conversation History:\n\n";

        foreach ($conversations as $index => $conv) {
            $text .= "--- Conversation " . ($index + 1) . " ---\n";
            $text .= "Subject: " . ($conv['subject'] ?? 'N/A') . "\n";
            $text .= "Status: " . ($conv['status'] ?? 'N/A') . "\n";
            $text .= "Date: " . ($conv['created_at'] ?? 'N/A') . "\n";

            if (!empty($conv['messages'])) {
                foreach ($conv['messages'] as $msg) {
                    $role = $msg['type'] === 'customer' ? 'Customer' : 'Agent';
                    $text .= "{$role}: {$msg['body']}\n";
                }
            }

            $text .= "\n";
        }

        return $text;
    }

    /**
     * Get settings.
     */
    public function getSettings(): AISettings
    {
        return $this->settings;
    }
}
