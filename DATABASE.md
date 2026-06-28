# Database Notes

## Database Engine

Awniq is developed against MySQL for local and production-like use. The automated test suite uses SQLite in memory for fast isolated tests.

Recommended local database:

```txt
DB_CONNECTION=mysql
DB_DATABASE=awniq
```

## Organization Scoping

Most operational tables include `organization_id`. Controllers, services, reports, exports, notifications, and public portal queries should always constrain records to the current organization unless the route is explicitly system-level.

Cross-organization data leakage is a release-blocking bug.

## Core Data Areas

Identity:

- `organizations`
- `branches`
- `users`
- Spatie role and permission tables
- `audit_logs`

Beneficiaries and cases:

- `beneficiaries`
- `beneficiary_family_members`
- `case_files`
- `case_notes`
- `case_documents`

Finance:

- `donors`
- `campaigns`
- `donations`
- `donation_allocations`
- `payment_transactions`
- `receipts`
- `idempotency_keys`

Inventory:

- `warehouses`
- `inventory_items`
- `stock_lots`
- `stock_movements`

Aid distribution:

- `aid_batches`
- `aid_distributions`
- `distribution_items`
- `stock_reservations`

Reports, public portal, and automation:

- `exports`
- `organization_settings`
- `public_donation_intents`
- `operational_notifications`
- `notification_preferences`
- `notification_deduplication_keys`
- Laravel `jobs`, `cache`, and `sessions` tables

## Financial Data

Money fields use decimal database columns. Do not store monetary values in floating point columns. Keep currency explicit on donations, allocations, campaigns, cash distributions, and reports.

## Stock Data

Stock movements are append-style operational records. Current stock is derived from stock lots, remaining quantities, and reservations.

Rules:

- Receiving stock creates or updates a lot and records a stock-in movement.
- Reserving stock should not reduce physical quantity until delivery is confirmed.
- Cancelling or failing an approved distribution should release reservations when appropriate.
- Expiry tracking is item-dependent.

## Files and Exports

Private case documents and generated exports should be stored outside the public web root unless they are intentionally public. Downloads must go through authorized API endpoints.

## Demo Data

`php artisan migrate --seed` creates fictional demo data through `DemoDataSeeder`.

Seeded data includes:

- One demo organization.
- Two branches.
- Users for each MVP role.
- Beneficiaries with mixed statuses.
- Case files and notes.
- Donors, campaigns, donations, allocations, transactions, and receipts.
- Warehouses, items, lots, movements.
- One draft aid batch with distribution items.

## Backup and Restore

Before production use, configure routine database and private file backups.

Minimum backup coverage:

- MySQL database.
- Private case document storage.
- Generated exports if they must be retained.
- Application `.env` secrets stored in a secure secrets manager.

Restore testing should be performed before relying on backups. A backup that has never been restored should not be treated as reliable.

## Retention Notes

Awniq does not yet enforce automated retention policies. Each organization should define retention rules for:

- Beneficiary and case records.
- Case documents and delivery proof.
- Donation records and receipts.
- Audit logs.
- Generated CSV exports.
