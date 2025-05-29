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
    // Use existing variables for articleUrlsGroup and responsesApiPromptGroup
    const infomaniakCheckbox = document.querySelector("input[name='infomaniak_enabled']");
    const infomaniakFields = [
        document.querySelector("input[name='infomaniak_api_key']")?.closest('.form-group'),
        document.querySelector("input[name='infomaniak_product_id']")?.closest('.form-group'),
        document.querySelector("input[name='infomaniak_model']")?.closest('.form-group')
    ];
    function toggleApiFields() {
        if (infomaniakCheckbox && infomaniakCheckbox.checked) {
            // Show Infomaniak fields
            infomaniakFields.forEach(f => { if (f) f.style.display = ''; });
            // Show articles textarea
            if (articleUrlsGroup) articleUrlsGroup.style.display = '';
            // Hide Responses API fields
            if (responsesApiCheckbox) responsesApiCheckbox.checked = false;
            if (responsesApiCheckbox) responsesApiCheckbox.disabled = true;
            if (responsesApiPromptGroup) responsesApiPromptGroup.style.display = 'none';
        } else {
            // Hide Infomaniak fields
            infomaniakFields.forEach(f => { if (f) f.style.display = 'none'; });
            // Enable Responses API checkbox
            if (responsesApiCheckbox) responsesApiCheckbox.disabled = false;
            // Show/hide articles textarea and prompt based on Responses API
            if (responsesApiCheckbox && responsesApiCheckbox.checked) {
                if (articleUrlsGroup) articleUrlsGroup.style.display = '';
                if (responsesApiPromptGroup) responsesApiPromptGroup.style.display = '';
            } else {
                if (articleUrlsGroup) articleUrlsGroup.style.display = 'none';
                if (responsesApiPromptGroup) responsesApiPromptGroup.style.display = 'none';
            }
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
