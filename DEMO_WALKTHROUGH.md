# Demo Walkthrough

This walkthrough uses only fictional seeded data.

## Start the App

Backend:

```bash
cd apps/api
php artisan migrate:fresh --seed
php artisan serve --host=127.0.0.1 --port=8000
```

Frontend:

```bash
cd apps/admin
npm run dev
```

Open:

```txt
http://127.0.0.1:5173
```

## Demo Login

Use:

```txt
admin@awniq.test
Password123!
```

Other seeded accounts:

```txt
super@awniq.test
case.manager@awniq.test
finance@awniq.test
warehouse@awniq.test
distribution@awniq.test
volunteer@awniq.test
auditor@awniq.test
```

## MVP Smoke Path

1. Log in as `admin@awniq.test`.
2. Open the dashboard and confirm seeded counts load.
3. Review beneficiaries and open a seeded beneficiary profile.
4. Review case files and submit or approve a case where the current status allows it.
5. Open campaigns and donations.
6. Create a manual donation, add allocations, confirm payment, and view the receipt.
7. Open inventory, review stock summary, low stock, and expiring stock.
8. Open aid batches, review the seeded draft batch, add or inspect distribution items.
9. Submit the batch for approval, approve it, and confirm reservation behavior.
10. Mark a distribution delivered with manual proof metadata.
11. Open reports and create a CSV export.
12. Visit `/public` and confirm the public portal only exposes public-safe data.
13. Open the notification bell and confirm workflow notifications appear.
14. On a list route, change rows per page, navigate forward/back, run a search, reload the page, and confirm the URL restores the same list state.
15. Open a beneficiary, case, donation, or distribution with related records and confirm its nested pagination changes independently from the main list.

## Admin Route Map

- Identity: `/organization`, `/branches`, `/users`, `/roles`, `/audit-logs`
- Cases: `/beneficiaries`, `/case-files`
- Finance: `/donors`, `/campaigns`, `/donations`, `/finance/payments`
- Inventory: `/warehouses`, `/inventory-items`, `/stock/summary`, `/stock/lots`, `/stock/movements`, `/stock/low-stock`, `/stock/expiring`
- Aid: `/aid-batches`, `/aid-distributions`
- Visibility and operations: `/reports`, `/settings/public-portal`, `/notifications`, `/system`

Navigation and direct routes are permission-aware. Write controls also require their matching create, update, workflow, or delete permission.

## Role Smoke Checks

- Auditor should be able to inspect records and reports but not perform write operations.
- Volunteer should have limited operational access.
- Finance officer should work primarily in donors, campaigns, donations, receipts, and finance reports.
- Warehouse manager should work primarily in warehouses, inventory, stock lots, and stock reports.
- Distribution officer should work primarily in aid batches and deliveries.

## API Smoke Checks

Postman collection:

```txt
postman/Awniq.postman_collection.json
```

OpenAPI snapshot:

```txt
openapi/openapi.yaml
```
