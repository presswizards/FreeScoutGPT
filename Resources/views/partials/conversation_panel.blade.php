{{-- AI Assistant Panel - Shows in conversation view --}}
<div class="ai-assistant-panel" id="ai-assistant-panel" data-conversation-id="{{ $conversation->id }}" data-mailbox-id="{{ $conversation->mailbox_id }}">
    <div class="ai-panel-header" onclick="toggleAiPanel()">
        <i class="fa-solid fa-robot"></i>
        <span>AI Assistant</span>
        <i class="fa-solid fa-chevron-down ai-panel-toggle"></i>
    </div>
    <div class="ai-panel-content" style="display: none;">
        {{-- Customer Context Section --}}
        @if($settings->customer_history_enabled ?? false)
        <div class="ai-context-section">
            <h5><i class="fa-solid fa-user-clock"></i> Customer Context</h5>
            <div class="ai-context-summary" id="ai-context-summary">
                <span class="text-muted">Loading customer context...</span>
            </div>
            <button type="button" class="btn btn-xs btn-default ai-refresh-context" onclick="refreshCustomerContext()">
                <i class="fa-solid fa-refresh"></i> Refresh
            </button>
        </div>
        @endif

        {{-- Quick Generate Section --}}
        <div class="ai-generate-section">
            <h5><i class="fa-solid fa-magic"></i> Generate Reply</h5>

            {{-- Additional Context Input --}}
            <div class="ai-context-input">
                <textarea id="ai-additional-context" class="form-control" rows="2" placeholder="Add context for this reply (optional)..."></textarea>
            </div>

            {{-- Tone Selection --}}
            <div class="ai-tone-selection margin-top-10">
                <label>Tone:</label>
                <div class="btn-group" role="group">
                    <button type="button" class="btn btn-xs btn-default ai-tone-btn active" data-tone="default">Default</button>
                    <button type="button" class="btn btn-xs btn-default ai-tone-btn" data-tone="formal">Formal</button>
                    <button type="button" class="btn btn-xs btn-default ai-tone-btn" data-tone="casual">Casual</button>
                    <button type="button" class="btn btn-xs btn-default ai-tone-btn" data-tone="empathetic">Empathetic</button>
                </div>
            </div>

            {{-- Generate Button --}}
            <div class="margin-top-10">
                <button type="button" class="btn btn-primary ai-generate-btn" onclick="generateFromPanel()">
                    <i class="fa-solid fa-wand-magic-sparkles"></i> Generate Reply
                </button>
            </div>
        </div>

        {{-- Generated Reply Section --}}
        <div class="ai-result-section" id="ai-result-section" style="display: none;">
            <h5><i class="fa-solid fa-message"></i> Generated Reply</h5>
            <div class="ai-generated-reply" id="ai-generated-reply"></div>
            <div class="ai-result-actions margin-top-10">
                <button type="button" class="btn btn-xs btn-success" onclick="insertGeneratedReply()">
                    <i class="fa-solid fa-check"></i> Insert
                </button>
                <button type="button" class="btn btn-xs btn-default" onclick="copyGeneratedReply()">
                    <i class="fa-solid fa-copy"></i> Copy
                </button>
                <button type="button" class="btn btn-xs btn-default" onclick="regenerateReply()">
                    <i class="fa-solid fa-refresh"></i> Regenerate
                </button>
                <div class="btn-group dropup">
                    <button type="button" class="btn btn-xs btn-default dropdown-toggle" data-toggle="dropdown">
                        <i class="fa-solid fa-sliders"></i> Adjust <span class="caret"></span>
                    </button>
                    <ul class="dropdown-menu">
                        <li><a href="#" onclick="adjustReplyTone('formal'); return false;">Make Formal</a></li>
                        <li><a href="#" onclick="adjustReplyTone('casual'); return false;">Make Casual</a></li>
                        <li><a href="#" onclick="adjustReplyTone('empathetic'); return false;">Make Empathetic</a></li>
                        <li class="divider"></li>
                        <li><a href="#" onclick="adjustReplyTone('shorter'); return false;">Make Shorter</a></li>
                        <li><a href="#" onclick="adjustReplyTone('longer'); return false;">Make Longer</a></li>
                    </ul>
                </div>
            </div>
        </div>

        {{-- Keyboard Shortcut Hint --}}
        <div class="ai-shortcut-hint text-muted margin-top-10">
            <small><kbd>Ctrl</kbd>+<kbd>Shift</kbd>+<kbd>G</kbd> to generate reply</small>
        </div>
    </div>
</div>

<style>
.ai-assistant-panel {
    margin: 10px 0;
    border: 1px solid #e0e0e0;
    border-radius: 4px;
    background: #fafafa;
}
.ai-panel-header {
    padding: 10px 15px;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 8px;
    font-weight: 500;
}
.ai-panel-header:hover {
    background: #f0f0f0;
}
.ai-panel-toggle {
    margin-left: auto;
    transition: transform 0.2s;
}
.ai-panel-header.expanded .ai-panel-toggle {
    transform: rotate(180deg);
}
.ai-panel-content {
    padding: 15px;
    border-top: 1px solid #e0e0e0;
}
.ai-context-section,
.ai-generate-section,
.ai-result-section {
    margin-bottom: 15px;
    padding-bottom: 15px;
    border-bottom: 1px solid #eee;
}
.ai-context-section:last-child,
.ai-generate-section:last-child,
.ai-result-section:last-child {
    margin-bottom: 0;
    padding-bottom: 0;
    border-bottom: none;
}
.ai-context-section h5,
.ai-generate-section h5,
.ai-result-section h5 {
    margin: 0 0 10px 0;
    font-size: 13px;
    color: #666;
}
.ai-context-summary {
    background: #fff;
    padding: 10px;
    border-radius: 4px;
    border: 1px solid #e0e0e0;
    font-size: 12px;
    margin-bottom: 10px;
    max-height: 100px;
    overflow-y: auto;
}
.ai-generated-reply {
    background: #fff;
    padding: 10px;
    border-radius: 4px;
    border: 1px solid #d4edda;
    background-color: #f8fff9;
    font-size: 13px;
    white-space: pre-wrap;
    max-height: 200px;
    overflow-y: auto;
}
.ai-tone-btn.active {
    background-color: #337ab7;
    color: #fff;
    border-color: #2e6da4;
}
.ai-shortcut-hint {
    font-size: 11px;
    text-align: center;
}
.ai-generate-btn {
    width: 100%;
}
.ai-result-actions {
    display: flex;
    gap: 5px;
    flex-wrap: wrap;
}
</style>
