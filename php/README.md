# PHP Rewrite (Prototype)

This directory contains a minimal PHP rewrite of the business-card scanner. It keeps parity with the Python workflow while staying dependency-light so it can be served with PHP's built-in server.

## Quick start

```bash
cd php
composer install
php -S localhost:8000 -t public
```

Then send HTTP requests to `http://localhost:8000`:

- `POST /login` with JSON `{ "username": "...", "password": "..." }`.
- `POST /passkey/register` with JSON `{ "passkey": "..." }` to store a hashed passkey in the PHP session.
- `POST /passkey/login` with JSON `{ "passkey": "..." }`.
- `POST /extract` with multipart form-data `images[]` (1–2 files) to extract contact data via OpenAI.
- `POST /notion/verify` to check the configured Notion data source connectivity.
- `POST /notion/create` with JSON `{ "contact": { ... }, "attachments": ["data:<mime>;base64,..."] }` to create a Notion page.

Environment variables match the Python version:

- `OPENAI_API_KEY`
- `NOTION_API_KEY`
- `NOTION_DATA_SOURCE_ID`
- `NOTION_VERSION` (defaults to `2025-09-03`)
- `AUTH_SECRET`
- `AUTH_USERNAME_ENC`
- `AUTH_PASSWORD_ENC`

Optional `property_config.json` values override Notion property names the same way as the Python app.

## Notes

- This is a thin prototype: it focuses on API parity, not UI. You can pair it with any frontend (e.g., Dropzone + fetch) that posts to these endpoints.
- OpenAI calls use the JSON response format to mirror the structured extraction in the Python app.
- Notion payload construction mirrors the property types (title, select, rich text, phone) used by the original implementation, including full-width comma replacement for company names.
