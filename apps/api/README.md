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

Protected beneficiary and case-management endpoints:

```txt
GET    /api/v1/beneficiaries
POST   /api/v1/beneficiaries
GET    /api/v1/beneficiaries/{beneficiary}
PATCH  /api/v1/beneficiaries/{beneficiary}
DELETE /api/v1/beneficiaries/{beneficiary}
POST   /api/v1/beneficiaries/{beneficiary}/submit-review
POST   /api/v1/beneficiaries/{beneficiary}/approve
POST   /api/v1/beneficiaries/{beneficiary}/reject
POST   /api/v1/beneficiaries/{beneficiary}/suspend
POST   /api/v1/beneficiaries/{beneficiary}/reactivate
GET    /api/v1/beneficiaries/{beneficiary}/family-members
POST   /api/v1/beneficiaries/{beneficiary}/family-members
PATCH  /api/v1/beneficiaries/{beneficiary}/family-members/{familyMember}
DELETE /api/v1/beneficiaries/{beneficiary}/family-members/{familyMember}
GET    /api/v1/case-files
POST   /api/v1/case-files
GET    /api/v1/case-files/{caseFile}
PATCH  /api/v1/case-files/{caseFile}
DELETE /api/v1/case-files/{caseFile}
POST   /api/v1/case-files/{caseFile}/submit-review
POST   /api/v1/case-files/{caseFile}/approve
POST   /api/v1/case-files/{caseFile}/reject
POST   /api/v1/case-files/{caseFile}/suspend
POST   /api/v1/case-files/{caseFile}/close
POST   /api/v1/case-files/{caseFile}/reopen
GET    /api/v1/case-files/{caseFile}/notes
POST   /api/v1/case-files/{caseFile}/notes
GET    /api/v1/case-files/{caseFile}/documents
POST   /api/v1/case-files/{caseFile}/documents
GET    /api/v1/case-files/{caseFile}/documents/{caseDocument}/download
DELETE /api/v1/case-files/{caseFile}/documents/{caseDocument}
```

The seeded database includes five beneficiaries, family members, five case files, and internal notes for manual testing.

## Commands

```bash
composer lint
composer test
```
