<?php

namespace Modules\FreeScoutGPT\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use App\Thread;
use App\Mailbox;
use Modules\FreeScoutGPT\Entities\GPTSettings;

class FreeScoutGPTController extends Controller
{

    /**
     * Display a listing of the resource.
     * @return Response
     */
    public function index()
    {
        return view('freescoutgpt::index');
    }

    /**
     * Show the form for creating a new resource.
     * @return Response
     */
    public function create()
    {
        return view('freescoutgpt::create');
    }

    /**
     * Store a newly created resource in storage.
     * @param  Request $request
     * @return Response
     */
    public function store(Request $request)
    {
    }

    /**
     * Show the specified resource.
     * @return Response
     */
    public function show()
    {
        return view('freescoutgpt::show');
    }

    /**
     * Show the form for editing the specified resource.
     * @return Response
     */
    public function edit()
    {
        return view('freescoutgpt::edit');
    }

    /**
     * Update the specified resource in storage.
     * @param  Request $request
     * @return Response
     */
    public function update(Request $request)
    {
    }

    /**
     * Remove the specified resource from storage.
     * @return Response
     */
    public function destroy()
    {
    }

    public function getAvailableModels(Request $request)
    {
        $apiKey = $request->input('api_key');

        if (!$apiKey) {
            return response()->json(['error' => 'API key is required'], 400);
        }

        $cacheKey = 'openai_models_' . md5($apiKey);

        // Check if models are cached
        if (Cache::has($cacheKey)) {
            return response()->json(['data' => Cache::get($cacheKey)]);
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

            // Filter models by version (o1, o3, o4) and remove older models (like 3.5, 4)
            $filteredModels = array_filter($models['data'], function ($model) {
                // Skip non-chat models like whisper, babbage, tts, etc.
                $nonChatModels = ['search','transcribe', 'realtime', 'whisper', 'babbage', 'davinci', 'curie', 'text-to-speech', 'dall-e', '-audio', 'tts', 'embedding', '2024', '2025'];
                foreach ($nonChatModels as $nonChatModel) {
                    if (strpos($model['id'], $nonChatModel) !== false) {
                        return false;
                    }
                }

                if (strpos($model['id'], 'gpt-4o') !== false || strpos($model['id'], 'gpt-4.5') !== false || strpos($model['id'], 'gpt-4.1') !== false) {
                    return true;
                }

                // Check if model is one of the newer versions (o1, o3, o4)
                if (preg_match('/(o[1-4]{1})/', $model['id'], $matches)) {
                    return true;
                }
                // Skip models that are older (e.g., 'gpt-3.5', 'gpt-4')
                if (strpos($model['id'], 'gpt-3.5') !== false || strpos($model['id'], 'gpt-4-') !== false) {
                    return false;
                }

                return false;
            });

            // Sort filtered models alphabetically by 'id'
            usort($filteredModels, function($a, $b) {
                return strcmp($a['id'], $b['id']);
            });

            // Cache filtered models for 10 minutes
            Cache::put($cacheKey, $filteredModels, now()->addMinutes(10));

            return response()->json(['data' => $filteredModels]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function generate(Request $request)
    {
        if (Auth::user() === null) return Response::json(["error" => "Unauthorized"], 401);
        $settings = GPTSettings::findOrFail($request->get("mailbox_id"));

        // Get ajax system prompt, and use it below if set
        $ajax_cmd = $request->get("command");
        if (!empty($ajax_cmd)) {
            $ajax_cmd = trim($ajax_cmd);
            \Log::info('Using Reply Prompt Override: ' . $ajax_cmd);
        }
        
        // If Responses API is enabled, use it instead of Chat Completions
        if (!empty($settings->use_responses_api)) {
            $articleUrls = array_filter(array_map('trim', preg_split('/\r?\n/', $settings->article_urls)));
            $fetchResult = $this->fetchArticlesContext($articleUrls, $settings, $request);
            if (!empty($fetchResult['error'])) {
                return Response::json([
                    'query' => $fetchResult['userQuery'] ?? '',
                    'answer' => $fetchResult['error']
                ], 200);
            }
            $context = $fetchResult['context'];
            $userQuery = $request->get('query');

            // Build prompt: use responses_api_prompt if set, otherwise use hardcoded default
            $prompt = (!empty($ajax_cmd) ? $ajax_cmd : $settings->start_message) . "\n\n";
            if (isset($settings->responses_api_prompt) && $settings->responses_api_prompt) {
                $prompt .= $settings->responses_api_prompt . "\n\n";
            } else {
                $prompt .= "If relevant given the customer's query, and the articles included, find the single article that best answers the user's question. Summarize the relevant part of that article as a support answer, and provide the article URL. If no article is relevant, reply with a concise best attempt to answer their concerns.";
            }

            // Use Guzzle to call OpenAI Responses API
            try {
                $guzzle = new \GuzzleHttp\Client(['timeout' => 30]);
                $apiKey = $settings->api_key;
                $payload = [
                    'model' => is_string($settings->model) ? $settings->model : (is_array($settings->model) ? reset($settings->model) : ''),
                    'input' => (string)($prompt . "\n" . $context),
                    'max_output_tokens' => (integer) $settings->token_limit
                ];
                $jsonPayload = json_encode($payload);
                $response = $guzzle->post('https://api.openai.com/v1/responses', [
                    'headers' => [
                        'Authorization' => 'Bearer ' . $apiKey,
                        'Content-Type' => 'application/json',
                    ],
                    'body' => $jsonPayload,
                ]);
                $data = json_decode($response->getBody(), true);
                $answerText = '';
                if (
                    isset($data['output'][0]['content'][0]['text']) &&
                    is_string($data['output'][0]['content'][0]['text'])
                ) {
                    $answerText = trim($data['output'][0]['content'][0]['text'], "\n");
                }
            } catch (\Exception $e) {
                $errorMsg = $e->getMessage();
                $openAiError = '';
                if (method_exists($e, 'getResponse') && $e->getResponse()) {
                    $body = (string) $e->getResponse()->getBody();
                    $json = json_decode($body, true);
                    if (isset($json['error']['message'])) {
                        $openAiError = $json['error']['message'];
                    } else {
                        $openAiError = $body;
                    }
                }
                $answerText = $openAiError ?: $errorMsg;
                \Log::error('Error on Responses API Request: ' . $answerText);
                return Response::json([
                    'query' => $userQuery ?? '',
                    'answer' => $answerText
                ], 200);
            }
            $thread = Thread::find($request->get('thread_id'));
            $answers = $thread->chatgpt ? json_decode($thread->chatgpt, true) : [];
            if ($answers === null) $answers = [];
            $answers[] = $answerText;
            $thread->chatgpt = json_encode($answers, JSON_UNESCAPED_UNICODE);
            $thread->save();
            return Response::json([
                'query' => $userQuery,
                'answer' => $answerText
            ], 200);
        }

        // Infomaniak API: use if enabled, before OpenAI
        if (!empty($settings->infomaniak_enabled)) {
            \Log::info('Using Infomaniak API for answers');
            $articleUrls = array_filter(array_map('trim', preg_split('/\r?\n/', $settings->article_urls)));
            $fetchResult = $this->fetchArticlesContext($articleUrls, $settings, $request);
            if (!empty($fetchResult['error'])) {
                \Log::error('Infomaniak API Articles Error: ' . $fetchResult['error']);
                return Response::json([
                    'query' => $fetchResult['userQuery'] ?? '',
                    'answer' => $fetchResult['error']
                ], 200);
            }
            $context = $fetchResult['context'];
            $apiKey = $settings->infomaniak_api_key;
            $productId = $settings->infomaniak_product_id;
            $model = $settings->infomaniak_model;
            $tokenLimit = (int) $settings->token_limit;
            $userQuery = $request->get('query');

            $systemPrompt = (!empty($ajax_cmd) ? $ajax_cmd : $settings->start_message);
            if (!empty($settings->infomaniak_api_prompt)) {
                $systemPrompt .= "\n\n" . $settings->infomaniak_api_prompt;
            }
            $systemPrompt .= "\n\n" . $context;

            \Log::info('Infomaniak API system prompt: ' . $systemPrompt);

            $messages = [
                [
                    'role' => 'system',
                    'content' => $systemPrompt
                ],
                [
                    'role' => 'user',
                    'content' => $userQuery
                ]
            ];

            $payload = [
                'model' => $model,
                'messages' => $messages,
                'max_tokens' => $tokenLimit
            ];
            try {
                $client = new \GuzzleHttp\Client(['timeout' => 30]);
                $response = $client->post("https://api.infomaniak.com/1/ai/{$productId}/openai/chat/completions", [
                    'headers' => [
                        'Authorization' => 'Bearer ' . $apiKey,
                        'Content-Type' => 'application/json',
                    ],
                    'body' => json_encode($payload),
                ]);
                $data = json_decode($response->getBody(), true);
                \Log::info('Infomaniak API Call Response ' . json_encode($data));
                $answerText = $data['choices'][0]['message']['content'] ?? '';
            } catch (\Exception $e) {
                \Log::error('Infomaniak API Response Error: ' . $e->getMessage());
                $answerText = $e->getMessage();
            }
            $thread = Thread::find($request->get('thread_id'));
            $answers = $thread->chatgpt ? json_decode($thread->chatgpt, true) : [];
            if ($answers === null) $answers = [];
            $answers[] = $answerText;
            $thread->chatgpt = json_encode($answers, JSON_UNESCAPED_UNICODE);
            $thread->save();
            \Log::info('Infomaniak API Generate Answer: ' . $answerText);
            return Response::json([
                'query' => $userQuery,
                'answer' => $answerText
            ], 200);
        }

        // OpenAI Chat Completions API
        $openaiClient = \Tectalic\OpenAi\Manager::build(new \GuzzleHttp\Client(
            [
                'timeout' => config('app.curl_timeout'),
                'connect_timeout' => config('app.curl_connect_timeout'),
                'proxy' => config('app.proxy'),
            ]
        ), new \Tectalic\OpenAi\Authentication($settings->api_key));

        // Determine role based on model
        if (strpos($settings->model, 'o1') !== false || strpos($settings->model, 'o3') !== false) {
            $req_role = 'user';
        } else {
            $req_role = 'developer'; // Default role, adjust as needed
        }

        $command = $request->get("command");
        $messages = [[
            'role' => $req_role,
            'content' => $command ?? $settings->start_message
        ]];

        if ($settings->client_data_enabled) {
            $customerName = $request->get("customer_name");
            $customerEmail = $request->get("customer_email");
            $conversationSubject = $request->get("conversation_subject");
            array_push($messages, [
                'role' => $req_role,
                'content' => __('Conversation subject is ":subject", customer name is ":name", customer email is ":email"', [
                    'subject' => $conversationSubject,
                    'name' => $customerName,
                    'email' => $customerEmail
                ])
            ]);
        }

        array_push($messages, [
            'role' => 'user',
            'content' => $request->get('query')
        ]);

        $response = $openaiClient->chatCompletions()->create(
            new \Tectalic\OpenAi\Models\ChatCompletions\CreateRequest([
                'model'  => $settings->model,
                'messages' => $messages,
                'max_output_tokens' => (integer) $settings->token_limit
            ])
        )->toModel();

        $thread = Thread::find($request->get('thread_id'));
        if ($thread->chatgpt === null) {
            $answers = [];
        } else {
            $answers = json_decode($thread->chatgpt, true);
        }
        if ($answers === null) {
            $answers = [];
        }
        array_push($answers, trim($response->choices[0]->message->content, "\n"));
        $thread->chatgpt = json_encode($answers, JSON_UNESCAPED_UNICODE);
        $thread->save();

        return Response::json([
            'query' => $request->get('query'),
            'answer' => $response->choices[0]->message->content
        ], 200);
    }

    public function answers(Request $request)
    {
        if (Auth::user() === null) return Response::json(["error" => "Unauthorized"], 401);
        $conversation = $request->query('conversation');
        $threads = Thread::where("conversation_id", $conversation)->get();
        $result = [];
        foreach ($threads as $thread) {
            if ($thread->chatgpt !== "{}" && $thread->chatgpt !== null) {
                $answers = [];
                $answers_text = json_decode($thread->chatgpt, true);
                if ($answers_text === null) continue;
                foreach ($answers_text as $answer_text) {
                    array_push($answers, $answer_text);
                }
                $answer = ["thread" => $thread->id, "answers" => $answers];
                array_push($result, $answer);
            }
        }
        return Response::json(["answers" => $result], 200);
    }

    public function settings($mailbox_id)
    {
        $mailbox = Mailbox::findOrFail($mailbox_id);

        $settings = GPTSettings::find($mailbox_id);

        if (empty($settings)) {
            $settings['mailbox_id'] = $mailbox_id;
            $settings['api_key'] = "";
            $settings['token_limit'] = "";
            $settings['start_message'] = "";
            $settings['enabled'] = false;
            $settings['model'] = "";
            $settings['client_data_enabled'] = false;
        }

        return view('freescoutgpt::settings', [
            'mailbox'   => $mailbox,
            'settings'  => $settings
        ]);
    }

    public function saveSettings($mailbox_id, Request $request)
    {
        GPTSettings::updateOrCreate(
            ['mailbox_id' => $mailbox_id],
            [
                'api_key' => $request->get("api_key"),
                'enabled' => isset($_POST['gpt_enabled']),
                'token_limit' => $request->get('token_limit'),
                'start_message' => $request->get('start_message'),
                'model' => $request->get('model'),
                'client_data_enabled' => isset($_POST['show_client_data_enabled']),
                'use_responses_api' => isset($_POST['use_responses_api']),
                'article_urls' => $request->get('article_urls'),
                'responses_api_prompt' => $request->get('responses_api_prompt'),
                'infomaniak_enabled' => isset($_POST['infomaniak_enabled']),
                'infomaniak_api_key' => $request->get('infomaniak_api_key'),
                'infomaniak_product_id' => $request->get('infomaniak_product_id'),
                'infomaniak_model' => $request->get('infomaniak_model'),
                'infomaniak_api_prompt' => $request->get('infomaniak_api_prompt'),
            ]
        );
        \Session::flash('flash_success_floating', __('Settings updated'));
        return redirect()->route('freescoutgpt.settings', ['mailbox_id' => $mailbox_id]);
    }

    public function checkIsEnabled(Request $request)
    {
        $settings = GPTSettings::find($request->query("mailbox"));
        if (empty($settings)) {
            return Response::json(['enabled' => false], 200);
        }
        return Response::json(['enabled' => $settings['enabled']], 200);
    }

    /**
     * Fetch and parse articles from URLs for context.
     * @param array $articleUrls
     * @return array [ 'context' => string, 'articles' => array ]
     */
    protected function fetchArticlesContext(array $articleUrls, $settings, $request)
    {
        $articles = [];
        $client = new \GuzzleHttp\Client(['timeout' => 20]);
        $userQuery = $request->get('query');
        foreach ($articleUrls as $url) {
            try {
                $res = $client->get($url, [
                    'headers' => [
                        'User-Agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36'
                    ]
                ]);
                $body = (string) $res->getBody();
                $contentType = $res->getHeaderLine('Content-Type');
                $isText = preg_match('/\.txt$/i', $url) || stripos($contentType, 'text/plain') !== false;
                if ($isText) {
                    $safeText = htmlspecialchars($body, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5);
                    $body = '<!DOCTYPE html><html><head><meta charset="UTF-8"></head><body><pre>' . $safeText . '</pre></body></html>';
                }
                // Use the new parseArticleHtml function for parsing
                $text = $this->parseArticleHtml($body);
                $articles[] = [
                    'url' => $url,
                    'text' => mb_substr($text, 0, 12000)
                ];
            } catch (\Exception $e) {
                $errorMsg = $e->getMessage();
                \Log::error('Error on Article Fetch: ' . $errorMsg ?? 'Error fetching or parsing the article.');
                return [
                    'context' => '',
                    'articles' => [],
                    'error' => $errorMsg ?? 'Error fetching or parsing the article.',
                    'userQuery' => $userQuery
                ];
            }
        }
        $context = "";
        if ($settings->client_data_enabled) {
            $customerName = $request->get("customer_name");
            $customerEmail = $request->get("customer_email");
            $conversationSubject = $request->get("conversation_subject");
            $context .= "Conversation subject is $conversationSubject, customer name is $customerName\n";
        }
        $context .= "Customer query: $userQuery\n";
        if (empty($articles)) {
            $context .= "No articles could be fetched or parsed.\n";
        } else {
            $context .= "Articles:\n";
            foreach ($articles as $i => $article) {
                $context .= "[Article #" . ($i + 1) . "] URL: " . $article['url'] . "\n";
                $context .= (is_string($article['text']) ? $article['text'] : '') . "\n\n";
            }
        }
        return [
            'context' => $context,
            'articles' => $articles
        ];
    }
    private function parseArticleHtml($html)
    {
        if (empty($html)) {
            return '';
        }

        // Normalize: ensure UTF-8 and decode any entities
        $html = html_entity_decode($html, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        libxml_use_internal_errors(true);
        $dom = new \DOMDocument();

        // Force UTF-8 parsing
        if ($dom->loadHTML('<?xml encoding="utf-8" ?>' . $html, LIBXML_NOERROR | LIBXML_NOWARNING)) {
            $xpath = new \DOMXPath($dom);
            $nodes = $xpath->query('/*');
            $text = '';

            foreach ($nodes as $node) {
                // Replace <a> tags with "text: href"
                $aTags = $node->getElementsByTagName('a');
                foreach (iterator_to_array($aTags) as $a) {
                    $linkText = trim($a->textContent);
                    $href = $a->getAttribute('href');
                    if ($linkText && $href) {
                        $replacement = $linkText . ': ' . $href . "\n";
                        $a->parentNode->replaceChild($dom->createTextNode($replacement), $a);
                    }
                }

                // Collect inner content
                $innerHTML = '';
                foreach ($node->childNodes as $child) {
                    $innerHTML .= $dom->saveHTML($child);
                }

                // Strip unwanted elements
                $innerHTML = preg_replace('/<style[\s\S]*?<\/style>/i', '', $innerHTML);
                $innerHTML = preg_replace('/<script[\s\S]*?<\/script>/i', '', $innerHTML);
                $plainText = strip_tags($innerHTML);

                // Normalize spacing and entities
                $plainText = html_entity_decode($plainText, ENT_QUOTES | ENT_HTML5, 'UTF-8');
                $plainText = str_replace("\xc2\xa0", ' ', $plainText); // non-breaking space
                $plainText = preg_replace('/[ \t]+/', ' ', $plainText);
                // Collapse 3+ newlines to 2, and trim leading/trailing whitespace/newlines
                $plainText = preg_replace('/[\r\n]{2,}/', "\n", $plainText);
                $plainText = preg_replace('/[\n]{2,}/', "\n", $plainText);
                $plainText = preg_replace('/^[\s\n\r]+|[\s\n\r]+$/u', '', $plainText);
                $text .= trim($plainText) . "";
            }

            libxml_clear_errors();
            return trim($text);
        }

        return '';
    }
}
