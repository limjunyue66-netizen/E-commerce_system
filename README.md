# NovaBazaar — PHP/MySQL E-Commerce System

Modern e-commerce marketplace built with plain PHP 8.3+, MySQL, Bootstrap 5, and vanilla JavaScript. Works on XAMPP and cPanel — no Composer, npm, Laravel, or Node.js required.

## Screenshots

| Home | Shop |
|:---:|:---:|
| ![Home](docs/screenshots/01-home.png) | ![Shop](docs/screenshots/02-shop.png) |

| Login | Cart |
|:---:|:---:|
| ![Login](docs/screenshots/03-login.png) | ![Cart](docs/screenshots/04-cart.png) |

More docs: [docs/README.md](docs/README.md)

## Features

- Customer shop, search, filters, pagination, wishlist, cart, coupons, checkout
- PayPal Sandbox checkout with server-side capture verification
- Cash on Delivery + Bank / Card (demo account number)
- Order history, timeline, cancellation with stock restore
- Product reviews (purchasers only)
- Admin dashboard, users/categories/products/coupons/orders/reviews
- CSRF protection, PDO prepared statements, password hashing, RBAC, secure uploads
- Responsive UI with Font Awesome icons and a teal/amber marketplace theme
- Currency: Malaysian Ringgit (**RM / MYR**)

## Technology Stack

- PHP 8.3+
- MySQL / MariaDB (PDO)
- HTML5, CSS3, Bootstrap 5, Font Awesome 6, Google Fonts
- Vanilla JavaScript
- Apache (XAMPP / cPanel)

## Requirements

- XAMPP (Apache + MySQL) or equivalent
- PHP extensions: PDO, pdo_mysql, mbstring, fileinfo, curl (for PayPal), openssl
- Modern browser

## Installation

### XAMPP Setup

1. Install XAMPP and start **Apache** + **MySQL**.
2. Copy this project to:

```text
C:\xampp\htdocs\E-commerce_system\
```

3. Create/import the database (phpMyAdmin or CLI):

- Open `http://localhost/phpmyadmin`
- Import `database/ecommerce_marketplace.sql`

Or CLI:

```bash
C:\xampp\mysql\bin\mysql.exe -u root < database\ecommerce_marketplace.sql
```

4. Copy env examples (never commit real secrets):

```bash
copy .env.example .env
```

Optional reference: `config/database.example.php`

Edit `.env`:

```env
APP_URL=http://localhost/E-commerce_system
APP_BASE_PATH=/E-commerce_system
DB_HOST=127.0.0.1
DB_NAME=ecommerce
DB_USER=root
DB_PASS=
```

If MySQL root has a password, set `DB_PASS` in `.env` only.

5. Open:

```text
http://localhost/E-commerce_system/
```

## Database Setup

The SQL file creates:

- Database `ecommerce` (generic name for any e-commerce install)
- All tables, indexes, foreign keys
- Sample categories, products, users, coupons, orders, reviews

See also: `config/database.example.php`

## Security (before GitHub / production)

**Do not commit:**

- `.env` (real DB password, PayPal secrets, mail password)
- `logs/*.log`
- Real uploaded files under `uploads/`

Already ignored via `.gitignore`. Use:

- `.env.example` — safe template
- `config/database.example.php` — safe DB reference
- `LICENSE` — MIT

Change demo passwords before any public/production deploy.

## cPanel Deployment

1. Upload project files to `public_html` (or a subfolder).
2. Create a MySQL database + user in cPanel.
3. Import `database/ecommerce_marketplace.sql`.
4. Update `.env`:

```env
APP_URL=https://yourdomain.com
APP_BASE_PATH=
DB_HOST=localhost
DB_NAME=cpanel_db_name
DB_USER=cpanel_db_user
DB_PASS=strong_password
APP_DEBUG=false
```

If installed in a subfolder, set `APP_BASE_PATH=/subfolder`.

## Configuration

Central config lives in `.env` and is loaded by `config/bootstrap.php`.

| Key | Purpose |
|---|---|
| `APP_URL` / `APP_BASE_PATH` | Absolute URL + folder path for links/assets |
| `DB_*` | Database credentials |
| `PAYPAL_*` | PayPal Sandbox/Live credentials |
| `MAIL_*` | Optional SMTP/mail settings |
| `CURRENCY_SYMBOL` / `CURRENCY_CODE` | Display currency (default RM / MYR) |
| `BANK_*` | Demo bank details for Bank / Card checkout |
| `SHIPPING_FEE` | Default shipping fee |
| `FORCE_PASSWORD_CHANGE` | Force default admin password change |

## Demo Login (local sample data only)

| Role | Email | Password |
|---|---|---|
| Admin | `admin@gmail.com` | `admin1234` |
| User | `diana@gmail.com` | `diana1234` |

Roles: **Admin** + **User** only (one admin).

## Customer Workflow

1. Register / login
2. Browse shop, search, filter
3. Add to wishlist / cart
4. Apply coupon (codes shown on cart page)
5. Checkout with PayPal, COD, or Bank / Card
6. View orders, cancel eligible ones, leave reviews

## Admin Workflow

1. Login as admin
2. Manage users, categories, products, coupons
3. Process orders and payment status
4. Moderate reviews
5. View dashboard sales chart

## Project Structure

```text
E-commerce_system/
├── admin/                 # Admin panel
├── assets/                # CSS / JS / images
├── config/                # bootstrap, app, db, mail, paypal
│   └── database.example.php
├── database/              # SQL schema + seed
├── docs/                  # Docs + screenshots
│   └── screenshots/
├── includes/              # Shared PHP helpers / layouts
├── public/                # Customer-facing pages
├── tools/                 # Optional maintenance scripts
├── uploads/               # User uploads (gitignored except placeholders)
├── .env.example
├── LICENSE
└── README.md
```

## License

MIT — see [LICENSE](LICENSE).
