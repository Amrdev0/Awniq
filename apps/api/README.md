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
GET    /api/v1/notifications
GET    /api/v1/notifications/unread-count
POST   /api/v1/notifications/{notification}/mark-read
POST   /api/v1/notifications/mark-all-read
GET    /api/v1/notification-preferences
PATCH  /api/v1/notification-preferences
GET    /api/v1/system/scheduled-jobs
GET    /api/v1/system/queue-health
GET    /api/v1/organization
GET    /api/v1/settings/public-portal
PATCH  /api/v1/settings/public-portal
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

Protected finance endpoints:

```txt
GET    /api/v1/donors
POST   /api/v1/donors
GET    /api/v1/donors/{donor}
PATCH  /api/v1/donors/{donor}
DELETE /api/v1/donors/{donor}
GET    /api/v1/donors/{donor}/donations
GET    /api/v1/campaigns
POST   /api/v1/campaigns
GET    /api/v1/campaigns/{campaign}
PATCH  /api/v1/campaigns/{campaign}
DELETE /api/v1/campaigns/{campaign}
POST   /api/v1/campaigns/{campaign}/activate
POST   /api/v1/campaigns/{campaign}/pause
POST   /api/v1/campaigns/{campaign}/complete
POST   /api/v1/campaigns/{campaign}/cancel
GET    /api/v1/donations
POST   /api/v1/donations
GET    /api/v1/donations/{donation}
PATCH  /api/v1/donations/{donation}
POST   /api/v1/donations/{donation}/confirm
POST   /api/v1/donations/{donation}/cancel
POST   /api/v1/donations/{donation}/refund
GET    /api/v1/donations/{donation}/receipt
POST   /api/v1/donations/{donation}/receipt
GET    /api/v1/donations/{donation}/allocations
POST   /api/v1/donations/{donation}/allocations
GET    /api/v1/donations/{donation}/payment-transactions
GET    /api/v1/payment-transactions/{paymentTransaction}
```

Donation confirmation supports an optional `Idempotency-Key` header so repeated confirmation requests return the same stored result without double-counting campaign totals.

The seeded database includes four donors, three campaigns, five donations, allocations, paid transactions, and receipt records.

Protected inventory endpoints:

```txt
GET    /api/v1/warehouses
POST   /api/v1/warehouses
GET    /api/v1/warehouses/{warehouse}
PATCH  /api/v1/warehouses/{warehouse}
DELETE /api/v1/warehouses/{warehouse}
GET    /api/v1/inventory-items
POST   /api/v1/inventory-items
GET    /api/v1/inventory-items/{inventoryItem}
PATCH  /api/v1/inventory-items/{inventoryItem}
DELETE /api/v1/inventory-items/{inventoryItem}
GET    /api/v1/inventory-items/{inventoryItem}/stock
GET    /api/v1/inventory-items/{inventoryItem}/movements
GET    /api/v1/stock/lots
POST   /api/v1/stock/lots
GET    /api/v1/stock/lots/{stockLot}
GET    /api/v1/stock/movements
POST   /api/v1/stock/movements/receive
POST   /api/v1/stock/movements/adjust
GET    /api/v1/stock/movements/{stockMovement}
GET    /api/v1/stock/summary
GET    /api/v1/stock/low-stock
GET    /api/v1/stock/expiring
```

Stock quantity changes are recorded through stock movements. Direct stock quantity edits are intentionally not exposed.

The seeded database includes two warehouses, four inventory items, five opening stock lots, one low-stock item, and one lot expiring within 30 days.

Protected aid distribution endpoints:

