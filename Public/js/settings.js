document.addEventListener("DOMContentLoaded", function () {
    const modelSelect = document.getElementById("model");
    const apiKeyInput = document.querySelector("input[name='api_key']");
    const savedModel = modelSelect.dataset.savedModel; // Load saved model from data attribute

    $(document).ready(function() {
        const robotIcon = document.querySelector('i.fa-solid.fa-robot');
        robotIcon.classList.add('fa-fade');
        setTimeout(() => {
            robotIcon.classList.remove('fa-fade');
        }, 3000);
    });

    // ===================================================================
    // OpenAI Models Fetch
    // ===================================================================
    function fetchModels(apiKey) {
        if (!apiKey) return;

        console.log('fetchModels');
        fetch("/freescoutgpt/get-models", {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').content,
            },
            body: JSON.stringify({ api_key: apiKey }),
        })
        .then(response => response.json())
        .then(data => {
            modelSelect.innerHTML = '<option value="">Select an API model</option>';
            if (data.data) {
                const models = Object.values(data.data);
                models.forEach(model => {
                const option = document.createElement("option");
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
        .catch(error => console.error("Error fetching models:", error));
    }

    if (apiKeyInput.value) {
        fetchModels(apiKeyInput.value);
    }

    apiKeyInput.addEventListener("blur", function () {
        fetchModels(this.value);
    });

    // ===================================================================
    // Infomaniak/Responses API UI Toggle Logic
    // ===================================================================
    const responsesApiCheckbox = document.querySelector("input[name='use_responses_api']");
    const articleUrlsGroup = document.getElementById("article-urls-group");
    const responsesApiPromptGroup = document.querySelector("textarea[name='responses_api_prompt']")?.closest('.form-group');
    const infomaniakCheckbox = document.querySelector("input[name='infomaniak_enabled']");
    const infomaniakApiKeyInput = document.querySelector("input[name='infomaniak_api_key']");
    const infomaniakProductIdSelect = document.getElementById("infomaniak_product_id_select");
    const infomaniakModelSelect = document.getElementById("infomaniak_model");
    const infomaniakApiPromptGroup = document.querySelector("textarea[name='infomaniak_api_prompt']")?.closest('.form-group');
    const litellmCheckbox = document.querySelector("input[name='litellm_enabled']");
    const litellmBaseUrlInput = document.querySelector("input[name='litellm_base_url']");
    const litellmApiKeyInput = document.querySelector("input[name='litellm_api_key']");
    const litellmModelSelect = document.getElementById("litellm_model");
    const litellmFields = [
        litellmBaseUrlInput?.closest('.form-group'),
        litellmApiKeyInput?.closest('.form-group'),
        litellmModelSelect?.closest('.form-group')
    ];

    const infomaniakFields = [
        infomaniakApiKeyInput?.closest('.form-group'),
        infomaniakProductIdSelect?.closest('.form-group'),
        infomaniakModelSelect?.closest('.form-group'),
        infomaniakApiPromptGroup
    ];

    function toggleApiFields(e) {
        if (e && e.type === 'change' && e.target === litellmCheckbox && litellmCheckbox.checked) {
            if (infomaniakCheckbox) infomaniakCheckbox.checked = false;
        }
        if (e && e.type === 'change' && e.target === infomaniakCheckbox && infomaniakCheckbox.checked) {
            if (litellmCheckbox) litellmCheckbox.checked = false;
            if (responsesApiCheckbox) responsesApiCheckbox.checked = false;
        }
        if (e && e.type === 'change' && e.target === responsesApiCheckbox && responsesApiCheckbox.checked) {
            if (infomaniakCheckbox) infomaniakCheckbox.checked = false;
        }

        const infomaniakNow = infomaniakCheckbox && infomaniakCheckbox.checked;
        const responsesApiNow = responsesApiCheckbox && responsesApiCheckbox.checked;
        const litellmNow = litellmCheckbox && litellmCheckbox.checked;

        infomaniakFields.forEach(f => { if (f) f.style.display = infomaniakNow ? '' : 'none'; });
        litellmFields.forEach(f => { if (f) f.style.display = litellmNow ? '' : 'none'; });

        if (responsesApiPromptGroup) {
            responsesApiPromptGroup.style.display = (responsesApiNow && !infomaniakNow) ? '' : 'none';
        }

        if (articleUrlsGroup) {
            articleUrlsGroup.style.display = (infomaniakNow || responsesApiNow) ? '' : 'none';
        }

        if (e && e.type === 'change' && e.target === infomaniakCheckbox && infomaniakCheckbox.checked) {
            if (infomaniakApiKeyInput && infomaniakApiKeyInput.value) {
                fetchAndPopulateProductIds(infomaniakApiKeyInput.value);
            }
        }

        if (e && e.type === 'change' && e.target === litellmCheckbox && litellmCheckbox.checked) {
            if (litellmBaseUrlInput && litellmBaseUrlInput.value) {
                fetchLitellmModels(litellmBaseUrlInput.value, litellmApiKeyInput ? litellmApiKeyInput.value : '');
            }
        }
    }

    if (infomaniakCheckbox) {
        infomaniakCheckbox.addEventListener('change', toggleApiFields);
    }
    if (responsesApiCheckbox) {
        responsesApiCheckbox.addEventListener('change', toggleApiFields);
    }
    if (litellmCheckbox) {
        litellmCheckbox.addEventListener('change', toggleApiFields);
    }
    toggleApiFields();

    // ===================================================================
    // Infomaniak Product IDs Fetch
    // ===================================================================
    const savedInfomaniakModel = infomaniakModelSelect ? infomaniakModelSelect.dataset.savedModel : '';

    function fetchAndPopulateProductIds(apiKey) {
        if (!apiKey || !infomaniakProductIdSelect) return;
        
        infomaniakProductIdSelect.innerHTML = '<option value="">Fetching your Product IDs...</option>';
        
        fetch("/freescoutgpt/get-infomaniak-product-ids", {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').content,
            },
            body: JSON.stringify({ infomaniak_api_key: apiKey }),
        })
        .then(response => response.json())
        .then(data => {
            infomaniakProductIdSelect.innerHTML = '<option value="">Select a Product ID</option>';
            if (Array.isArray(data.data) && data.data.length > 0) {
                data.data.forEach((pid) => {
                    const option = document.createElement("option");
                    option.value = pid;
                    option.textContent = pid;
                    if (infomaniakProductIdSelect.dataset.savedProductId && pid == infomaniakProductIdSelect.dataset.savedProductId) {
                        option.selected = true;
                    }
                    infomaniakProductIdSelect.appendChild(option);
                });
                // If no saved value, select the first
                if (!infomaniakProductIdSelect.value || infomaniakProductIdSelect.value === "") {
                    infomaniakProductIdSelect.selectedIndex = 1; // 0 is placeholder
                }
                // Fetch models for the selected Product ID
                if (infomaniakProductIdSelect.value) {
                    fetchInfomaniakModels(apiKey, infomaniakProductIdSelect.value);
                }
            } else {
                infomaniakProductIdSelect.innerHTML = '<option value="">No Product IDs found</option>';
            }
        })
        .catch(error => {
            infomaniakProductIdSelect.innerHTML = '<option value="">Error fetching Product IDs</option>';
            console.error("Error fetching Infomaniak Product IDs:", error);
        });
    }

    // ===================================================================
    // Infomaniak Models Fetch
    // ===================================================================
    function fetchInfomaniakModels(apiKey, productId) {
        if (!apiKey || !productId || !infomaniakModelSelect) return;
        
        infomaniakModelSelect.innerHTML = '<option value="">Fetching your Infomaniak models...</option>';
        
        fetch("/freescoutgpt/infomaniak-models", {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').content,
            },
            body: JSON.stringify({ infomaniak_api_key: apiKey, infomaniak_product_id: productId }),
        })
        .then(response => response.json())
        .then(data => {
            infomaniakModelSelect.innerHTML = '<option value="">Select an Infomaniak model</option>';
            if (data.data) {
                data.data.forEach(model => {
                    const option = document.createElement("option");
                    option.value = model.id;
                    option.textContent = model.id;
                    if (model.id === savedInfomaniakModel) {
                        option.selected = true;
                    }
                    infomaniakModelSelect.appendChild(option);
                });
            } else {
                infomaniakModelSelect.innerHTML = '<option value="">No models found</option>';
            }
        })
        .catch(error => {
            infomaniakModelSelect.innerHTML = '<option value="">Error fetching models</option>';
            console.error("Error fetching Infomaniak models:", error);
        });
    }

    // ===================================================================
    // Infomaniak Event Listeners
    // ===================================================================
    if (infomaniakApiKeyInput) {
        // On API key blur, fetch product IDs
        infomaniakApiKeyInput.addEventListener("blur", function () {
            if (this.value && infomaniakCheckbox && infomaniakCheckbox.checked) {
                fetchAndPopulateProductIds(this.value);
            }
        });
        
        // Initial fetch if value exists and Infomaniak is enabled
        if (infomaniakApiKeyInput.value && infomaniakCheckbox && infomaniakCheckbox.checked) {
            fetchAndPopulateProductIds(infomaniakApiKeyInput.value);
        }
    }

    if (infomaniakProductIdSelect) {
        infomaniakProductIdSelect.addEventListener("change", function () {
            if (infomaniakApiKeyInput && this.value) {
                fetchInfomaniakModels(infomaniakApiKeyInput.value, this.value);
            }
        });
    }

    // ===================================================================
    // LiteLLM Models Fetch
    // ===================================================================
    const savedLitellmModel = litellmModelSelect ? litellmModelSelect.dataset.savedModel : '';

    function fetchLitellmModels(baseUrl, apiKey) {
        if (!baseUrl || !litellmModelSelect) return;

        litellmModelSelect.innerHTML = '<option value="">Fetching models from proxy...</option>';

        fetch("/freescoutgpt/litellm-models", {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').content,
            },
            body: JSON.stringify({ litellm_base_url: baseUrl, litellm_api_key: apiKey || '' }),
        })
        .then(response => response.json())
        .then(data => {
            litellmModelSelect.innerHTML = '<option value="">Select a model</option>';
            if (data.data && data.data.length > 0) {
                data.data.forEach(model => {
                    const option = document.createElement("option");
                    option.value = model.id;
                    option.textContent = model.id;
                    if (model.id === savedLitellmModel) {
                        option.selected = true;
                    }
                    litellmModelSelect.appendChild(option);
                });
            } else if (data.error) {
                litellmModelSelect.innerHTML = '<option value="">Error: ' + data.error + '</option>';
            } else {
                litellmModelSelect.innerHTML = '<option value="">No models found</option>';
            }
        })
        .catch(error => {
            litellmModelSelect.innerHTML = '<option value="">Error fetching models</option>';
            console.error("Error fetching LiteLLM models:", error);
        });
    }

    // ===================================================================
    // LiteLLM Event Listeners
    // ===================================================================
    if (litellmBaseUrlInput) {
        litellmBaseUrlInput.addEventListener("blur", function () {
            if (this.value && litellmCheckbox && litellmCheckbox.checked) {
                fetchLitellmModels(this.value, litellmApiKeyInput ? litellmApiKeyInput.value : '');
            }
        });

        if (litellmBaseUrlInput.value && litellmCheckbox && litellmCheckbox.checked) {
            fetchLitellmModels(litellmBaseUrlInput.value, litellmApiKeyInput ? litellmApiKeyInput.value : '');
        }
    }

    if (litellmApiKeyInput) {
        litellmApiKeyInput.addEventListener("blur", function () {
            if (litellmBaseUrlInput && litellmBaseUrlInput.value && litellmCheckbox && litellmCheckbox.checked) {
                fetchLitellmModels(litellmBaseUrlInput.value, this.value);
            }
        });
    }
});
