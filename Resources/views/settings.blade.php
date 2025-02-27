@extends('layouts.app')

@section('title_full', 'FreeScoutGPT - ' . $mailbox->name)

@section('body_attrs')@parent data-mailbox_id="{{ $mailbox->id }}"@endsection

@section('sidebar')
    @include('partials/sidebar_menu_toggle')
    @include('mailboxes/sidebar_menu')
@endsection

@section('content')
    <div class="section-heading">
        FreeScoutGPT
    </div>
    <div class="col-xs-12">
        <form class="form-horizontal margin-top margin-bottom" method="POST" action="">
            {{ csrf_field() }}

            <div class="form-group">
                <label for="gpt_enabled" class="col-sm-2 control-label">{{ __("Enable ChatGPT") }}</label>

                <div class="col-sm-6">
                    <div class="controls">
                        <div class="onoffswitch-wrap">
                            <div class="onoffswitch">
                                <input type="checkbox" name="gpt_enabled" id="gpt_enabled" class="onoffswitch-checkbox"
                                    {!! $settings['enabled'] ? "checked" : "" !!}
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
                    <input name="api_key" class="form-control" placeholder="sk-..." value="{{ $settings['api_key'] }}" required />
                </div>
            </div>

            <div class="form-group margin-top">
                <label class="col-sm-2 control-label"><a target="_blank" href="https://help.openai.com/en/articles/4936856-what-are-tokens-and-how-to-count-them">{{ __("Token limit") }}</a></label>

                <div class="col-sm-6">
                    <input name="token_limit" class="form-control" placeholder="1024" type="number" value="{{ $settings['token_limit'] }}" required />
                </div>
            </div>

            <div class="form-group">
                <label class="col-sm-2 control-label"><a target="_blank" href="https://help.openai.com/en/articles/10032626-prompt-engineering-best-practices-for-chatgpt">{{ __("Prompt and Training") }}</a></label>

                <div class="col-sm-6">
                    <textarea rows="15" name="start_message" class="form-control" placeholder="Act like a support agent. (Add details like website link, knowledgebase link, etc. See module GitHub screenshots for an example)" required>{{ $settings['start_message'] }}</textarea>
                </div>
            </div>

            <div class="form-group">
                <label class="col-sm-2 control-label"><a target="_blank" href="https://platform.openai.com/docs/models">{{ __("OpenAI Model") }}</a> 
                <br/><a target="_blank" href="https://platform.openai.com/docs/pricing">{{ __("Model Pricing") }}</a>
                </label>

                <div class="col-sm-6">
                <i style="margin: 0 20px" class="glyphicon glyphicon-info-sign icon-info" data-toggle="popover" data-trigger="hover" data-html="true" data-placement="left" data-content="{{ __('Check the model pricing and capabilities, gpt-4o is the latest ChatGPT model (moderately priced), 4o-mini is decent and very inexpensive, o1-preview is the latest reasoning model (expensive), and o1-mini is a much less expensive reasoning model.') }}" data-original-title="" title=""></i>
                   <select id="model" class="form-control input-sized" name="model" required>
                        <option value="chatgpt-4o-latest" {!! $settings['model'] == "chatgpt-4o-latest" ? "selected" : "" !!}>chatgpt-4o-latest</option>
                        <option value="gpt-4o-mini" {!! $settings['model'] == "gpt-4o-mini" ? "selected" : "" !!}>gpt-4o-mini</option>
<!--
                        <option value="o1-preview" {!! $settings['model'] == "o1-preview" ? "selected" : "" !!}>o1-preview</option>
                        <option value="o1-mini" {!! $settings['model'] == "o1-mini" ? "selected" : "" !!}>o1-mini</option>
-->
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label for="show_client_data_enabled" class="col-sm-2 control-label">{{ __("Send client information to ChatGPT") }}</label>

                <div class="col-sm-6" style="display: inline-flex;">
                    <i style="margin: 0 20px" class="glyphicon glyphicon-info-sign icon-info" data-toggle="popover" data-trigger="hover" data-html="true" data-placement="left" data-content="{{ __('If enabled, information such as the subject, customer name, and email address will be sent to the GPT. After activating this function, you can ask in a prompt, for example, to call the client by name, GPT will know his name.') }}" data-original-title="" title=""></i>
                    <div class="controls">
                        <div class="onoffswitch-wrap">
                            <div class="onoffswitch">
                                <input type="checkbox" name="show_client_data_enabled" id="show_client_data_enabled" class="onoffswitch-checkbox"
                                    {!! $settings['client_data_enabled'] ? "checked" : "" !!}
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
