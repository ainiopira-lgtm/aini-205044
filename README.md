# PustakaKu

A library management application built with Laravel 12, Breeze (Blade), Tailwind CSS, Alpine.js, Spatie Laravel Permission, and Pest.

## Requirements

- PHP 8.2+
- Composer
- Node.js and npm
- MySQL

## Local setup

Run these commands from the project directory in PowerShell:

```powershell
composer install
if (!(Test-Path .env)) { Copy-Item .env.example .env }
php artisan key:generate
mysql -u root -p -e "CREATE DATABASE IF NOT EXISTS pustakaku CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
npm.cmd install
php artisan migrate --seed
php artisan storage:link
npm.cmd run build
php artisan test
```

Set `DB_USERNAME` and `DB_PASSWORD` in `.env` to match your local MySQL account before migrating. Keep `.env` private and do not commit it.

The database seeder creates three example users. Each uses the local-only password `password`; change it before using any account outside development.
It also seeds example catalog data: 50 books, three copies per book, and their categories, authors, publishers, and shelves.

Administrators and librarians can manage members from the admin navigation. Member records receive annual `AGT-YYYY-0001` numbers and a linked `anggota` account automatically; the member detail page includes loan history, accumulated fines, and a printable PDF card with a QR code. Member photos are stored on Laravel's public disk, so run `php artisan storage:link` during setup.

| Role | Email |
| --- | --- |
| admin | admin@pustakaku.test |
| pustakawan | pustakawan@pustakaku.test |
| anggota | anggota@pustakaku.test |
