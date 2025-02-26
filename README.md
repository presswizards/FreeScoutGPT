![FreeScout ChatGPT Integration Module](https://platform.theverge.com/wp-content/uploads/sites/2/2025/02/openai-new-logo_f252fc.png?quality=75&strip=all&crop=7.8125%2C0%2C84.375%2C100&w=300 "ChatGPT Logo")

# FreeScout ChatGPT Integration Module (FreeScoutGPT)

This repository contains the FreeScout ChatGPT Integration Module, which connects FreeScout with the powerful language model ChatGPT by OpenAI via their API and your API key. This integration enables the generation of AI-based responses for incoming messages, providing a more efficient and intelligent support system for your FreeScout users.

![FreeScout ChatGPT Integration Module Example](https://my.hostetski.com/files/img/hostetskigpt.jpg "Integration Module Example")

## Features
- Generate AI-based responses for each incoming message
- Utilize the powerful ChatGPT language model to improve support efficiency
- Customizable starting message to set the AI's role (e.g., support agent, sales manager, etc.), associate it with your brand, or provide additional context

![FreeScout ChatGPT Integration Module Example](https://my.hostetski.com/files/git/gpt.gif "Integration Module Example")

## Requirements
To use this module, you will need an API key for ChatGPT, which can be obtained from the OpenAI platform at [https://platform.openai.com/account/api-keys](https://platform.openai.com/account/api-keys).

## Configuration and Use
1. Upload the FreeScoutGPT Module ZIP file to your FreeScout Modules folder, rename it to ensure the module's folder is named "FreeScoutGPT".
2. Go to each mailbox you want it enabled on, and add your ChatGPT API key to the module's configuration page.
3. Set a "prompts message" for the AI as well.
4. Be sure to check the Enable switch at the top.
5. On a conversation, click the drop-down and choose "Generate answer (GPT)".
6. Then you can copy that answer, click Reply, and paste it into your message, and edit/polish it as desired.

![FreeScout ChatGPT Integration Module Example](https://my.hostetski.com/files/git/gpt-settings.png "GPT Setting Page")

## TODO
 - [x] Settings via web interface
 - [x] Loader, which shows that the response is being generated and you have to wait a bit
 - [ ] Multiple prompts
 - [ ] Grammar check
 - [ ] Select multiple answers in a conversation

## Contributing
This is an early version of the FreeScout ChatGPT Integration Module, and we appreciate any feedback, suggestions, or contributions to help improve the module. Please feel free to open issues or submit pull requests on GitHub.

Together, we can make this integration a valuable addition to the FreeScout ecosystem and enhance the capabilities of helpdesk software for the entire community.

### Use of OpenAI ChatGPT name and API
This module uses the ChatGPT API, developed by OpenAI, and is not officially affiliated with or endorsed by OpenAI. For more details, see OpenAI’s [Terms of Service](https://openai.com/policies/terms-of-use).
