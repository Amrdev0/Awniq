# Deployment Guide

This guide describes a normal Laravel and Vite deployment. Docker Compose files are intentionally not part of the tracked MVP release because the project is currently being built as a standard Laravel application.

## Requirements

- PHP 8.2+
- Composer 2
- Node.js 22+
- MySQL 8 or compatible
- Web server capable of serving Laravel from `apps/api/public`
- Process manager for queue workers
- Cron entry for the Laravel scheduler

## Backend Setup

```bash
cd apps/api
composer install --no-dev --optimize-autoloader
cp .env.example .env
php artisan key:generate
php artisan migrate --force
php artisan storage:link
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Production `.env` checklist:

```txt
APP_ENV=production
APP_DEBUG=false
APP_URL=https://api.example.org
APP_VERSION=0.10.0
APP_COMMIT=<deployed-git-sha>
DB_CONNECTION=mysql
QUEUE_CONNECTION=database
CACHE_STORE=database
SESSION_DRIVER=database
CORS_ALLOWED_ORIGINS=https://admin.example.org
SANCTUM_STATEFUL_DOMAINS=admin.example.org
MAIL_MAILER=<configured-mailer>
```

Never reuse the demo password in production.

## Frontend Setup

```bash
cd apps/admin
npm ci
cp .env.example .env
npm run build
```

Production frontend environment:

```txt
VITE_API_BASE_URL=https://api.example.org/api/v1
```

Serve `apps/admin/dist` from a static web host or web server. Configure SPA fallback to `index.html`.

## Queue Worker

Run a supervised worker:

```bash
cd apps/api
php artisan queue:work --tries=3 --timeout=90
```

Restart workers after deploy:

```bash
php artisan queue:restart
```

## Scheduler

Add this cron entry on the backend server:

```cron
* * * * * cd /path/to/Awniq/apps/api && php artisan schedule:run >> /dev/null 2>&1
```

The scheduler currently runs notification reminders for stock, case follow-up, pending batch approvals, and pending donations.

## Health Checks

Public health:

```txt
GET /api/v1/health
```

Public release metadata:

```txt
GET /api/v1/version
```

Authenticated operations checks:

```txt
GET /api/v1/system/queue-health
GET /api/v1/system/scheduled-jobs
```

## Backups

At minimum, back up:

- MySQL database.
- Private Laravel storage.
- `.env` secrets through a secure secret manager.

Test restore procedures before production use.

## Upgrade Process

1. Pull the new release.
2. Install backend dependencies.
3. Install frontend dependencies.
4. Run tests in staging.
5. Back up database and private storage.
6. Run `php artisan migrate --force`.
7. Build frontend assets.
8. Restart queue workers.
9. Check `/api/v1/health` and `/api/v1/version`.
