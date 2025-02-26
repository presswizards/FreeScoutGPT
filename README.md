![FreeScout ChatGPT Integration Module](https://platform.theverge.com/wp-content/uploads/sites/2/2025/02/openai-new-logo_f252fc.png?quality=75&strip=all&crop=7.8125%2C0%2C84.375%2C100&w=300 "ChatGPT Logo")

# FreeScout ChatGPT Integration Module (FreeScoutGPT)

This repository contains the FreeScout + ChatGPT Integration Module, which connects FreeScout with the powerful language models of ChatGPT by OpenAI via their API using your own API key. This integration enables the generation of AI-based responses for incoming messages, providing a more efficient and intelligent support system for your FreeScout team.

## Features
- Generate ChatGPT AI-based responses for each incoming message, then edit/polish as needed
- Utilize the powerful ChatGPT language models to improve support efficiency
- Customizable prompt message to set ChatGPT's role (e.g., support agent, sales manager, etc.), associate it with your brand with website links, and provide additional training information and context as desired

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

### Module Settings Page (set per mailbox)
![FreeScoutGPT Module Settings Page](https://private-user-images.githubusercontent.com/16616345/417042293-690ef890-58a4-4c37-a29f-94665102ce0d.png?jwt=eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJnaXRodWIuY29tIiwiYXVkIjoicmF3LmdpdGh1YnVzZXJjb250ZW50LmNvbSIsImtleSI6ImtleTUiLCJleHAiOjE3NDA2MDg5OTYsIm5iZiI6MTc0MDYwODY5NiwicGF0aCI6Ii8xNjYxNjM0NS80MTcwNDIyOTMtNjkwZWY4OTAtNThhNC00YzM3LWEyOWYtOTQ2NjUxMDJjZTBkLnBuZz9YLUFtei1BbGdvcml0aG09QVdTNC1ITUFDLVNIQTI1NiZYLUFtei1DcmVkZW50aWFsPUFLSUFWQ09EWUxTQTUzUFFLNFpBJTJGMjAyNTAyMjYlMkZ1cy1lYXN0LTElMkZzMyUyRmF3czRfcmVxdWVzdCZYLUFtei1EYXRlPTIwMjUwMjI2VDIyMjQ1NlomWC1BbXotRXhwaXJlcz0zMDAmWC1BbXotU2lnbmF0dXJlPWRhMmUzMGFjZjhmZDUzODlkZWQ2NmE3MWVkMThiYTA2MDBkNjkzOGM5MGMwYmM5ODZmMDgxM2RmMTI2ZmVkNmImWC1BbXotU2lnbmVkSGVhZGVycz1ob3N0In0.EDKTMGzB1GiZ5Cno4ntu1rzyGXl7OqW2KIn0a4CRcFQ)

### Look for "Generate answer (GPT)" in the drop-down on a conversation page (not reply page)
![FreeScoutGPT Generate answer in drop-down](https://private-user-images.githubusercontent.com/16616345/417354236-7fd0bee6-d8c3-4321-829d-1f0ade379911.png?jwt=eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJnaXRodWIuY29tIiwiYXVkIjoicmF3LmdpdGh1YnVzZXJjb250ZW50LmNvbSIsImtleSI6ImtleTUiLCJleHAiOjE3NDA2MTAyNjAsIm5iZiI6MTc0MDYwOTk2MCwicGF0aCI6Ii8xNjYxNjM0NS80MTczNTQyMzYtN2ZkMGJlZTYtZDhjMy00MzIxLTgyOWQtMWYwYWRlMzc5OTExLnBuZz9YLUFtei1BbGdvcml0aG09QVdTNC1ITUFDLVNIQTI1NiZYLUFtei1DcmVkZW50aWFsPUFLSUFWQ09EWUxTQTUzUFFLNFpBJTJGMjAyNTAyMjYlMkZ1cy1lYXN0LTElMkZzMyUyRmF3czRfcmVxdWVzdCZYLUFtei1EYXRlPTIwMjUwMjI2VDIyNDYwMFomWC1BbXotRXhwaXJlcz0zMDAmWC1BbXotU2lnbmF0dXJlPTlhMmI2MzQ4ZDYyYTFiZjE3NWFmN2FjMjhjZjVjNThjNjQ0YzBhYzAzYmQ2Njc5NGQwYWEwZjc4Yjc5OTc2M2QmWC1BbXotU2lnbmVkSGVhZGVycz1ob3N0In0.-8m6-G0rIQa3myOL-mo7MovszZ_ux9PqxzEAkz1xcWs)

<img width="897" alt="Image" src="https://github.com/user-attachments/assets/7fd0bee6-d8c3-4321-829d-1f0ade379911" />

### Generated answer in yellow box above that conversation with a copy button next to it
![FreeScoutGPT generated answer attached to conversation, click the Copy icon to copy it](https://private-user-images.githubusercontent.com/16616345/417036861-c2e37401-cd5d-4f5a-a689-b3850ddd7843.png?jwt=eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJnaXRodWIuY29tIiwiYXVkIjoicmF3LmdpdGh1YnVzZXJjb250ZW50LmNvbSIsImtleSI6ImtleTUiLCJleHAiOjE3NDA2MDg5OTYsIm5iZiI6MTc0MDYwODY5NiwicGF0aCI6Ii8xNjYxNjM0NS80MTcwMzY4NjEtYzJlMzc0MDEtY2Q1ZC00ZjVhLWE2ODktYjM4NTBkZGQ3ODQzLnBuZz9YLUFtei1BbGdvcml0aG09QVdTNC1ITUFDLVNIQTI1NiZYLUFtei1DcmVkZW50aWFsPUFLSUFWQ09EWUxTQTUzUFFLNFpBJTJGMjAyNTAyMjYlMkZ1cy1lYXN0LTElMkZzMyUyRmF3czRfcmVxdWVzdCZYLUFtei1EYXRlPTIwMjUwMjI2VDIyMjQ1NlomWC1BbXotRXhwaXJlcz0zMDAmWC1BbXotU2lnbmF0dXJlPWE2YjJhNjdjMjUyYTI0YjZlYzUwN2EyMjUxNzdlZDUxN2NiZjBmMmFjYzUyYjc3NTkxZGRjY2E4OTAzNjFmYjAmWC1BbXotU2lnbmVkSGVhZGVycz1ob3N0In0.OHixgvlzf3RRiSKyYfE4RFSv6FXNwPEuWqquCe3ftKQ)

### Copied to the clipboard succcessful notice after clicking the copy icon
![Copied to the clipboard notice](https://private-user-images.githubusercontent.com/16616345/417036859-3d131a3a-8212-4887-8c6b-351202df7a2f.png?jwt=eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJnaXRodWIuY29tIiwiYXVkIjoicmF3LmdpdGh1YnVzZXJjb250ZW50LmNvbSIsImtleSI6ImtleTUiLCJleHAiOjE3NDA2MDg5OTYsIm5iZiI6MTc0MDYwODY5NiwicGF0aCI6Ii8xNjYxNjM0NS80MTcwMzY4NTktM2QxMzFhM2EtODIxMi00ODg3LThjNmItMzUxMjAyZGY3YTJmLnBuZz9YLUFtei1BbGdvcml0aG09QVdTNC1ITUFDLVNIQTI1NiZYLUFtei1DcmVkZW50aWFsPUFLSUFWQ09EWUxTQTUzUFFLNFpBJTJGMjAyNTAyMjYlMkZ1cy1lYXN0LTElMkZzMyUyRmF3czRfcmVxdWVzdCZYLUFtei1EYXRlPTIwMjUwMjI2VDIyMjQ1NlomWC1BbXotRXhwaXJlcz0zMDAmWC1BbXotU2lnbmF0dXJlPTAxYzQxZmUyOWFiMDQwYWZmNzc5NzYzYWMzNjkzOWU2ZWZmOGJlZWM3NDdkYjljY2M5YWIzYWI5N2Q3YzBkOTQmWC1BbXotU2lnbmVkSGVhZGVycz1ob3N0In0.4iRy1NZ7f8rWmCjGvkIh7rs7kmftZf9mVpAgz6Kh8Lg)

### Paste the generated answer into your reply text area, and edit as desired
![Paste the generated answer into your reply text area](https://private-user-images.githubusercontent.com/16616345/417036860-4cf70554-a082-49b6-92d8-b5cf3c082a2b.png?jwt=eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJnaXRodWIuY29tIiwiYXVkIjoicmF3LmdpdGh1YnVzZXJjb250ZW50LmNvbSIsImtleSI6ImtleTUiLCJleHAiOjE3NDA2MDg5OTYsIm5iZiI6MTc0MDYwODY5NiwicGF0aCI6Ii8xNjYxNjM0NS80MTcwMzY4NjAtNGNmNzA1NTQtYTA4Mi00OWI2LTkyZDgtYjVjZjNjMDgyYTJiLnBuZz9YLUFtei1BbGdvcml0aG09QVdTNC1ITUFDLVNIQTI1NiZYLUFtei1DcmVkZW50aWFsPUFLSUFWQ09EWUxTQTUzUFFLNFpBJTJGMjAyNTAyMjYlMkZ1cy1lYXN0LTElMkZzMyUyRmF3czRfcmVxdWVzdCZYLUFtei1EYXRlPTIwMjUwMjI2VDIyMjQ1NlomWC1BbXotRXhwaXJlcz0zMDAmWC1BbXotU2lnbmF0dXJlPTQ4MTFiMmIzMTM2NDA2NGFkNjcyYzliZTljYmExNTA1ZGYxYTRjMmY1ZjkxZjMxNjMxZjhlNzU4YWI4NTZjNmEmWC1BbXotU2lnbmVkSGVhZGVycz1ob3N0In0.8Vnhmh8gqRJ7HWldxkLDTcC5ibaDDw-lViOaPaPUPi4)

## Contributing
This is an updated version of the FreeScout + ChatGPT Integration Module, and we appreciate any feedback, suggestions, or contributions to help improve the module. Please feel free to open issues or submit pull requests on GitHub.

Together, we can make this integration a valuable addition to the FreeScout ecosystem and enhance the capabilities of helpdesk software for the entire community.

### Use of OpenAI ChatGPT name and API
This module uses the ChatGPT API, developed by OpenAI, and is not officially affiliated with or endorsed by OpenAI. For more details, see OpenAI’s [Terms of Service](https://openai.com/policies/terms-of-use).
