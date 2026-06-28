# Contributing to Awniq

Thank you for considering a contribution to Awniq. This project handles sensitive aid operations, so contributions should be practical, tested, and privacy-aware.

## Project Scope

Awniq is an open-source aid operations platform for NGOs, charities, and small institutions. The MVP focuses on:

- Identity, organizations, users, roles, permissions, and audit logs.
- Beneficiaries, family members, case files, case notes, and private case documents.
- Donors, campaigns, donations, allocations, payment transactions, and receipts.
- Warehouses, inventory items, stock lots, stock movements, and stock reports.
- Aid batches, distributions, delivery status, and proof metadata.
- Dashboard reports, CSV exports, public transparency APIs, and operational notifications.

Do not add payment gateways, WhatsApp, OCR, mobile apps, or advanced BI features unless they are part of an accepted roadmap issue.

## Local Setup

Backend:

```bash
cd apps/api
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan serve --host=127.0.0.1 --port=8000
```

Frontend:

```bash
cd apps/admin
npm install
cp .env.example .env
npm run dev
```

Default admin login:

```txt
admin@awniq.test
Password123!
```

## Development Rules

- Keep changes scoped to one feature, fix, or documentation improvement.
- Prefer existing Laravel, React, and TypeScript patterns already used in the repo.
- Add or update tests when behavior changes.
- Do not commit `.env`, local databases, private documents, generated exports, or real beneficiary/donor data.
- Do not commit files from the local planning-only `docs/` folder.
- Keep demo data fictional and clearly marked with `.test` domains or demo IDs.

## Quality Gates

Run these before opening a pull request:

```bash
cd apps/api
composer lint
composer test
```

```bash
cd apps/admin
npm run lint
npm run test
npm run build
```

For database-facing work, also run:

```bash
cd apps/api
php artisan migrate:fresh --seed
```

## Pull Requests

Each pull request should include:

- A short summary of the change.
- The affected backend/frontend/API areas.
- Tests run.
- Screenshots for UI changes when possible.
- Notes about migrations, seed data, permissions, or breaking changes.

## Privacy Expectations

Awniq is built for humanitarian and social-sector workflows. Treat the following as sensitive:

- Beneficiary identity, household, vulnerability, health, and case data.
- Donor contact and payment records.
- Receipts, delivery proof, documents, and audit logs.
- Organization operational data that is not explicitly public.

Public transparency features must expose only aggregate or intentionally public-safe data.
