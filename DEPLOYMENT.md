# Deployment to an Ubuntu VPS

This guide targets Ubuntu 24.04, PHP 8.3-FPM, MySQL, Nginx, Supervisor, and Certbot. Replace `/var/www/pustakuku` and `your-domain.example` with the deployment's real values. Do not copy demo users or passwords into production.

## 1. Install server packages

```bash
sudo apt update
sudo apt upgrade -y
sudo apt install -y nginx mysql-server supervisor certbot python3-certbot-nginx cron \
  git unzip curl ca-certificates \
  php8.3-cli php8.3-fpm php8.3-mysql php8.3-mbstring php8.3-xml \
  php8.3-curl php8.3-zip php8.3-gd php8.3-bcmath php8.3-intl php8.3-opcache
```

Install Composer 2 using the official Composer installation instructions. Install Node.js 22 LTS (Vite 7 requires a current Node 20/22 release) for the asset build; Node is not needed to serve the compiled assets.

Allow only the required ports in the VPS firewall:

```bash
sudo ufw allow OpenSSH
sudo ufw allow 'Nginx Full'
sudo ufw enable
```

## 2. Create the production database

Open the local MySQL shell:

```bash
sudo mysql
```

Create a dedicated database and least-privilege application user. Replace the password placeholder with a unique random secret and keep it in a password manager:

```sql
CREATE DATABASE pustakuku CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'pustakuku'@'localhost' IDENTIFIED BY 'REPLACE_WITH_A_LONG_RANDOM_PASSWORD';
GRANT ALL PRIVILEGES ON pustakuku.* TO 'pustakuku'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

Keep MySQL bound to localhost unless a separate private database network is intentionally configured.

## 3. Deploy the application and environment

Create a deployment directory and clone the repository. Use a dedicated deploy account with read access to the repository:

```bash
sudo mkdir -p /var/www/pustakuku
sudo chown "$USER":www-data /var/www/pustakuku
git clone <repository-url> /var/www/pustakuku
cd /var/www/pustakuku
cp .env.production.example .env
nano .env
```

Set the real `APP_URL`, database password, SMTP settings, and sender address. Keep `APP_DEBUG=false`, `APP_ENV=production`, `SESSION_SECURE_COOKIE=true`, and `APP_TIMEZONE=Asia/Jakarta`. Leave `SESSION_DOMAIN` empty for a host-only session cookie unless the deployment specifically needs a shared parent domain.

Generate `APP_KEY` once for this installation and retain it across deployments:

```bash
php artisan key:generate --force
```

Do not commit `.env`, `.env.production`, database credentials, SMTP credentials, or the generated key. `.env.production.example` contains placeholders only.

Install production dependencies and compile assets:

```bash
composer install --no-dev --prefer-dist --no-interaction --optimize-autoloader
npm ci
npm run build
```

## 4. Migrate and provision initial data

Run migrations and initialize only circulation settings:

```bash
php artisan migrate --force
php artisan db:seed --class=CirculationSettingsSeeder --force
php artisan storage:link
```

Do **not** run `php artisan db:seed` in production: `DatabaseSeeder` creates development demo accounts and sample catalog data.

Create the first verified administrator interactively, using a unique password:

```bash
php artisan tinker
```

At the Tinker prompt:

```php
use App\Models\User;
use Spatie\Permission\Models\Role;

Role::findOrCreate('admin', 'web');
$name = readline('Admin name: ');
$email = readline('Admin email: ');
$password = readline('Unique admin password: ');
$admin = User::create([
    'name' => $name,
    'email' => $email,
    'password' => $password,
    'email_verified_at' => now(),
]);
$admin->assignRole('admin');
```

Exit Tinker with `exit`.

## 5. Set ownership and permissions

The deploy account owns release files; PHP-FPM and queue workers run as `www-data`. Ensure they can read the application and write only to Laravel's required runtime directories:

```bash
sudo chown -R "$USER":www-data /var/www/pustakuku
sudo find /var/www/pustakuku -type d -exec chmod 755 {} \;
sudo find /var/www/pustakuku -type f -exec chmod 644 {} \;
sudo chown -R www-data:www-data /var/www/pustakuku/storage /var/www/pustakuku/bootstrap/cache
sudo chmod 640 /var/www/pustakuku/.env
sudo chmod -R ug+rwX /var/www/pustakuku/storage /var/www/pustakuku/bootstrap/cache
```

## 6. Configure Nginx

Create `/etc/nginx/sites-available/pustakuku`:

```nginx
server {
    listen 80;
    listen [::]:80;
    server_name your-domain.example www.your-domain.example;
    root /var/www/pustakuku/public;
    index index.php;
    charset utf-8;
    client_max_body_size 10M;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    location ~ ^/index\.php(/|$) {
        fastcgi_pass unix:/run/php/php8.3-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        fastcgi_param DOCUMENT_ROOT $realpath_root;
        include fastcgi_params;
    }

    location ~ \.php$ {
        return 404;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

Enable the site and validate the configuration:

```bash
sudo ln -s /etc/nginx/sites-available/pustakuku /etc/nginx/sites-enabled/pustakuku
sudo nginx -t
sudo systemctl reload nginx
```

Ensure DNS A/AAAA records for the chosen hostnames point to this VPS before requesting the certificate.

## 7. Enable HTTPS with Let's Encrypt

```bash
sudo certbot --nginx -d your-domain.example -d www.your-domain.example
sudo certbot renew --dry-run
```

Certbot updates the Nginx virtual host and configures certificate renewal. Keep `APP_URL` set to the HTTPS URL.

## 8. Configure the queue worker with Supervisor

Create `/etc/supervisor/conf.d/pustakuku-worker.conf`:

```ini
[program:pustakuku-worker]
process_name=%(program_name)s_%(process_num)02d
directory=/var/www/pustakuku
command=/usr/bin/php /var/www/pustakuku/artisan queue:work database --sleep=3 --tries=3 --timeout=60
user=www-data
numprocs=1
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
redirect_stderr=true
stdout_logfile=/var/www/pustakuku/storage/logs/queue-worker.log
stopwaitsecs=90
```

Load the configuration and confirm the worker is running:

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl status
```

The app uses the database queue by default; migrations create the jobs and failed-jobs tables. Configure a real SMTP mailer in `.env` to deliver queued notifications.

## 9. Register Laravel's scheduler

Create `/etc/cron.d/pustakuku`:

```cron
* * * * * www-data cd /var/www/pustakuku && /usr/bin/php artisan schedule:run >> /dev/null 2>&1
```

The scheduler marks overdue loans daily and queues H-1 reminders at 08:00. Reservation-ready emails are queued when a copy is released to the next member.

## 10. Cache configuration and verify

After `.env` is complete and the database is reachable, generate the production caches:

```bash
cd /var/www/pustakuku
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan about
```

The deploy user must be able to write to `bootstrap/cache`; PHP-FPM must be able to write to `storage`. Verify the HTTPS site, login, book import, reports, SMTP delivery, and worker status. Monitor `storage/logs/laravel.log`, Supervisor logs, and failed queue jobs (`php artisan queue:failed`).

## Subsequent deployments

Use the same release procedure for each update, then restart long-running workers:

```bash
cd /var/www/pustakuku
php artisan down
git pull --ff-only
composer install --no-dev --prefer-dist --no-interaction --optimize-autoloader
npm ci
npm run build
php artisan migrate --force
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan queue:restart
php artisan up
```

Do not regenerate `APP_KEY` during normal deployments. Back up the database and user-uploaded files before migrations or releases.
