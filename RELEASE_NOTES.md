# Release Notes

## 0.10.0 - MVP Release Candidate

Date: 2026-06-28

This release candidate packages the Awniq MVP foundation for public review and manual demo testing.

### Included

- Laravel API and React admin monorepo.
- Sanctum authentication.
- Organization, branch, user, role, permission, and audit-log management.
- Beneficiary profiles, family members, case files, case notes, and private case documents.
- Donors, campaigns, donations, allocations, payment transactions, receipts, and idempotent manual confirmation.
- Warehouses, inventory items, stock lots, stock movements, low-stock and expiring-stock reporting.
- Aid batches, distributions, distribution items, stock reservation, delivery confirmation, failed delivery, rescheduling, and proof metadata.
- Dashboard metrics, internal reports, CSV exports.
- Public-safe transparency portal APIs and frontend route.
- In-app operational notifications, preferences, scheduled reminders, queue health, and scheduler visibility.
- Fictional demo seed data.
- OpenAPI snapshot and Postman collection.
- GitHub Actions CI for backend and frontend checks.
- Open-source governance files.

### Known Limitations

- No production payment gateway is integrated.
- Public donation intent is a placeholder, not a real checkout.
- Email notification delivery is not enabled yet.
- No SMS or WhatsApp channel exists yet.
- No mobile app exists yet.
- Screenshots are not checked in yet.
- OpenAPI is manually maintained and can drift if contributors forget to update it.
- RTL and localization are planned but not complete.
- Data retention policies are documented but not automated.

### Upgrade Notes

Fresh local setup:

```bash
cd apps/api
php artisan migrate:fresh --seed
```

Existing local database:

```bash
cd apps/api
php artisan migrate --seed
```

Frontend:

```bash
cd apps/admin
npm install
npm run build
```
