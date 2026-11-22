# PustakaKu

A library management application built with Laravel 12, Blade, Tailwind CSS, Alpine.js, and Spatie Laravel Permission.

## Requirements

- PHP 8.2+ with extensions used by Laravel, DomPDF, and PhpSpreadsheet
- Composer 2
- Node.js 22 LTS and npm
- MySQL 8+

## Local installation

1. Install PHP/Composer, Node.js, and MySQL. Create a database named `pustakaku`.
2. Install dependencies and prepare the environment:

   ```powershell
   composer install
   if (!(Test-Path .env)) { Copy-Item .env.example .env }
   php artisan key:generate
   npm.cmd install
   ```

3. Set `DB_USERNAME` and `DB_PASSWORD` in `.env`, then initialize the database and build the frontend:

   ```powershell
   php artisan migrate --seed
   php artisan storage:link
   npm.cmd run build
   php artisan test
   ```

   For a local Linux/macOS shell, use `cp .env.example .env` and `npm` in place of `npm.cmd`.

4. Start the development services with `composer run dev`, or run the web server and queue worker separately. The scheduler requires `php artisan schedule:work` locally or a system scheduler.

### Local demo accounts

`php artisan migrate --seed` creates these development-only accounts. Each uses the password `password`.

| Role | Email |
| --- | --- |
| admin | admin@pustakaku.test |
| pustakawan | pustakawan@pustakaku.test |
| anggota | anggota@pustakaku.test |

Never use these accounts or their passwords in production. The main seeder also adds sample books and circulation settings.

## Features

- Public home page, searchable/filterable catalog, and book availability details.
- Member management with linked accounts, annual membership numbers, status filters, and printable PDF/QR cards.
- Member portal for loans, history, fines, self-service renewals, and reservations.
- Transactional circulation, configurable loan rules, overdue processing, fines, and payment receipts.
- Admin dashboard with circulation statistics, a 30-day Chart.js graph, popular books, and today's due loans.
- Date-filtered reports for loans, returns, fines, and popular books, plus a current inventory snapshot; export each report to PDF or XLSX.
- Excel/CSV book import with validation and failed-row reporting.
- Queued email for H-1 due-date reminders and ready reservations, plus audit logs for books, members, and circulation.
- Role-based admin/member access, login throttling, Laravel CSRF middleware, and validated input.

See [DEPLOYMENT.md](./DEPLOYMENT.md) for Ubuntu VPS, Nginx, MySQL, SSL, queue-worker, and scheduler instructions. `.env.production.example` is a placeholder template; keep the real `.env` private and never commit credentials or `APP_KEY`.
