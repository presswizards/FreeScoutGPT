![FreeScout ChatGPT Integration Module](https://platform.theverge.com/wp-content/uploads/sites/2/2025/02/openai-new-logo_f252fc.png?quality=75&strip=all&crop=7.8125%2C0%2C84.375%2C100&w=300 "ChatGPT Logo")

# FreeScout ChatGPT Integration Module (FreeScoutGPT)

This repository contains the FreeScout + ChatGPT Integration Module, which connects FreeScout with the powerful language models of ChatGPT by OpenAI via their API using your own API key. This integration enables the generation of AI-based responses for incoming messages, providing a more efficient and intelligent support system for your FreeScout team.

## Features
- Generate ChatGPT AI-based responses for each incoming message, then edit/polish as needed
- Utilize the powerful ChatGPT language models to improve support efficiency
- Customizable prompt message to set ChatGPT's role (e.g., support agent, sales manager, etc.), associate it with your brand with website links, and provide additional training information and context as desired
- Edit the prompt per reply using the Edit Prompt button on the Reply page, to add message-specific info to send with the request for a more detailed reply.

## Requirements
To use this module, you will need an API key for ChatGPT, which can be obtained from the OpenAI platform at [https://platform.openai.com/account/api-keys](https://platform.openai.com/account/api-keys).

## Configuration and Use
1. Upload the FreeScoutGPT Module ZIP file to your FreeScout Modules folder, rename it to ensure the module's folder is named "FreeScoutGPT".
2. Go to each mailbox you want it enabled on, and add your ChatGPT API key to the module's configuration page.
3. Set a good prompt to describe the role, and add some training info, website and KB links, etc for ChatGPT to consider using in it's answers.
4. Be sure to check the Enable switch at the top.
5. On a conversation page, not the reply page, click the drop-down in the upper right near the message time and choose "Generate answer (GPT)".
6. Then you can copy that answer, click Reply, and paste it into your message, and edit/polish it as desired.

## Helpful Screenshots

### Module Settings Page - Set Per Mailbox (Models drop-down shown here is often out of date)
![FreeScoutGPT Module Settings Page](https://github.com/user-attachments/assets/e1a54db7-dc6e-4af2-9c9e-3622cf92a9bc)

### Look for "Generate answer (GPT)" in the drop-down on a conversation page (not reply page)
![Generate answer](https://github.com/user-attachments/assets/7fd0bee6-d8c3-4321-829d-1f0ade379911)

### Generated answer in yellow box above that conversation with a copy button next to it
![Yellow answer box](https://github.com/user-attachments/assets/c2e37401-cd5d-4f5a-a689-b3850ddd7843)

### Copied to the clipboard succcessful notice after clicking the copy icon
![copied success](https://github.com/user-attachments/assets/3d131a3a-8212-4887-8c6b-351202df7a2f)

### Paste the generated answer into your reply text area, and edit as desired
![paste menu](https://github.com/user-attachments/assets/4cf70554-a082-49b6-92d8-b5cf3c082a2b)

### Use the Edit Prompt button to add message-specific info to use when replying to this one message
![edit prompt button](https://github.com/user-attachments/assets/71c93d6c-ee9b-4d94-9676-66e2235edfaf)

### Edit prompt modal window displays current prompt, add message-specific info here and send it to get a generated reply
![edit prompt modal window](https://github.com/user-attachments/assets/e70a5c89-f8e7-4667-bd5d-5c7604d14720)

## OpenAI Models

OpenAI offers a range of AI language models, each with distinct capabilities and pricing structures. Below is an overview of the models available for chat completions within FreeScoutGPT, with pricing details (which may change and be outdated).

Visit https://platform.openai.com/docs/pricing for the latest pricing.

### In order of least expensive to most expensive

gpt-4o-mini: Introduced as a cost-efficient alternative, 4o-mini balances performance with affordability. It offers a 128K context window and multimodal capabilities, including text and vision processing. This model replaces gpt-3.5-turbo. Pricing is set at $0.15 per million input tokens and $0.60 per million output tokens. Least expensive and fastest performance with decent quality results.

gpt-4o: GPT-4o (“o” for “omni”) is a versatile, high-intelligence flagship model, ideal for complex tasks requiring high accuracy and advanced capabilities. Less expensive but almost as good in quality and performance to chatgpt-4o-latest, its priced at $2.50 per million input tokens, and $10 per million output tokens.

chatgpt-4o-latest: OpenAI’s advanced chat model, designed to enhance pattern recognition and generate creative insights. It’s particularly adept at tasks requiring high emotional intelligence and creativity, such as writing and brainstorming. However, its advanced capabilities come with a higher cost, priced at $5 per million input tokens, and $15 per million output tokens.

There are new reasoning models as well, including o1, o1-preview, o1-mini, and o3-mini. Tier 1 API accounts apparently have access to only o1-preview and o1-mini, so we've fixed the API calls to remove system or developer roles which these models don't support, and added them to be available in the FreeScoutGPT settings page. o1-mini is priced at $1.10 per million input tokens, and $4.40 per million output tokens. o1-preview is the most expensive, priced at $15 per million input tokens, and $60 per million output tokens.

For more details on the models, visit https://platform.openai.com/docs/models

## Token Details

The default limit of 1024 tokens is about 750 words, and that would include the prompt and generated AI output. If you have more prompt details and training information, and longer messages to send out, then you may want to increase the token limit.

Rough Breakdown:
	•	1 token ≈ 4 characters (including spaces & punctuation)
	•	100 tokens ≈ 75 words
	•	1024 tokens ≈ 750 words

For larger prompts and messages, and larger responses, 2048 or 4096 tokens may work for a better balance of cost and performance. Decrease the token limit for shorter, lower-cost queries to avoid unnecessary expense.

## Contributing
This is an updated version of the FreeScout + ChatGPT Integration Module, and we appreciate any feedback, suggestions, or contributions to help improve the module. Please feel free to open issues or submit pull requests on GitHub.

Together, we can make this integration a valuable addition to the FreeScout ecosystem and enhance the capabilities of helpdesk software for the entire community.

## Use of OpenAI ChatGPT Name and API
This module uses the ChatGPT API, developed by OpenAI, and is not officially affiliated with or endorsed by OpenAI. For more details, see OpenAI’s [Terms of Service](https://openai.com/policies/terms-of-use).
