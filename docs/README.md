# Documentation

Project docs and UI screenshots for GitHub.

## Contents

| Path | Description |
|------|-------------|
| [screenshots/](screenshots/) | Website page screenshots |
| [../config/database.example.php](../config/database.example.php) | Example DB config (no secrets) |
| [../.env.example](../.env.example) | Example environment file |
| [../database/ecommerce_marketplace.sql](../database/ecommerce_marketplace.sql) | Schema + sample data |

## Local setup (safe)

1. Copy `.env.example` → `.env`
2. Set your own `DB_PASS` in `.env` (do not commit `.env`)
3. Import SQL into database `ecommerce`
4. Open `http://localhost/E-commerce_system/`

## Security notes

Never commit:

- `.env` (real passwords / API keys)
- `logs/*.log`
- uploaded customer/product files under `uploads/` (except placeholders)
- PayPal live credentials
