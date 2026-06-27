# Awniq API

Laravel backend API for Awniq.

## Setup

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan serve --host=127.0.0.1 --port=8000
```

Create a MySQL database named `awniq` and configure `.env`:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=awniq
DB_USERNAME=root
DB_PASSWORD=
```

## Health Check

```txt
GET /api/v1/health
```

## Commands

```bash
composer lint
composer test
```
