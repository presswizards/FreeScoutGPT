/**
 * AI Assistant Module for FreeScout
 * Provides AI-powered reply drafting with customer context learning
 */

// Global state
var aiAssistantState = {
    generatedReply: '',
    selectedTone: 'default',
    isGenerating: false,
    customerContext: null
};

// Support both old and new data object names
function getConfig() {
    return window.aiAssistantData || window.freescoutGPTData || {};
}

function aiAssistantInit() {
    $(document).ready(function() {
        // Add event listeners for thread menu actions
        $(document).on("click", ".ai-assistant-generate, .chatgpt-get", generateAnswer);
        $(document).on("click", ".gptbutton", showModifyPromptAlert);
        $(document).on("click", ".gpt-nav-previous", previousAnswer);
        $(document).on("click", ".gpt-nav-next", nextAnswer);
        $(document).on("click", ".gpt-copy-icon", copyAnswer);
        $(document).on("click", ".gpt-close-modal", hideModifyPromptAlert);
        $(document).on("click", ".gpt-apply-modal", injectGptAnswer);

        // AI Panel tone button listeners
        $(document).on("click", ".ai-tone-btn", function() {
            $(".ai-tone-btn").removeClass("active");
            $(this).addClass("active");
            aiAssistantState.selectedTone = $(this).data("tone");
        });

        // Add modal for prompt editing
        addModifyPromptAlert();

        // Register keyboard shortcut
        registerKeyboardShortcuts();

        // Initialize if on conversation page
        if (document.location.pathname.startsWith("/conversation")) {
            initConversationPage();
        }
    });
}

// Backward compatibility alias
function freescoutgptInit() {
    aiAssistantInit();
}

/**
 * Initialize conversation page features
 */
function initConversationPage() {
    const mailbox_id = $("body").attr("data-mailbox_id");
    const conversation_id = $("body").attr("data-conversation_id");

    // Check if module is enabled
    $.ajax({
        url: '/aiassistant/is_enabled?mailbox=' + mailbox_id,
        dataType: 'json',
        success: function(response) {
            if (!response.enabled) {
                $(".ai-assistant-generate, .chatgpt-get").remove();
                $("#ai-assistant-panel").remove();
            } else {
                // Load existing answers
                loadExistingAnswers(conversation_id);

                // Load customer context if enabled
                if (response.customer_history_enabled) {
                    loadCustomerContext(conversation_id);
                }
            }
        },
        error: function() {
            // Fallback to legacy endpoint
            $.ajax({
                url: '/freescoutgpt/is_enabled?mailbox=' + mailbox_id,
                dataType: 'json',
                success: function(response) {
                    if (!response.enabled) {
                        $(".ai-assistant-generate, .chatgpt-get").remove();
                    } else {
                        loadExistingAnswers(conversation_id);
                    }
                }
            });
        }
    });

    // Add AI button to reply toolbar
    addToolbarButton();
}

/**
 * Load existing AI answers for the conversation
 */
function loadExistingAnswers(conversation_id) {
    $.ajax({
        url: '/aiassistant/answers?conversation=' + conversation_id,
        dataType: 'json',
        success: function(response) {
            response.answers.forEach(function(item) {
                item.answers.forEach(function(answer) {
                    addAnswer(item.thread, answer);
                });
                $(`#thread-${item.thread} .gpt-answer`).last().removeClass("hidden");
                $(`#thread-${item.thread} .gpt-current-answer`).text($(`#thread-${item.thread} .gpt-answers div`).length);
            });
        },
        error: function() {
            // Fallback to legacy endpoint
            $.ajax({
                url: '/freescoutgpt/answers?conversation=' + conversation_id,
                dataType: 'json',
                success: function(response) {
                    response.answers.forEach(function(item) {
                        item.answers.forEach(function(answer) {
                            addAnswer(item.thread, answer);
                        });
                        $(`#thread-${item.thread} .gpt-answer`).last().removeClass("hidden");
                        $(`#thread-${item.thread} .gpt-current-answer`).text($(`#thread-${item.thread} .gpt-answers div`).length);
                    });
                }
            });
        }
    });
}

/**
 * Load customer context for the AI panel
 */
