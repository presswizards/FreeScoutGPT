@extends('layouts.app')

@section('title_full', 'AI Assistant - ' . $mailbox->name)

@section('body_attrs')@parent data-mailbox_id="{{ $mailbox->id }}"@endsection

@section('sidebar')
	@include('partials/sidebar_menu_toggle')
	@include('mailboxes/sidebar_menu')
@endsection

@section('content')
	<div class="section-heading">
		AI Assistant <i class="fa-solid fa-robot"></i>
	</div>
	<div class="row">
		<div class="col-md-8 col-md-offset-0 col-xs-10 col-xs-offset-1">
			<form class="form-horizontal margin-top margin-bottom" method="POST" action="">
				{{ csrf_field() }}

				{{-- Enable Module --}}
				<div class="form-group">
					<label for="gpt_enabled" class="col-sm-4 control-label">{{ __("Enable AI Assistant Module") }}</label>
					<div class="col-sm-8">
						<div class="controls">
							<div class="onoffswitch-wrap">
								<div class="onoffswitch">
									<input type="checkbox" name="gpt_enabled" id="gpt_enabled" class="onoffswitch-checkbox"
										{!! ($settings->enabled ?? false) ? "checked" : "" !!}
									>
									<label class="onoffswitch-label" for="gpt_enabled"></label>
								</div>
							</div>
						</div>
					</div>
				</div>

				{{-- API Key --}}
				<div class="form-group">
					<label class="col-sm-2 control-label"><a target="_blank" href="https://platform.openai.com/api-keys">{{ __("OpenAI API key") }}</a></label>
					<div class="col-sm-8">
						<input name="api_key" class="form-control" placeholder="sk-..." value="{{ $settings->api_key ?? '' }}" required />
					</div>
				</div>

				{{-- Token Limit --}}
				<div class="form-group margin-top">
					<label class="col-sm-4 control-label"><a target="_blank" href="https://help.openai.com/en/articles/4936856-what-are-tokens-and-how-to-count-them">{{ __("Token limit") }}</a></label>
					<div class="col-sm-8">
						<input name="token_limit" class="form-control" placeholder="1024" type="number" value="{{ $settings->token_limit ?? 1024 }}" required />
					</div>
				</div>

				{{-- System Prompt --}}
				<div class="form-group">
					<label class="col-sm-4 control-label"><a target="_blank" href="https://help.openai.com/en/articles/10032626-prompt-engineering-best-practices-for-chatgpt">{{ __("Prompt and Training") }}</a></label>
					<div class="col-sm-8">
						<textarea rows="15" name="start_message" class="form-control" placeholder="Act like a support agent. (Add details like website link, knowledgebase link, etc.)" required>{{ $settings->start_message ?? '' }}</textarea>
					</div>
				</div>

				{{-- Model Selection --}}
				<div class="form-group">
					<label class="col-sm-4 control-label"><a target="_blank" href="https://platform.openai.com/docs/models">{{ __("OpenAI Model") }}</a>
						<br/><a target="_blank" href="https://platform.openai.com/docs/pricing">{{ __("Model Pricing") }}</a>
					</label>
					<div class="col-sm-8">
						<i style="margin: 0 20px" class="glyphicon glyphicon-info-sign icon-info" data-toggle="popover" data-trigger="hover" data-html="true" data-placement="left" data-content="{{ __('Check the model capabilities and pricing, and test models to see which works best for you.') }}"></i>
						<select id="model" class="form-control input-sized" name="model" required data-saved-model="{{ old('model', $settings->model ?? '') }}">
							<option value="">Fetching your API Key models...</option>
						</select>
					</div>
				</div>

				{{-- Send Client Data --}}
				<div class="form-group">
					<label for="show_client_data_enabled" class="col-sm-2 control-label">{{ __("Send client information to AI") }}</label>
					<div class="col-sm-8" style="display: inline-flex;">
						<i style="margin: 0 20px" class="glyphicon glyphicon-info-sign icon-info" data-toggle="popover" data-trigger="hover" data-html="true" data-placement="left" data-content="{{ __('If enabled, information such as the subject, customer name, and email address will be sent to the AI.') }}"></i>
						<div class="controls">
							<div class="onoffswitch-wrap">
								<div class="onoffswitch">
									<input type="checkbox" name="show_client_data_enabled" id="show_client_data_enabled" class="onoffswitch-checkbox"
										{!! ($settings->client_data_enabled ?? false) ? "checked" : "" !!}
									>
									<label class="onoffswitch-label" for="show_client_data_enabled"></label>
								</div>
							</div>
						</div>
					</div>
				</div>

				<hr class="margin-top-30">
				<h4>{{ __("Customer History Learning") }}</h4>
				<p class="text-help">{{ __("Learn from previous closed conversations to provide more contextual, personalized responses.") }}</p>

				{{-- Enable Customer History --}}
				<div class="form-group margin-top">
					<label for="customer_history_enabled" class="col-sm-4 control-label">{{ __("Enable Customer History Learning") }}</label>
					<div class="col-sm-8">
						<div class="controls">
							<div class="onoffswitch-wrap">
								<div class="onoffswitch">
									<input type="checkbox" name="customer_history_enabled" id="customer_history_enabled" class="onoffswitch-checkbox"
										{!! ($settings->customer_history_enabled ?? false) ? "checked" : "" !!}
									>
									<label class="onoffswitch-label" for="customer_history_enabled"></label>
								</div>
							</div>
						</div>
					</div>
				</div>

				{{-- History Depth --}}
				<div class="form-group customer-history-setting" style="display: {{ ($settings->customer_history_enabled ?? false) ? 'block' : 'none' }};">
					<label class="col-sm-4 control-label">{{ __("History Depth") }}</label>
					<div class="col-sm-8">
						<select name="history_depth" class="form-control input-sized">
							<option value="5" {{ ($settings->history_depth ?? 10) == 5 ? 'selected' : '' }}>5 conversations</option>
							<option value="10" {{ ($settings->history_depth ?? 10) == 10 ? 'selected' : '' }}>10 conversations</option>
							<option value="20" {{ ($settings->history_depth ?? 10) == 20 ? 'selected' : '' }}>20 conversations</option>
						</select>
						<span class="help-block">{{ __("Number of past closed conversations to analyze per customer.") }}</span>
					</div>
				</div>

				{{-- Context Refresh Interval --}}
				<div class="form-group customer-history-setting" style="display: {{ ($settings->customer_history_enabled ?? false) ? 'block' : 'none' }};">
					<label class="col-sm-4 control-label">{{ __("Context Refresh Interval") }}</label>
					<div class="col-sm-8">
						<select name="context_refresh_interval" class="form-control input-sized">
							<option value="daily" {{ ($settings->context_refresh_interval ?? 'weekly') == 'daily' ? 'selected' : '' }}>Daily</option>
							<option value="weekly" {{ ($settings->context_refresh_interval ?? 'weekly') == 'weekly' ? 'selected' : '' }}>Weekly</option>
							<option value="monthly" {{ ($settings->context_refresh_interval ?? 'weekly') == 'monthly' ? 'selected' : '' }}>Monthly</option>
						</select>
						<span class="help-block">{{ __("How often to refresh the customer context analysis.") }}</span>
					</div>
				</div>

				{{-- Analysis Model --}}
				<div class="form-group customer-history-setting" style="display: {{ ($settings->customer_history_enabled ?? false) ? 'block' : 'none' }};">
					<label class="col-sm-4 control-label">{{ __("Analysis Model") }}</label>
					<div class="col-sm-8">
						<input name="analysis_model" class="form-control input-sized" value="{{ $settings->analysis_model ?? 'gpt-4o-mini' }}" />
						<span class="help-block">{{ __("Model used for analyzing customer history (cheaper model recommended).") }}</span>
					</div>
				</div>

				{{-- Custom Context Prompt Template --}}
				<div class="form-group customer-history-setting" style="display: {{ ($settings->customer_history_enabled ?? false) ? 'block' : 'none' }};">
					<label class="col-sm-4 control-label">{{ __("Context Prompt Template") }}</label>
					<div class="col-sm-8">
						<textarea rows="6" name="context_prompt_template" class="form-control" placeholder="Leave empty for default template">{{ $settings->context_prompt_template ?? '' }}</textarea>
						<span class="help-block">{{ __("Custom prompt for analyzing customer history. Leave empty for default.") }}</span>
					</div>
				</div>

				<hr class="margin-top-30">
				<h4>{{ __("Auto-Draft Feature") }}</h4>
				<p class="text-help">{{ __("Automatically generate draft replies when customers send messages.") }}</p>

				{{-- Enable Auto-Draft --}}
				<div class="form-group margin-top">
					<label for="auto_draft_enabled" class="col-sm-4 control-label">{{ __("Enable Auto-Draft") }}</label>
					<div class="col-sm-8">
						<div class="controls">
							<div class="onoffswitch-wrap">
								<div class="onoffswitch">
									<input type="checkbox" name="auto_draft_enabled" id="auto_draft_enabled" class="onoffswitch-checkbox"
										{!! ($settings->auto_draft_enabled ?? false) ? "checked" : "" !!}
									>
									<label class="onoffswitch-label" for="auto_draft_enabled"></label>
								</div>
							</div>
						</div>
					</div>
				</div>

				{{-- Draft Notification Type --}}
				<div class="form-group auto-draft-setting" style="display: {{ ($settings->auto_draft_enabled ?? false) ? 'block' : 'none' }};">
					<label class="col-sm-4 control-label">{{ __("Draft Notification") }}</label>
					<div class="col-sm-8">
						<select name="draft_notification_type" class="form-control input-sized">
							<option value="banner" {{ ($settings->draft_notification_type ?? 'banner') == 'banner' ? 'selected' : '' }}>{{ __("Show banner notification") }}</option>
							<option value="auto_insert" {{ ($settings->draft_notification_type ?? 'banner') == 'auto_insert' ? 'selected' : '' }}>{{ __("Auto-insert into editor") }}</option>
						</select>
						<span class="help-block">{{ __("How to notify agents about AI-generated drafts.") }}</span>
					</div>
				</div>

				<hr class="margin-top-30">
				<h4>{{ __("Responses API (Articles)") }}</h4>
				<p class="text-help">{{ __("Use external articles for enhanced responses.") }}</p>

				{{-- Use Responses API --}}
				<div class="form-group margin-top">
					<label for="use_responses_api" class="col-sm-4 control-label">{{ __("Use Articles and Responses API") }}</label>
					<div class="col-sm-8">
						<i style="margin: 0 20px" class="glyphicon glyphicon-info-sign icon-info" data-toggle="popover" data-trigger="hover" data-html="true" data-placement="left" data-content="{{ __('If enabled, the articles and prompt below will be sent to generate more targeted answers.') }}"></i>
						<div class="controls">
							<div class="onoffswitch-wrap">
								<div class="onoffswitch">
									<input type="checkbox" name="use_responses_api" id="use_responses_api" class="onoffswitch-checkbox"
										{!! ($settings->use_responses_api ?? false) ? "checked" : "" !!}
									>
									<label class="onoffswitch-label" for="use_responses_api"></label>
								</div>
							</div>
						</div>
					</div>
				</div>

				{{-- Article URLs --}}
				<div class="form-group responses-api-setting" id="article-urls-group" style="display: {{ ($settings->use_responses_api ?? false) ? 'block' : 'none' }};">
					<label for="article_urls" class="col-sm-4 control-label">{{ __("Article URLs for Web Search") }}</label>
					<div class="col-sm-8">
						<textarea rows="5" name="article_urls" class="form-control" placeholder="https://example.com/article1