```txt
GET    /api/v1/aid-batches
POST   /api/v1/aid-batches
GET    /api/v1/aid-batches/{aidBatch}
PATCH  /api/v1/aid-batches/{aidBatch}
DELETE /api/v1/aid-batches/{aidBatch}
POST   /api/v1/aid-batches/{aidBatch}/submit-approval
POST   /api/v1/aid-batches/{aidBatch}/approve
POST   /api/v1/aid-batches/{aidBatch}/cancel
POST   /api/v1/aid-batches/{aidBatch}/complete
GET    /api/v1/aid-batches/{aidBatch}/eligible-beneficiaries
GET    /api/v1/aid-batches/{aidBatch}/stock-check
GET    /api/v1/aid-batches/{aidBatch}/distributions
POST   /api/v1/aid-batches/{aidBatch}/distributions
PATCH  /api/v1/aid-batches/{aidBatch}/distributions/{distribution}
DELETE /api/v1/aid-batches/{aidBatch}/distributions/{distribution}
GET    /api/v1/aid-distributions/{distribution}
PATCH  /api/v1/aid-distributions/{distribution}
GET    /api/v1/aid-distributions/{distribution}/items
POST   /api/v1/aid-distributions/{distribution}/items
PATCH  /api/v1/aid-distributions/{distribution}/items/{item}
DELETE /api/v1/aid-distributions/{distribution}/items/{item}
POST   /api/v1/aid-distributions/{distribution}/mark-delivered
POST   /api/v1/aid-distributions/{distribution}/mark-failed
POST   /api/v1/aid-distributions/{distribution}/reschedule
POST   /api/v1/aid-distributions/{distribution}/proof
```

Batch approval reserves stock transactionally and creates `reserved` stock movements. Delivery confirmation converts reservations to `distributed` movements. Failed or cancelled distributions release reserved stock through `released` movements.

The seeded database includes one draft aid batch with one approved beneficiary and one rice distribution item. Submit and approve it manually to test stock reservation.

Protected report and export endpoints:

```txt
GET  /api/v1/reports/dashboard
GET  /api/v1/reports/donations
GET  /api/v1/reports/campaigns
GET  /api/v1/reports/beneficiaries
GET  /api/v1/reports/case-files
GET  /api/v1/reports/distributions
GET  /api/v1/reports/inventory
GET  /api/v1/reports/audit-logs
POST /api/v1/exports
GET  /api/v1/exports
GET  /api/v1/exports/{export}
GET  /api/v1/exports/{export}/download
```

Report endpoints accept shared filters such as `date_from`, `date_to`, `branch_id`, `warehouse_id`, `campaign_id`, `status`, `payment_method`, `donor_type`, and `category` where relevant.

Exports are generated synchronously as CSV files for the local MVP and require both `reports.export` and the source report view permission.

Public transparency endpoints:

```txt
GET  /api/v1/public/organization
GET  /api/v1/public/campaigns
GET  /api/v1/public/campaigns/{slug}
GET  /api/v1/public/stats
GET  /api/v1/public/reports
POST /api/v1/public/donations
```

Public endpoints are unauthenticated and rate limited. They use public-safe resources and must not expose donor identities, beneficiary identities, case details, audit logs, private campaign records, or internal operational notes.

The seeded demo organization has its public portal enabled, public reports enabled, and public donation intake disabled. Use `PATCH /api/v1/settings/public-portal` as `admin@awniq.test` to toggle public settings for manual testing.

Protected notification and automation endpoints:

```txt
GET    /api/v1/notifications
GET    /api/v1/notifications/unread-count
POST   /api/v1/notifications/{notification}/mark-read
POST   /api/v1/notifications/mark-all-read
GET    /api/v1/notification-preferences
PATCH  /api/v1/notification-preferences
GET    /api/v1/system/scheduled-jobs
GET    /api/v1/system/queue-health
```

Scheduled notification jobs are registered in `routes/console.php`. Run the scheduler in production with:

```bash
php artisan schedule:work
```

Run queued work with:

```bash
php artisan queue:work
```

MVP notification categories are `cases`, `finance`, `inventory`, `aid_distribution`, and `system`. Email, SMS, and WhatsApp delivery are intentionally deferred; `email_enabled` is stored as `false` until a mail/channel integration is configured.

Default routing sends case alerts to assigned case managers and case-manager roles, finance alerts to finance officers, stock alerts to warehouse managers, aid approval/delivery alerts to warehouse or distribution roles, and critical operational alerts to organization admins where relevant.

## Commands

```bash
composer lint
composer test
```
