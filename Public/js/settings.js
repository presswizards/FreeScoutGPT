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
    const infomaniakFields = [
        document.querySelector("input[name='infomaniak_api_key']")?.closest('.form-group'),
        document.querySelector("input[name='infomaniak_product_id']")?.closest('.form-group'),
        document.querySelector("input[name='infomaniak_model']")?.closest('.form-group')
    ];

    function toggleApiFields() {
        const infomaniakOn = infomaniakCheckbox && infomaniakCheckbox.checked;
        const responsesApiOn = responsesApiCheckbox && responsesApiCheckbox.checked;

        // Infomaniak fields
        infomaniakFields.forEach(f => { if (f) f.style.display = infomaniakOn ? '' : 'none'; });

        // If Infomaniak is enabled, disable and uncheck Responses API
        if (infomaniakOn) {
            if (responsesApiCheckbox) {
                responsesApiCheckbox.checked = false;
                responsesApiCheckbox.disabled = true;
            }
        } else {
            if (responsesApiCheckbox) {
                responsesApiCheckbox.disabled = false;
            }
        }

        // Show/hide Responses API prompt group
        if (responsesApiPromptGroup) {
            responsesApiPromptGroup.style.display = (responsesApiOn && !infomaniakOn) ? '' : 'none';
        }

        // Show Article URLs if either is enabled
        if (articleUrlsGroup) {
            articleUrlsGroup.style.display = (infomaniakOn || responsesApiOn) ? '' : 'none';
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
        if (responsesApiCheckbox.checked) {
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
});