function loadCustomerContext(conversation_id) {
    $.ajax({
        url: '/aiassistant/customer-context?conversation_id=' + conversation_id,
        dataType: 'json',
        success: function(response) {
            aiAssistantState.customerContext = response.context;
            displayCustomerContext(response.context);
        },
        error: function() {
            $("#ai-context-summary").html('<span class="text-muted">Unable to load context</span>');
        }
    });
}

/**
 * Display customer context in the AI panel
 */
function displayCustomerContext(context) {
    var html = '';
    if (context.has_history) {
        if (context.history_summary) {
            html += '<p><strong>Summary:</strong> ' + escapeHtml(context.history_summary) + '</p>';
        }
        if (context.common_issues && context.common_issues.length > 0) {
            html += '<p><strong>Common Issues:</strong> ' + escapeHtml(context.common_issues.join(', ')) + '</p>';
        }
        if (context.communication_style) {
            html += '<p><strong>Style:</strong> ' + escapeHtml(context.communication_style) + '</p>';
        }
    } else {
        html = '<span class="text-muted">No previous history available</span>';
    }
    $("#ai-context-summary").html(html);
}

/**
 * Refresh customer context
 */
function refreshCustomerContext() {
    var panel = $("#ai-assistant-panel");
    var conversation_id = panel.data("conversation-id");
    var mailbox_id = panel.data("mailbox-id");
    var customer_id = $("body").attr("data-customer_id");

    $("#ai-context-summary").html('<span class="text-muted">Refreshing...</span>');

    $.ajax({
        url: '/aiassistant/refresh-context',
        method: 'POST',
        data: {
            conversation_id: conversation_id,
            mailbox_id: mailbox_id,
            customer_id: customer_id,
            _token: $('meta[name="csrf-token"]').attr('content')
        },
        dataType: 'json',
        success: function(response) {
            aiAssistantState.customerContext = response.context;
            displayCustomerContext(response.context);
            showFloatingAlert('success', 'Customer context refreshed');
        },
        error: function() {
            showFloatingAlert('error', 'Failed to refresh context');
        }
    });
}

/**
 * Add AI button to reply toolbar
 */
function addToolbarButton() {
    $(".conv-reply-body .note-toolbar > .note-btn-group:first").append(
        '<button type="button" class="note-btn btn btn-default btn-sm gptbutton" tabindex="-1" title="AI Assistant" aria-label="AI Assistant">' +
        '<i class="fa-solid fa-robot"></i>' +
        '</button>'
    );
}

/**
 * Generate answer from thread menu
 */
function generateAnswer(e) {
    e.preventDefault();

    var thread = $(e.target).closest(".thread");
    var text = thread.find(".thread-content").text().trim();
    var thread_id = thread.attr("data-thread_id");
    var mailbox_id = $("body").attr("data-mailbox_id");
    var conversation_id = $("body").attr("data-conversation_id");

    var customer_name = $(".customer-name").text();
    var customer_email = $(".customer-email").text().trim();
    var conversation_subject = $(".conv-subjtext span").text().trim();

    // Show loading indicator
    $(`#thread-${thread_id} .thread-info`).prepend('<img class="gpt-loader" src="/modules/aiassistant/img/loading.gif" alt="Loading">');

    var requestData = {
        mailbox_id: mailbox_id,
        query: text,
        thread_id: thread_id,
        conversation_id: conversation_id,
        customer_name: customer_name,
        customer_email: customer_email,
        conversation_subject: conversation_subject
    };

    $.ajax({
        url: '/aiassistant/generate',
        method: 'POST',
        data: requestData,
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        dataType: 'json',
        success: function(response) {
            $(`#thread-${thread_id} .gpt-answer`).last().addClass("hidden");
            addAnswer(thread_id, response.answer);
            $(`#thread-${thread_id} .gpt-answer`).last().removeClass("hidden");
            $(`#thread-${thread_id} .gpt-current-answer`).text($(`#thread-${thread_id} .gpt-answers div`).length);
            $(`#thread-${thread_id} .gpt-loader`).remove();
        },
        error: function() {
            showFloatingAlert('error', 'Failed to generate response');
            $(`#thread-${thread_id} .gpt-loader`).remove();
        }
    });
}

