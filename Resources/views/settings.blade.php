@extends('layouts.app')

@section('title_full', 'FreeScoutGPT - ' . $mailbox->name)

@section('body_attrs')@parent data-mailbox_id="{{ $mailbox->id }}"@endsection

@section('sidebar')
    @include('partials/sidebar_menu_toggle')
    @include('mailboxes/sidebar_menu')
@endsection

@section('content')
    <div class="section-heading">
        FreeScoutGPT <i class="fa-solid fa-robot"></i> 
    </div>
    <div class="col-xs-12">
        <form class="form-horizontal margin-top margin-bottom" method="POST" action="">
            {{ csrf_field() }}

            <div class="form-group">
                <label for="gpt_enabled" class="col-sm-2 control-label">{{ __("Enable FreeScoutGPT") }}</label>

                <div class="col-sm-6">
                    <div class="controls">
                        <div class="onoffswitch-wrap">
                            <div class="onoffswitch">
                                <input type="checkbox" name="gpt_enabled" id="gpt_enabled" class="onoffswitch-checkbox"
                                    {!! ($settings['enabled'] ?? false) ? "checked" : "" !!}
                                >
                                <label class="onoffswitch-label" for="gpt_enabled"></label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label class="col-sm-2 control-label"><a target="_blank" href="https://platform.openai.com/api-keys">{{ __("OpenAI API key") }}</a></label>

                <div class="col-sm-6">
                    <input name="api_key" class="form-control" placeholder="sk-..." value="{{ $settings['api_key'] ?? '' }}" />
                </div>
            </div>

            <div class="form-group margin-top">
                <label class="col-sm-2 control-label"><a target="_blank" href="https://help.openai.com/en/articles/4936856-what-are-tokens-and-how-to-count-them">{{ __("Token limit") }}</a></label>

                <div class="col-sm-6">
                    <input name="token_limit" class="form-control" placeholder="4096" type="number" value="{{ $settings['token_limit'] ?? 4096 }}" required />
                </div>
            </div>

            <div class="form-group">
                <label class="col-sm-2 control-label"><a target="_blank" href="https://help.openai.com/en/articles/10032626-prompt-engineering-best-practices-for-chatgpt">{{ __("Prompt and Training") }}</a></label>

                <div class="col-sm-6">
                    <textarea rows="15" name="start_message" class="form-control" placeholder="Act like a support agent. (Add details like website link, knowledgebase link, etc. See module GitHub screenshots for an example)" required>{{ $settings['start_message'] ?? '' }}</textarea>
                </div>
            </div>

            <div class="form-group">
                <label class="col-sm-2 control-label"><a target="_blank" href="https://platform.openai.com/docs/models">{{ __("OpenAI Model") }}</a> 
                    <br/><a target="_blank" href="https://platform.openai.com/docs/pricing">{{ __("Model Pricing") }}</a>
                </label>

                <div class="col-sm-6">
                <i style="margin: 0 20px" class="glyphicon glyphicon-info-sign icon-info" data-toggle="popover" data-trigger="hover" data-html="true" data-placement="left" data-content="{{ __('Check the model capabilities and pricing, and test models to see which works best for you.') }}" data-original-title="" title=""></i>
                   <select id="model" class="form-control input-sized" name="model" required data-saved-model="{{ old('model', $settings['model'] ?? '') }}">
                        <option value="">Fetching your API Key models...</option>
                   </select>
                </div>
            </div>

            <meta name="csrf-token" content="{{ csrf_token() }}">
            <script src="{{ \Module::getPublicPath('freescoutgpt') }}/js/settings.js"></script>
            
            <div class="form-group">
                <label for="show_client_data_enabled" class="col-sm-2 control-label">{{ __("Send client information to ChatGPT") }}</label>

                <div class="col-sm-6" style="display: inline-flex;">
                    <i style="margin: 0 20px" class="glyphicon glyphicon-info-sign icon-info" data-toggle="popover" data-trigger="hover" data-html="true" data-placement="left" data-content="{{ __('If enabled, information such as the subject, customer name, and email address will be sent to the GPT. After activating this function, you can ask in a prompt, for example, to call the client by name, GPT will know his name.') }}" data-original-title="" title=""></i>
                    <div class="controls">
                        <div class="onoffswitch-wrap">
                            <div class="onoffswitch">
                                <input type="checkbox" name="show_client_data_enabled" id="show_client_data_enabled" class="onoffswitch-checkbox"
                                    {!! ($settings['client_data_enabled'] ?? false) ? "checked" : "" !!}
                                >
                                <label class="onoffswitch-label" for="show_client_data_enabled"></label>
                            </div>
                        </div>
                    </div>

                </div>
            </div>

            <div class="form-group">
                <label for="show_client_data_enabled" class="col-sm-2 control-label"><a target="_blank" href="https://platform.openai.com/usage">{{ __("ChatGPT Usage") }}</a></label>

                <div class="col-sm-6" style="display: inline-flex;">
                    <i style="margin-left: 20px" class="glyphicon glyphicon-info-sign icon-info" data-toggle="popover" data-trigger="hover" data-html="true" data-placement="left" data-content="{{ __('View your ChatGPT usage by clicking the link. Be sure to set a budget and review costs regularly.') }}" data-original-title="" title=""></i>
                </div>
            </div>

            <div class="form-group">
                <label for="use_responses_api" class="col-sm-2 control-label">{{ __("Use OpenAI Responses API") }}</label>
                <div class="col-sm-6">
                <i style="margin: 0 20px" class="glyphicon glyphicon-info-sign icon-info" data-toggle="popover" data-trigger="hover" data-html="true" data-placement="left" data-content="{{ __('If enabled, the articles and prompt below will be sent to the new Responses API, and should generate much more targeted and informative answers if the articles have all the relevant information.') }}" data-original-title="" title=""></i>
                    <div class="controls">
                        <div class="onoffswitch-wrap">
                            <div class="onoffswitch">
                                <input type="checkbox" name="use_responses_api" id="use_responses_api" class="onoffswitch-checkbox"
                                    {!! ($settings['use_responses_api'] ?? false) ? "checked" : "" !!}
                                >
                                <label class="onoffswitch-label" for="use_responses_api"></label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label class="col-sm-2 control-label">{{ __("Responses API Prompt") }}</label>
                <div class="col-sm-6">
                    <textarea rows="6" name="responses_api_prompt" class="form-control" placeholder="Prompt for Responses API (used after articles context)">{{ $settings['responses_api_prompt'] ?? "If relevant given the customer's query, and the articles included, find the single article that best answers the user's question. Summarize the relevant part of that article as a support answer, and provide the article URL. If no article is relevant, reply with a concise best attempt to answer their concerns." }}</textarea>
                    <span class="help-block">{{ __("This prompt is used for the OpenAI Responses API, after the articles context. You can use this to further instruct the model.") }}</span>
                </div>
            </div>

            <div class="form-group">
                <label for="infomaniak_enabled" class="col-sm-2 control-label">{{ __("Use Infomaniak Chat Completions API") }}</label>
                <div class="col-sm-6">
                    <div class="controls">
                        <div class="onoffswitch-wrap">
                            <div class="onoffswitch">
                                <input type="checkbox" name="infomaniak_enabled" id="infomaniak_enabled" class="onoffswitch-checkbox"
                                    {!! ($settings['infomaniak_enabled'] ?? false) ? "checked" : "" !!}
                                >
                                <label class="onoffswitch-label" for="infomaniak_enabled"></label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label class="col-sm-2 control-label">{{ __("Infomaniak API Key") }}</label>
                <div class="col-sm-6">
                    <input name="infomaniak_api_key" class="form-control" placeholder="Infomaniak API Key" value="{{ $settings['infomaniak_api_key'] ?? '' }}" />
                </div>
            </div>

            <div class="form-group">
                <label class="col-sm-2 control-label">{{ __("Infomaniak Product ID") }}</label>
                <div class="col-sm-6">
                    <input name="infomaniak_product_id" class="form-control" placeholder="33" value="{{ $settings['infomaniak_product_id'] ?? '33' }}" />
                </div>
            </div>

            <div class="form-group">
                <label class="col-sm-2 control-label">{{ __("Infomaniak Model Name") }}</label>
                <div class="col-sm-6">
                    <input name="infomaniak_model" class="form-control" placeholder="mistral24b" value="{{ $settings['infomaniak_model'] ?? 'mistral24b' }}" />
                </div>
            </div>

            <div class="form-group" id="article-urls-group">
                <label for="article_urls" class="col-sm-2 control-label">{{ __("Article URLs for Web Search") }}</label>
                <div class="col-sm-6">
                    <textarea rows="5" name="article_urls" class="form-control" placeholder="https://example.com/article1
https://example.com/article2">{{ $settings['article_urls'] ?? '' }}</textarea>
                    <span class="help-block">{{ __("Enter one article URL per line. These will be used for web search and summarization when the Responses API is enabled.") }}</span>
                </div>
            </div>

            <div class="form-group margin-top margin-bottom">
                <div class="col-sm-6 col-sm-offset-2">
                    <button type="submit" class="btn btn-primary">
                        {{ __("Save") }}
                    </button>
                </div>
            </div>
        </form>
    </div>
@endsection

@section('body_bottom')
    @parent

@endsection