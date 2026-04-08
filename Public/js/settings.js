document.addEventListener("DOMContentLoaded", function () {
    const modelSelect = document.getElementById("model");
    const apiKeyInput = document.querySelector("input[name='api_key']");
    const baseUrlInput = document.getElementById("api_base_url");
    const savedModel = modelSelect.dataset.savedModel;

    $(document).ready(function() {
        const robotIcon = document.querySelector('i.fa-solid.fa-robot');
        robotIcon.classList.add('fa-fade');
        setTimeout(() => {
            robotIcon.classList.remove('fa-fade');
        }, 3000);
    });

    // ===================================================================
    // Model Fetch
    // ===================================================================
    function fetchModels(apiKey, baseUrl) {
        if (!apiKey) return;

        modelSelect.innerHTML = '<option value="">Fetching models...</option>';

        const payload = { api_key: apiKey };
        if (baseUrl) {
            payload.base_url = baseUrl;
        }

        fetch("/freescoutgpt/get-models", {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').content,
            },
            body: JSON.stringify(payload),
        })
        .then(response => response.json())
        .then(data => {
            modelSelect.innerHTML = '<option value="">Select a model</option>';
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
            } else if (data.error) {
                modelSelect.innerHTML = '<option value="">Error: ' + data.error + '</option>';
            } else {
                modelSelect.innerHTML = '<option value="">No models found</option>';
            }
        })
        .catch(error => {
            modelSelect.innerHTML = '<option value="">Error fetching models</option>';
            console.error("Error fetching models:", error);
        });
    }

    function triggerModelFetch() {
        if (apiKeyInput.value) {
            fetchModels(apiKeyInput.value, baseUrlInput ? baseUrlInput.value : '');
        }
    }

    triggerModelFetch();

    apiKeyInput.addEventListener("blur", triggerModelFetch);

    if (baseUrlInput) {
        baseUrlInput.addEventListener("blur", triggerModelFetch);
    }

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

    const infomaniakFields = [
        infomaniakApiKeyInput?.closest('.form-group'),
        infomaniakProductIdSelect?.closest('.form-group'),
        infomaniakModelSelect?.closest('.form-group'),
        infomaniakApiPromptGroup
    ];

    function toggleApiFields(e) {
        if (e && e.type === 'change' && e.target === infomaniakCheckbox && infomaniakCheckbox.checked) {
            if (responsesApiCheckbox) responsesApiCheckbox.checked = false;
        }
        if (e && e.type === 'change' && e.target === responsesApiCheckbox && responsesApiCheckbox.checked) {
            if (infomaniakCheckbox) infomaniakCheckbox.checked = false;
        }

        const infomaniakNow = infomaniakCheckbox && infomaniakCheckbox.checked;
        const responsesApiNow = responsesApiCheckbox && responsesApiCheckbox.checked;

        infomaniakFields.forEach(f => { if (f) f.style.display = infomaniakNow ? '' : 'none'; });

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
    }

    if (infomaniakCheckbox) {
        infomaniakCheckbox.addEventListener('change', toggleApiFields);
    }
    if (responsesApiCheckbox) {
        responsesApiCheckbox.addEventListener('change', toggleApiFields);
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
                if (!infomaniakProductIdSelect.value || infomaniakProductIdSelect.value === "") {
                    infomaniakProductIdSelect.selectedIndex = 1;
                }
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
        infomaniakApiKeyInput.addEventListener("blur", function () {
            if (this.value && infomaniakCheckbox && infomaniakCheckbox.checked) {
                fetchAndPopulateProductIds(this.value);
            }
        });
        
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
});