/**
 * Add answer to thread display
 */
function addAnswer(thread_id, text) {
    if (!$(`#thread-${thread_id} .gpt`).length) {
        $(`#thread-${thread_id}`).prepend(`<div class="gpt">
            <strong><i class="fa-solid fa-robot"></i> AI Assistant:</strong>
            <br />
            <div class="gpt-answers-data">
                <div class="gpt-nav">
                    <svg style="margin-right: 2px" xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-arrow-left gpt-nav-previous" viewBox="0 0 16 16">
                        <path fill-rule="evenodd" d="M15 8a.5.5 0 0 0-.5-.5H2.707l3.147-3.146a.5.5 0 1 0-.708-.708l-4 4a.5.5 0 0 0 0 .708l4 4a.5.5 0 0 0 .708-.708L2.707 8.5H14.5A.5.5 0 0 0 15 8z"/>
                    </svg>
                    <span class="gpt-current-answer">1</span>/<span class="gpt-max-answer">1</span>
                    <svg style="margin-left: 2px" xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-arrow-right gpt-nav-next" viewBox="0 0 16 16">
                        <path fill-rule="evenodd" d="M1 8a.5.5 0 0 1 .5-.5h11.793l-3.147-3.146a.5.5 0 0 1 .708-.708l4 4a.5.5 0 0 1 0 .708l-4 4a.5.5 0 0 1-.708-.708L13.293 8.5H1.5A.5.5 0 0 1 1 8z"/>
                    </svg>
                </div>
                <div class="gpt-answers"></div>
                <span class="gpt-copy-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-clipboard" viewBox="0 0 16 16">
                        <path d="M4 1.5H3a2 2 0 0 0-2 2V14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V3.5a2 2 0 0 0-2-2h-1v1h1a1 1 0 0 1 1 1V14a1 1 0 0 1-1 1H3a1 1 0 0 1-1-1V3.5a1 1 0 0 1 1-1h1v-1z"/>
                        <path d="M9.5 1a.5.5 0 0 1 .5.5v1a.5.5 0 0 1-.5.5h-3a.5.5 0 0 1-.5-.5v-1a.5.5 0 0 1 .5-.5h3zm-3-1A1.5 1.5 0 0 0 5 1.5v1A1.5 1.5 0 0 0 6.5 4h3A1.5 1.5 0 0 0 11 2.5v-1A1.5 1.5 0 0 0 9.5 0h-3z"/>
                    </svg>
                </span>
            </div>
        </div>`);
    }

    $(`#thread-${thread_id} .gpt-answers`).append(`<div class="gpt-answer hidden">${escapeHtml(text)}</div>`);
    $(`#thread-${thread_id} .gpt-max-answer`).text($(`#thread-${thread_id} .gpt-answers div`).length);

    // Animate robot icon
    var robotIcon = document.querySelector('.gpt > strong > i.fa-solid.fa-robot');
    if (robotIcon) {
        robotIcon.classList.add('fa-fade');
        setTimeout(function() {
            robotIcon.classList.remove('fa-fade');
        }, 3000);
    }
}

/**
 * Navigate to previous answer
 */
function previousAnswer(e) {
    var thread_id = $(e.target).closest(".thread").attr("data-thread_id");
    var current_answer = $(`#thread-${thread_id} .gpt-answer`).not(".hidden");
    var previous_answer = current_answer.prev();
    var current_answer_number = $(`#thread-${thread_id} .gpt-current-answer`);

    if (!previous_answer.length) return;

    current_answer.addClass("hidden");
    previous_answer.removeClass("hidden");
    current_answer_number.text(parseInt(current_answer_number.text()) - 1);
}

/**
 * Navigate to next answer
 */
function nextAnswer(e) {
    var thread_id = $(e.target).closest(".thread").attr("data-thread_id");
    var current_answer = $(`#thread-${thread_id} .gpt-answer`).not(".hidden");
    var next_answer = current_answer.next();
    var current_answer_number = $(`#thread-${thread_id} .gpt-current-answer`);

    if (!next_answer.length) return;

    current_answer.addClass("hidden");
    next_answer.removeClass("hidden");
    current_answer_number.text(parseInt(current_answer_number.text()) + 1);
}

