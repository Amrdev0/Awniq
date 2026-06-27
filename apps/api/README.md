# Awniq API

Laravel backend API for Awniq.

## Setup

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
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

## Demo Login

```txt
Email: admin@awniq.test
Password: Password123!
```

Login endpoint:

```txt
POST /api/v1/auth/login
```

Protected identity endpoints:

```txt
GET    /api/v1/auth/me
POST   /api/v1/auth/logout
GET    /api/v1/organization
GET    /api/v1/branches
GET    /api/v1/users
GET    /api/v1/roles
GET    /api/v1/permissions
GET    /api/v1/audit-logs
```

## Commands

```bash
composer lint
composer test
```
