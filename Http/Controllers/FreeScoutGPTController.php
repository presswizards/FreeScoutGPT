<?php

namespace Modules\FreeScoutGPT\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
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

    public function generate(Request $request) {
        if (Auth::user() === null) return Response::json(["error" => "Unauthorized"], 401);
        $settings = GPTSettings::findOrFail($request->get("mailbox_id"));
        $openaiClient = \Tectalic\OpenAi\Manager::build(new \GuzzleHttp\Client(
            [
                'timeout' => config('app.curl_timeout'),
                'connect_timeout' => config('app.curl_connect_timeout'),
                'proxy' => config('app.proxy'),
            ]
        ), new \Tectalic\OpenAi\Authentication($settings->api_key));

        $command = $request->get("command");
        $messages = [[
            'role' => 'assistant',
            'content' => $command ?? $settings->start_message
        ]];

        if ($settings->client_data_enabled) {
            $customerName = $request->get("customer_name");
            $customerEmail = $request->get("customer_email");
            $conversationSubject = $request->get("conversation_subject");
            array_push($messages, [
                'role' => 'assistant',
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
            'max_completion_tokens' => (integer) $settings->token_limit
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

    public function answers(Request $request) {
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

    public function settings($mailbox_id) {
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

    public function saveSettings($mailbox_id, Request $request) {
        //return $request->get('model');
        GPTSettings::updateOrCreate(
            ['mailbox_id' => $mailbox_id],
            [
                'api_key' => $request->get("api_key"),
                'enabled' => isset($_POST['gpt_enabled']),
                'token_limit' => $request->get('token_limit'),
                'start_message' => $request->get('start_message'),
                'model' => $request->get('model'),
                'client_data_enabled' => isset($_POST['show_client_data_enabled'])
            ]
        );
        \Session::flash('flash_success_floating', __('Settings updated'));
        return redirect()->route('freescoutgpt.settings', ['mailbox_id' => $mailbox_id]);
    }

    public function checkIsEnabled(Request $request) {
        $settings = GPTSettings::find($request->query("mailbox"));
        if (empty($settings)) {
            return Response::json(['enabled'=> false], 200);
        }
        return Response::json(['enabled' => $settings['enabled']], 200);
    }

}
