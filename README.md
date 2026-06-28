# Awniq

Awniq is an open-source aid operations platform for NGOs, charities, and small institutions.

The project is being built with Laravel as the main backend application foundation. The current foundation includes identity, organizations, branches, users, roles, audit logs, beneficiaries, family members, case files, case notes, private case document upload/download, donors, campaigns, donations, allocations, payment transactions, receipts, warehouses, inventory items, stock lots, stock movements, stock status reports, aid batches, aid distributions, delivery proof metadata, stock reservations, dashboard metrics, internal reports, CSV exports, public portal settings, public-safe campaign/statistics APIs, public donation intent placeholders, in-app notifications, notification preferences, scheduled operational reminders, and queue/scheduler health endpoints.

## Current Status

Phase 09: notifications, scheduler, and automation foundation.

Implemented so far:

- Laravel 12 API app in `apps/api`
- React TypeScript admin shell in `apps/admin`
- MySQL-ready environment configuration
- Laravel Sanctum installed for upcoming API authentication
- Spatie Laravel Permission installed for upcoming roles and permissions
- Public health endpoint at `/api/v1/health`
- Authentication endpoints
- Organization profile endpoint
- Branch, user, role, permission, and audit-log endpoints
- Beneficiary profile, family member, case file, case note, and case document endpoints
- Beneficiary and case review/approval workflows
- Donor, campaign, donation, allocation, payment transaction, and receipt endpoints
- Manual donation confirmation with idempotency support
- Warehouse, inventory item, stock lot, stock movement, low-stock, and expiring-stock endpoints
- Aid batch, distribution, distribution item, approval, reservation, delivery, failure, reschedule, and proof endpoints
- Dashboard, donation, campaign, beneficiary, case, distribution, inventory, audit report, and CSV export endpoints
- Public organization profile, public campaign, public stats, public reports, public donation intent, and public portal settings endpoints
- Public portal frontend route group at `/public`
- In-app notification, unread count, mark-read, notification preference, queue health, and scheduler visibility endpoints
- Scheduled jobs for low stock, expiring stock, case follow-up, pending batch approval, and pending donation reminders
- Admin notification bell with unread badge and mark-read actions
- Demo identity, case-management, finance, inventory, and aid distribution seed data for manual testing
- Basic backend and frontend test setup
- GitHub Actions CI workflow

## Tech Stack

Backend:

- PHP 8.2+
- Laravel 12
- MySQL
- Laravel Sanctum
- Spatie Laravel Permission
- PHPUnit
- Laravel Pint

Frontend:

- React
- TypeScript
- Vite
- Tailwind CSS
- TanStack Query
- React Router
- Vitest

## Project Structure

```txt
apps/
  api/      Laravel backend API
  admin/    React admin frontend
openapi/    API documentation placeholder
postman/    Postman collection placeholder
```

## Local Setup

### 1. Clone The Repository

```bash
git clone https://github.com/Amrdev0/Awniq.git
cd Awniq
```

### 2. Configure The Laravel API

```bash
cd apps/api
composer install
cp .env.example .env
php artisan key:generate
```

Create a MySQL database named:

```txt
awniq
```

Then update `apps/api/.env` if needed:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=awniq
DB_USERNAME=root
DB_PASSWORD=
```

Run the API:

```bash
php artisan migrate --seed
php artisan serve --host=127.0.0.1 --port=8000
```

Health check:

```txt
http://127.0.0.1:8000/api/v1/health
```

Expected response:

```json
{
  "data": {
    "status": "ok",
    "service": "awniq-api",
    "environment": "local"
  },
  "message": "Operation completed successfully."
}
```

### 3. Configure The Admin Frontend

Open a second terminal:

```bash
cd apps/admin
npm install
cp .env.example .env
npm run dev
```

Admin URL:

```txt
http://127.0.0.1:5173
```

Public portal URL:

```txt
http://127.0.0.1:5173/public
```

## Demo Accounts

Seeded users all use this password:

```txt
Password123!
```

Available accounts:

```txt
super@awniq.test
admin@awniq.test
case.manager@awniq.test
finance@awniq.test
warehouse@awniq.test
distribution@awniq.test
volunteer@awniq.test
auditor@awniq.test
```

Use `admin@awniq.test` for normal manual testing.

## Manual API Test

Login:

```bash
curl -X POST http://127.0.0.1:8000/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -d "{\"email\":\"admin@awniq.test\",\"password\":\"Password123!\",\"device_name\":\"manual\"}"
```

Use the returned token as a Bearer token:

```bash
curl http://127.0.0.1:8000/api/v1/auth/me \
  -H "Authorization: Bearer YOUR_TOKEN"
