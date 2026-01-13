/**
 * AI Assistant Settings Page JavaScript
 */
document.addEventListener("DOMContentLoaded", function () {
    const modelSelect = document.getElementById("model");
    const apiKeyInput = document.querySelector("input[name='api_key']");
    const savedModel = modelSelect ? modelSelect.dataset.savedModel : '';

    // Animate robot icon on page load
    $(document).ready(function() {
        const robotIcon = document.querySelector('i.fa-solid.fa-robot');
        if (robotIcon) {
            robotIcon.classList.add('fa-fade');
            setTimeout(function() {
                robotIcon.classList.remove('fa-fade');
            }, 3000);
        }
    });

    /**
     * Fetch available models from OpenAI API
     */
    function fetchModels(apiKey) {
        if (!apiKey || !modelSelect) return;

        // Try new endpoint first, fallback to legacy
        fetch("/aiassistant/get-models", {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').content,
            },
            body: JSON.stringify({ api_key: apiKey }),
        })
        .then(function(response) {
            if (!response.ok) {
                // Try legacy endpoint
                return fetch("/freescoutgpt/get-models", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                        "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').content,
                    },
                    body: JSON.stringify({ api_key: apiKey }),
                });
            }
            return response;
        })
        .then(function(response) { return response.json(); })
        .then(function(data) {
            modelSelect.innerHTML = '<option value="">Select an API model</option>';
            if (data.data) {
                var models = Object.values(data.data);
                models.forEach(function(model) {
                    var option = document.createElement("option");
                    option.value = model.id;
                    option.textContent = model.id;

                    if (model.id === savedModel) {
                        option.selected = true;
                    }

                    modelSelect.appendChild(option);
                });
            } else {
                console.log('No models found or invalid data format');
            }
        })
        .catch(function(error) {
            console.error("Error fetching models:", error);
        });
    }

    // Fetch models if API key is present
    if (apiKeyInput && apiKeyInput.value) {
        fetchModels(apiKeyInput.value);
    }

    // Fetch models when API key field loses focus
    if (apiKeyInput) {
        apiKeyInput.addEventListener("blur", function () {
            fetchModels(this.value);
        });
    }
});
