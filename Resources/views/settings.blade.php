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
                <label class="col-sm-2 control-label">{{ __("OpenAI API key") }}</label>

                <div class="col-sm-6">
                    <input name="api_key" class="form-control" placeholder="sk-..." value="{{ $settings['api_key'] }}" required />
                </div>
            </div>

            <div class="form-group margin-top">
                <label class="col-sm-2 control-label">{{ __("Token limit") }}</label>

                <div class="col-sm-6">
                    <input name="token_limit" class="form-control" placeholder="1024" type="number" value="{{ $settings['token_limit'] }}" required />
                </div>
            </div>

            <div class="form-group">
                <label class="col-sm-2 control-label">{{ __("Prompts and Training Info") }}</label>

                <div class="col-sm-6">
                    <textarea rows="15" name="start_message" class="form-control" placeholder="Act like a support agent. Act like a professional concise support agent. Train ChatGPT more by adding details like website link, knowledgebase link, etc." required>{{ $settings['start_message'] }}</textarea>
                </div>
            </div>

            <div class="form-group">
                <label class="col-sm-2 control-label">{{ __("Model") }}</label>

                <div class="col-sm-6">
                    <select id="model" class="form-control input-sized" name="model" required>
                        <option value="gpt-3.5-turbo" {!! $settings['model'] == "gpt-3.5-turbo" ? "selected" : "" !!}>gpt-3.5-turbo</option>
                        <option value="gpt-3.5-turbo-16k" {!! $settings['model'] == "gpt-3.5-turbo-16k" ? "selected" : "" !!}>gpt-3.5-turbo-16k</option>
                        <option value="gpt-4" {!! $settings['model'] == "gpt-4" ? "selected" : "" !!}>gpt-4</option>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label for="show_client_data_enabled" class="col-sm-2 control-label">{{ __("Send client information to ChatGPT") }}</label>

                <div class="col-sm-6" style="display: inline-flex;">
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

                    <i style="margin-left: 20px" class="glyphicon glyphicon-info-sign icon-info" data-toggle="popover" data-trigger="hover" data-html="true" data-placement="left" data-content="{{ __('If enabled, information such as the subject, customer name, and email address will be sent to the GPT. After activating this function, you can ask in a prompt, for example, to call the client by name, GPT will know his name.') }}" data-original-title="" title=""></i>
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
