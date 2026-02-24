# Zero Application

Payment gateway management and self-service deposits/withdrawals for brands. Inspired by locknpay; supports **Super Admin** and **Brand** roles only (no players).

## Features

- **Super Admin:** Manage payment method accounts (gateways), create brands, assign gateways to brands, monitor all deposits and withdrawals.
- **Brand:** Log in, create deposits and withdrawals, and self-approve them. Only sees their own brand’s data.

## Requirements

- PHP 8.2+
- Composer
- Node.js & NPM (for Filament assets)
- SQLite (default) or MySQL

## Installation

```bash
cd zero-application
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
npm install && npm run build
php artisan serve
```

## Panel URLs & Roles

| Panel   | URL           | Role        |
|---------|---------------|------------|
| Admin   | `/admin`      | Super Admin |
| Brand   | `/company`    | Brand       |

## Seeded users (after `php artisan migrate --seed`)

| Email            | Password  | Role        |
|------------------|-----------|------------|
| admin@zero.test  | password  | Super Admin |
| brand@zero.test  | password  | Brand (Demo Brand) |

## Usage

1. **Super Admin:** Log in at `/admin`. Create payment method accounts (Cashier → Payment Method Accounts), create brands (Brand Management → Brands), then open a brand and use “Payment Gateways” to attach PMAs. View all transactions under Monitoring → Deposits / Withdrawals.
2. **Brand:** Log in at `/company`. Create a deposit or withdrawal, then use “Approve” (or “Deny” for withdrawals) on the list.

## Payment gateways

All non-hub gateway types from locknpay are supported in the enum; configure them as payment method accounts and assign to brands as needed. Stripe is not required; deposits/withdrawals can be used with manual approve only.

## License

MIT.
