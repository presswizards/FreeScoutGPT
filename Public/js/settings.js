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

    // --- BEGIN Infomaniak/Responses API UI logic ---
    const responsesApiCheckbox = document.querySelector("input[name='use_responses_api']");
    const articleUrlsGroup = document.getElementById("article-urls-group");
    const responsesApiPromptGroup = document.querySelector("textarea[name='responses_api_prompt']")?.closest('.form-group');
    const infomaniakCheckbox = document.querySelector("input[name='infomaniak_enabled']");
    const infomaniakApiPromptGroup = document.querySelector("textarea[name='infomaniak_api_prompt']")?.closest('.form-group');
    const infomaniakFields = [
        document.querySelector("input[name='infomaniak_api_key']")?.closest('.form-group'),
        document.querySelector("input[name='infomaniak_product_id']")?.closest('.form-group'),
        document.querySelector("input[name='infomaniak_model']")?.closest('.form-group'),
        infomaniakApiPromptGroup
    ];

    function toggleApiFields(e) {
        const infomaniakOn = infomaniakCheckbox && infomaniakCheckbox.checked;
        const responsesApiOn = responsesApiCheckbox && responsesApiCheckbox.checked;

        // If turning ON Infomaniak, turn OFF Responses API
        if (e && e.type === 'change' && e.target === infomaniakCheckbox && infomaniakCheckbox.checked) {
            if (responsesApiCheckbox) responsesApiCheckbox.checked = false;
        }
        // If turning ON Responses API, turn OFF Infomaniak
        if (e && e.type === 'change' && e.target === responsesApiCheckbox && responsesApiCheckbox.checked) {
            if (infomaniakCheckbox) infomaniakCheckbox.checked = false;
        }

        const infomaniakNow = infomaniakCheckbox && infomaniakCheckbox.checked;
        const responsesApiNow = responsesApiCheckbox && responsesApiCheckbox.checked;

        // Infomaniak fields (including prompt)
        infomaniakFields.forEach(f => { if (f) f.style.display = infomaniakNow ? '' : 'none'; });

        // Show/hide Responses API prompt group
        if (responsesApiPromptGroup) {
            responsesApiPromptGroup.style.display = (responsesApiNow && !infomaniakNow) ? '' : 'none';
        }

        // Show Article URLs if either is enabled
        if (articleUrlsGroup) {
            articleUrlsGroup.style.display = (infomaniakNow || responsesApiNow) ? '' : 'none';
        }
    }

    if (infomaniakCheckbox) {
        infomaniakCheckbox.addEventListener('change', toggleApiFields);
    }
    if (responsesApiCheckbox) {
        responsesApiCheckbox.addEventListener('change', toggleApiFields);
    }
    // Initial state
    toggleApiFields();
    // --- END Infomaniak/Responses API UI logic ---
    // Hide/show Article URLs textarea based on Responses API checkbox
    function toggleResponsesApiFields() {
        if (infomaniakCheckbox && infomaniakCheckbox.checked) {
            articleUrlsGroup.style.display = '';
            responsesApiPromptGroup.style.display = 'none';
        } else if (responsesApiCheckbox && responsesApiCheckbox.checked) {
            articleUrlsGroup.style.display = '';
            responsesApiPromptGroup.style.display = '';
        } else {
            articleUrlsGroup.style.display = 'none';
            responsesApiPromptGroup.style.display = 'none';
        }
    }
    if (responsesApiCheckbox && articleUrlsGroup && responsesApiPromptGroup) {
        responsesApiCheckbox.addEventListener('change', toggleResponsesApiFields);
        toggleResponsesApiFields(); // Set initial state
    }
    // --- BEGIN Infomaniak Models Fetch ---
    const infomaniakApiKeyInput = document.querySelector("input[name='infomaniak_api_key']");
    const infomaniakProductIdInput = document.querySelector("input[name='infomaniak_product_id']");
    const infomaniakModelSelect = document.getElementById("infomaniak_model");
    const savedInfomaniakModel = infomaniakModelSelect ? infomaniakModelSelect.dataset.savedModel : '';

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
    if (infomaniakApiKeyInput && infomaniakProductIdInput && infomaniakApiKeyInput.value && infomaniakProductIdInput.value) {
        fetchInfomaniakModels(infomaniakApiKeyInput.value, infomaniakProductIdInput.value);
    }
    if (infomaniakApiKeyInput && infomaniakProductIdInput) {
        infomaniakApiKeyInput.addEventListener("blur", function () {
            fetchInfomaniakModels(infomaniakApiKeyInput.value, infomaniakProductIdInput.value);
        });
        infomaniakProductIdInput.addEventListener("blur", function () {
            fetchInfomaniakModels(infomaniakApiKeyInput.value, infomaniakProductIdInput.value);
        });
    }
    // --- END Infomaniak Models Fetch ---
    // --- BEGIN Infomaniak Product ID Fetch ---
    // Use different variable names to avoid redeclaration
    const infomaniakApiKeyInput2 = document.querySelector("input[name='infomaniak_api_key']");
    const infomaniakProductIdInput2 = document.querySelector("input[name='infomaniak_product_id']");
    function fetchInfomaniakProductIds(apiKey) {
        if (!apiKey || !infomaniakProductIdInput2) return;
        // If input is a select, clear options
        if (infomaniakProductIdInput2.tagName === 'SELECT') {
            infomaniakProductIdInput2.innerHTML = '';
        }
        fetch("/freescoutgpt/get-infomaniak-product-ids", {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-TOKEN": document.querySelector('meta[name=\"csrf-token\"]').content,
            },
            body: JSON.stringify({ infomaniak_api_key: apiKey }),
        })
        .then(response => response.json())
        .then(data => {
            if (Array.isArray(data.data) && data.data.length > 0) {
                if (infomaniakProductIdInput2.tagName === 'SELECT') {
                    infomaniakProductIdInput2.innerHTML = '<option value="">Select a Product ID</option>';
                    data.data.forEach(pid => {
                        const option = document.createElement("option");
                        option.value = pid;
                        option.textContent = pid;
                        if (pid == infomaniakProductIdInput2.value) option.selected = true;
                        infomaniakProductIdInput2.appendChild(option);
                    });
                } else {
                    infomaniakProductIdInput2.value = data.data[0];
                }
            } else {
                if (infomaniakProductIdInput2.tagName === 'SELECT') {
                    infomaniakProductIdInput2.innerHTML = '<option value="">No Product IDs found</option>';
                }
            }
        })
        .catch(error => {
            if (infomaniakProductIdInput2.tagName === 'SELECT') {
                infomaniakProductIdInput2.innerHTML = '<option value="">Error fetching Product IDs</option>';
            }
            console.error("Error fetching Infomaniak Product IDs:", error);
        });
    }
    if (infomaniakApiKeyInput2) {
        infomaniakApiKeyInput2.addEventListener("blur", function () {
            fetchInfomaniakProductIds(infomaniakApiKeyInput2.value);
        });
    }
    // --- END Infomaniak Product ID Fetch ---
    // --- BEGIN Infomaniak Product ID Select Logic ---
    const infomaniakApiKeyInput3 = document.querySelector("input[name='infomaniak_api_key']");
    const infomaniakProductIdSelect = document.getElementById("infomaniak_product_id_select");
    if (infomaniakProductIdSelect) {
        function fetchAndPopulateProductIds(apiKey) {
            if (!apiKey) return;
            infomaniakProductIdSelect.innerHTML = '<option value="">Fetching your Product IDs...</option>';
            fetch("/freescoutgpt/get-infomaniak-product-ids", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": document.querySelector('meta[name=\"csrf-token\"]').content,
                },
                body: JSON.stringify({ infomaniak_api_key: apiKey }),
            })
            .then(response => response.json())
            .then(data => {
                infomaniakProductIdSelect.innerHTML = '<option value="">Select a Product ID</option>';
                if (Array.isArray(data.data) && data.data.length > 0) {
                    data.data.forEach(pid => {
                        const option = document.createElement("option");
                        option.value = pid;
                        option.textContent = pid;
                        if (infomaniakProductIdSelect.dataset.savedProductId && pid == infomaniakProductIdSelect.dataset.savedProductId) {
                            option.selected = true;
                        }
                        infomaniakProductIdSelect.appendChild(option);
                    });
                } else {
                    infomaniakProductIdSelect.innerHTML = '<option value="">No Product IDs found</option>';
                }
            })
            .catch(error => {
                infomaniakProductIdSelect.innerHTML = '<option value="">Error fetching Product IDs</option>';
                console.error("Error fetching Infomaniak Product IDs:", error);
            });
        }
        // On API key blur, fetch product IDs
        if (infomaniakApiKeyInput3) {
            infomaniakApiKeyInput3.addEventListener("blur", function () {
                fetchAndPopulateProductIds(infomaniakApiKeyInput3.value);
            });
            // Initial fetch if value exists
            if (infomaniakApiKeyInput3.value) {
                fetchAndPopulateProductIds(infomaniakApiKeyInput3.value);
            }
        }
    }
    // --- END Infomaniak Product ID Select Logic ---
});