```

Useful protected endpoints:

```txt
GET    /api/v1/auth/me
POST   /api/v1/auth/logout
GET    /api/v1/notifications
GET    /api/v1/notifications/unread-count
POST   /api/v1/notifications/{notification}/mark-read
POST   /api/v1/notifications/mark-all-read
GET    /api/v1/notification-preferences
PATCH  /api/v1/notification-preferences
GET    /api/v1/system/scheduled-jobs
GET    /api/v1/system/queue-health
GET    /api/v1/organization
PATCH  /api/v1/organization
GET    /api/v1/settings/public-portal
PATCH  /api/v1/settings/public-portal
GET    /api/v1/branches
POST   /api/v1/branches
GET    /api/v1/users
POST   /api/v1/users
GET    /api/v1/roles
GET    /api/v1/permissions
GET    /api/v1/audit-logs
GET    /api/v1/beneficiaries
POST   /api/v1/beneficiaries
GET    /api/v1/beneficiaries/{beneficiary}
POST   /api/v1/beneficiaries/{beneficiary}/submit-review
POST   /api/v1/beneficiaries/{beneficiary}/approve
POST   /api/v1/beneficiaries/{beneficiary}/reject
GET    /api/v1/beneficiaries/{beneficiary}/family-members
POST   /api/v1/beneficiaries/{beneficiary}/family-members
GET    /api/v1/case-files
POST   /api/v1/case-files
GET    /api/v1/case-files/{caseFile}
POST   /api/v1/case-files/{caseFile}/submit-review
POST   /api/v1/case-files/{caseFile}/approve
POST   /api/v1/case-files/{caseFile}/reject
GET    /api/v1/case-files/{caseFile}/notes
POST   /api/v1/case-files/{caseFile}/notes
GET    /api/v1/case-files/{caseFile}/documents
POST   /api/v1/case-files/{caseFile}/documents
GET    /api/v1/donors
POST   /api/v1/donors
GET    /api/v1/campaigns
POST   /api/v1/campaigns
POST   /api/v1/campaigns/{campaign}/activate
GET    /api/v1/donations
POST   /api/v1/donations
POST   /api/v1/donations/{donation}/confirm
GET    /api/v1/donations/{donation}/receipt
GET    /api/v1/warehouses
POST   /api/v1/warehouses
GET    /api/v1/inventory-items
POST   /api/v1/inventory-items
GET    /api/v1/stock/lots
POST   /api/v1/stock/lots
GET    /api/v1/stock/movements
POST   /api/v1/stock/movements/receive
POST   /api/v1/stock/movements/adjust
GET    /api/v1/stock/summary
GET    /api/v1/stock/low-stock
GET    /api/v1/stock/expiring
GET    /api/v1/aid-batches
POST   /api/v1/aid-batches
POST   /api/v1/aid-batches/{aidBatch}/submit-approval
POST   /api/v1/aid-batches/{aidBatch}/approve
POST   /api/v1/aid-batches/{aidBatch}/cancel
POST   /api/v1/aid-batches/{aidBatch}/complete
GET    /api/v1/aid-batches/{aidBatch}/distributions
POST   /api/v1/aid-batches/{aidBatch}/distributions
GET    /api/v1/aid-batches/{aidBatch}/stock-check
GET    /api/v1/aid-distributions/{distribution}
POST   /api/v1/aid-distributions/{distribution}/items
POST   /api/v1/aid-distributions/{distribution}/mark-delivered
POST   /api/v1/aid-distributions/{distribution}/mark-failed
POST   /api/v1/aid-distributions/{distribution}/reschedule
POST   /api/v1/aid-distributions/{distribution}/proof
GET    /api/v1/reports/dashboard
GET    /api/v1/reports/donations
GET    /api/v1/reports/campaigns
GET    /api/v1/reports/beneficiaries
GET    /api/v1/reports/case-files
GET    /api/v1/reports/distributions
GET    /api/v1/reports/inventory
GET    /api/v1/reports/audit-logs
POST   /api/v1/exports
GET    /api/v1/exports
GET    /api/v1/exports/{export}/download
```

Useful public endpoints:

```txt
GET  /api/v1/public/organization
GET  /api/v1/public/campaigns
GET  /api/v1/public/campaigns/{slug}
GET  /api/v1/public/stats
GET  /api/v1/public/reports
POST /api/v1/public/donations
```

The public endpoints do not require authentication. They only return public-safe organization fields, public campaigns, aggregate statistics, and placeholder donation intent responses. Beneficiary, donor, case, inventory, receipt, and audit details are intentionally excluded.

## Test Commands

Backend:

```bash
cd apps/api
composer lint
composer test
```

Frontend:

```bash
cd apps/admin
npm run lint
npm run test
npm run build
```

## GitHub Push Notes

The following local planning files are intentionally ignored and should not be pushed:

- `OpenImpact_OS_Project_Brief.md`
- `docs/`

Environment files are also ignored:

- `.env`
- `.env.*`

Only `.env.example` files should be committed.

## Roadmap

Next implementation phase:

1. Open-source release readiness
2. Contributor documentation
3. Security and privacy hardening
4. Deployment and production operations guide

Release hardening will follow after the notification and automation foundation is stable.