/**
 * Copy answer to clipboard
 */
function copyAnswer(e) {
    var thread_id = $(e.target).closest(".thread").attr("data-thread_id");
    var current_answer = $(`#thread-${thread_id} .gpt-answer`).not(".hidden");
    var text = current_answer.text().replace(/```/g, "");

    navigator.clipboard.writeText(text).then(function() {
        showFloatingAlert('success', getConfig().copiedToClipboard || 'Copied to clipboard');
    });
}

/**
 * Generate reply from AI panel
 */
function generateFromPanel() {
    if (aiAssistantState.isGenerating) return;

    var panel = $("#ai-assistant-panel");
    var mailbox_id = panel.data("mailbox-id");
    var conversation_id = panel.data("conversation-id");
    var additionalContext = $("#ai-additional-context").val();
    var tone = aiAssistantState.selectedTone;

    // Get last customer message
    var thread = $(".thread-type-customer:first");
    var text = thread.find(".thread-content").text().trim();
    var thread_id = thread.attr("data-thread_id");

    var customer_name = $(".customer-name").text();
    var customer_email = $(".customer-email").text().trim();
    var conversation_subject = $(".conv-subjtext span").text().trim();

    aiAssistantState.isGenerating = true;
    $(".ai-generate-btn").prop("disabled", true).html('<i class="fa-solid fa-spinner fa-spin"></i> Generating...');

    var command = null;
    if (additionalContext || tone !== 'default') {
        var config = getConfig();
        command = config.start_message || '';
        if (additionalContext) {
            command += "\n\nAdditional context: " + additionalContext;
        }
        if (tone !== 'default') {
            command += "\n\nPlease respond in a " + tone + " tone.";
        }
    }

    var requestData = {
        mailbox_id: mailbox_id,
        query: text,
        thread_id: thread_id,
        conversation_id: conversation_id,
        customer_name: customer_name,
        customer_email: customer_email,
        conversation_subject: conversation_subject
    };

    if (command) {
        requestData.command = command;
    }

    $.ajax({
        url: '/aiassistant/generate',
        method: 'POST',
        data: requestData,
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        dataType: 'json',
        success: function(response) {
            aiAssistantState.generatedReply = response.answer;
            $("#ai-generated-reply").text(response.answer);
            $("#ai-result-section").show();
            showFloatingAlert('success', 'Reply generated successfully');
        },
        error: function() {
            showFloatingAlert('error', 'Failed to generate reply');
        },
        complete: function() {
            aiAssistantState.isGenerating = false;
            $(".ai-generate-btn").prop("disabled", false).html('<i class="fa-solid fa-wand-magic-sparkles"></i> Generate Reply');
        }
    });
}

/**
 * Insert generated reply into editor
 */
function insertGeneratedReply() {
    if (!aiAssistantState.generatedReply) return;

    var html = aiAssistantState.generatedReply.replace(/\n/g, "<br>");
    $('#body').summernote('pasteHTML', html);
    showFloatingAlert('success', 'Reply inserted');
}

/**
 * Copy generated reply to clipboard
 */
function copyGeneratedReply() {
    if (!aiAssistantState.generatedReply) return;

    navigator.clipboard.writeText(aiAssistantState.generatedReply).then(function() {
        showFloatingAlert('success', getConfig().copiedToClipboard || 'Copied to clipboard');
    });
}

/**
 * Regenerate reply
 */
function regenerateReply() {
    generateFromPanel();
}

/**
 * Adjust reply tone
 */
function adjustReplyTone(tone) {
    if (!aiAssistantState.generatedReply || aiAssistantState.isGenerating) return;

    var panel = $("#ai-assistant-panel");
    var mailbox_id = panel.data("mailbox-id");

    aiAssistantState.isGenerating = true;
    $("#ai-generated-reply").html('<i class="fa-solid fa-spinner fa-spin"></i> Adjusting tone...');

    $.ajax({
        url: '/aiassistant/adjust-tone',
        method: 'POST',
        data: {
            mailbox_id: mailbox_id,
            text: aiAssistantState.generatedReply,
            tone: tone,
            _token: $('meta[name="csrf-token"]').attr('content')
        },
        dataType: 'json',
        success: function(response) {
            aiAssistantState.generatedReply = response.adjusted;
            $("#ai-generated-reply").text(response.adjusted);
            showFloatingAlert('success', 'Tone adjusted');
        },
        error: function() {
            $("#ai-generated-reply").text(aiAssistantState.generatedReply);
            showFloatingAlert('error', 'Failed to adjust tone');
        },
        complete: function() {
            aiAssistantState.isGenerating = false;
        }
    });
}

/**
 * Toggle AI panel visibility
 */
function toggleAiPanel() {
    var header = $(".ai-panel-header");
    var content = $(".ai-panel-content");

    header.toggleClass("expanded");
    content.slideToggle(200);
}

/**
 * Register keyboard shortcuts
 */
function registerKeyboardShortcuts() {
    $(document).on('keydown', function(e) {
        // Ctrl+Shift+G to generate reply
        if (e.ctrlKey && e.shiftKey && e.key === 'G') {
            e.preventDefault();

            // If panel is visible, use panel generation
            if ($("#ai-assistant-panel").length && $(".ai-panel-content").is(":visible")) {
                generateFromPanel();
            } else {
                // Otherwise, trigger from first customer thread
                var thread = $(".thread-type-customer:first");
                if (thread.length) {
                    thread.find(".ai-assistant-generate, .chatgpt-get").first().click();
                }
            }
        }
    });
}

/**
 * Inject GPT answer from modal
 */
function injectGptAnswer() {
    var thread = $(".thread-type-customer:first");
    var text = thread.find(".thread-content").text().trim();
    var thread_id = thread.attr("data-thread_id");
    var mailbox_id = $("body").attr("data-mailbox_id");
    var conversation_id = $("body").attr("data-conversation_id");
    var customer_name = $(".customer-name").text();
    var customer_email = $(".customer-email").text().trim();
    var conversation_subject = $(".conv-subjtext span").text().trim();
    var command = $("#gpt-modified-prompt").val();

    $(".gptbutton").addClass("fa-beat-fade");
    hideModifyPromptAlert();

    $.ajax({
        url: '/aiassistant/generate',
        method: 'POST',
        data: {
            mailbox_id: mailbox_id,
            query: text,
            command: command,
            thread_id: thread_id,
            conversation_id: conversation_id,
            customer_name: customer_name,
            customer_email: customer_email,
            conversation_subject: conversation_subject
        },
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        dataType: 'json',
        success: function(response) {
            $('#body').summernote('pasteHTML', response.answer.replace(/\n/g, "<br>"));
            $(".gptbutton").removeClass("fa-beat-fade");
        },
        error: function() {
            $(".gptbutton").removeClass("fa-beat-fade");
            showFloatingAlert('error', 'Failed to generate response');
        }
    });
}

/**
 * Add modal for prompt modification
 */
function addModifyPromptAlert() {
    var config = getConfig();
    $('body').append(`
        <div class="modal fade" aria-hidden="false" tabindex="-1" style="display: none;" id="gpt-prompt-append">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close gpt-close-modal" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                        <h4 class="modal-title">${config.modifyPrompt || 'Complete prompt and send to AI'}</h4>
                    </div>
                    <div class="modal-body">
                        <div class="form-group">
                            <textarea rows="15" class="form-control" id="gpt-modified-prompt"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-primary gpt-apply-modal">
                            ${config.send || 'Generate Answer'}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    `);
    $("#gpt-modified-prompt").val(config.start_message || '');
}

/**
 * Show modal for prompt modification
 */
function showModifyPromptAlert() {
    var alert = $("#gpt-prompt-append");
    alert.addClass("in").css("display", "block");
    $("body").append('<div class="modal-backdrop fade in"></div>');
}

/**
 * Hide modal for prompt modification
 */
function hideModifyPromptAlert() {
    var alert = $("#gpt-prompt-append");
    alert.removeClass("in").css("display", "none");
    $(".modal-backdrop").remove();
    $("#gpt-modified-prompt").val(getConfig().start_message || '');
}

/**
 * Escape HTML to prevent XSS
 */
function escapeHtml(text) {
    if (!text) return '';
    var div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}