https://example.com/article2">{{ $settings->article_urls ?? '' }}</textarea>
						<span class="help-block">{{ __("Enter one article URL per line.") }}</span>
					</div>
				</div>

				{{-- Responses API Prompt --}}
				<div class="form-group responses-api-setting" style="display: {{ ($settings->use_responses_api ?? false) ? 'block' : 'none' }};">
					<label class="col-sm-4 control-label">{{ __("Responses API Prompt") }}</label>
					<div class="col-sm-8">
						<textarea rows="6" name="responses_api_prompt" class="form-control" placeholder="Prompt for Responses API">{{ $settings->responses_api_prompt ?? "If relevant given the customer's query, and the articles included, find the single article that best answers the user's question. Summarize the relevant part of that article as a support answer, and provide the article URL. If no article is relevant, reply with a concise best attempt to answer their concerns." }}</textarea>
						<span class="help-block">{{ __("This prompt is used after the articles context.") }}</span>
					</div>
				</div>

				{{-- Usage Link --}}
				<div class="form-group margin-top-30">
					<label class="col-sm-4 control-label"><a target="_blank" href="https://platform.openai.com/usage">{{ __("OpenAI Usage Dashboard") }}</a></label>
					<div class="col-sm-8">
						<i style="margin-left: 20px" class="glyphicon glyphicon-info-sign icon-info" data-toggle="popover" data-trigger="hover" data-html="true" data-placement="left" data-content="{{ __('View your OpenAI usage and set budget limits.') }}"></i>
					</div>
				</div>

				{{-- Save Button --}}
				<div class="form-group margin-top margin-bottom">
					<div class="col-sm-6 col-sm-offset-2">
						<button type="submit" class="btn btn-primary">
							{{ __("Save") }}
						</button>
					</div>
				</div>
			</form>
		</div>
	</div>
	<meta name="csrf-token" content="{{ csrf_token() }}">
	<script src="{{ \Module::getPublicPath('aiassistant') }}/js/settings.js"></script>
@endsection

@section('body_bottom')
	@parent
	<script>
		// Toggle customer history settings visibility
		document.getElementById('customer_history_enabled').addEventListener('change', function() {
			var settings = document.querySelectorAll('.customer-history-setting');
			settings.forEach(function(el) {
				el.style.display = this.checked ? 'block' : 'none';
			}.bind(this));
		});

		// Toggle auto-draft settings visibility
		document.getElementById('auto_draft_enabled').addEventListener('change', function() {
			var settings = document.querySelectorAll('.auto-draft-setting');
			settings.forEach(function(el) {
				el.style.display = this.checked ? 'block' : 'none';
			}.bind(this));
		});

		// Toggle responses API settings visibility
		document.getElementById('use_responses_api').addEventListener('change', function() {
			var settings = document.querySelectorAll('.responses-api-setting');
			settings.forEach(function(el) {
				el.style.display = this.checked ? 'block' : 'none';
			}.bind(this));
		});
	</script>
@endsection
