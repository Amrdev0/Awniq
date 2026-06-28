# Awniq Architecture

## Overview

Awniq is a monorepo with a Laravel API backend and a React admin frontend.

```txt
apps/
  api/      Laravel API, database, seeders, tests, queues, scheduler
  admin/    React TypeScript admin and public portal frontend
openapi/    OpenAPI contract snapshot
postman/    Postman collection for manual API testing
```

The backend is the source of truth for authorization, organization scoping, workflow state, auditability, and data validation. The frontend is a client of the API and should not enforce business rules without matching backend validation.

## Backend

The Laravel API is organized by domain:

- Identity: organizations, branches, users, roles, permissions, audit logs.
- Case management: beneficiaries, family members, case files, notes, documents.
- Finance: donors, campaigns, donations, allocations, transactions, receipts.
- Inventory: warehouses, inventory items, stock lots, stock movements, stock reports.
- Aid distribution: aid batches, distributions, distribution items, reservations, delivery status.
- Reports: dashboard metrics, report endpoints, CSV exports.
- Public portal: public organization, campaigns, aggregate stats, donation intent placeholders.
- Notifications: operational notifications, preferences, scheduled reminders, queue/scheduler visibility.

Common response shape:

```json
{
  "data": {},
  "message": "Operation completed successfully."
}
```

## Authorization

Authentication uses Laravel Sanctum bearer tokens. Authorization is permission-based through Spatie Laravel Permission.

Rules:

- API routes must declare permission middleware unless intentionally public.
- Organization-scoped records must be constrained by the authenticated user's `organization_id`.
- Public routes must never expose private beneficiary, donor, case, inventory, receipt, document, or audit details.
- Demo users represent real operating roles and should stay aligned with seeded permissions.

## Workflow Boundaries

State transitions belong in services when they coordinate more than one model:

- Donation confirmation updates payment status, transactions, receipts, and campaign totals.
- Stock reservation and release coordinates lots, reservations, and distribution status.
- Aid delivery updates distribution status, stock quantities, and proof metadata.
- Notifications are routed through notification services so workflow controllers do not duplicate routing rules.

## Frontend

The admin frontend uses React, TypeScript, Vite, React Router, TanStack Query, Tailwind CSS, and small API service modules.

Frontend expectations:

- Route protection should reflect backend permissions.
- Mutations should invalidate relevant query keys.
- Empty, loading, and error states should be present for operational screens.
- Public portal pages must only use public API endpoints.

## Scheduler and Queue

The scheduler registers operational reminder jobs in `apps/api/routes/console.php`.

Production deployments should run:

```bash
php artisan schedule:run
php artisan queue:work
```

Local development can use:

```bash
php artisan schedule:work
php artisan queue:work
```

## API Contract

The maintained public contract is:

```txt
openapi/openapi.yaml
postman/Awniq.postman_collection.json
```

When an endpoint changes, update the controller/request/resource, tests, OpenAPI file, Postman collection, and README examples in the same pull request.
