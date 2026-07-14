# Awniq Roadmap

## MVP Definition

The MVP is the minimum useful aid operations platform for a small NGO that needs to track people, cases, funds, stock, deliveries, and basic transparency without relying on spreadsheets.

MVP included:

- Phase 01: repository setup and architecture.
- Phase 02: authentication, organizations, users, roles, permissions, and audit logs.
- Phase 03: beneficiaries and case management.
- Phase 04: donors, campaigns, donations, allocations, payment transactions, and receipts.
- Phase 05: inventory and warehouses.
- Phase 06: aid batches and distribution.
- Phase 07: dashboard, reports, and CSV exports.

Included in the first public release candidate:

- Phase 08: public transparency portal.
- Phase 09: notifications, scheduler, and automation.
- Phase 10: open-source release readiness.

Release blocker discovered after Phase 10:

- Phase 11: full admin control system frontend.

The backend/API foundation and full sidebar-driven admin control frontend now exist. The seeded browser walkthrough and Phase 11 screenshot pass are complete.

## Phase Dependencies

1. Identity and permissions must exist before any private operational module.
2. Beneficiaries and case files are required before targeted aid distribution.
3. Campaigns and donations are required before donation-funded reporting.
4. Inventory must exist before stock-backed aid batches.
5. Aid distribution depends on beneficiaries, cases, campaigns, warehouses, and stock.
6. Reports depend on all operational modules.
7. Public transparency depends on finance, distribution, and portal settings.
8. Notifications depend on workflows and operational data.
9. Release readiness depends on all included MVP surfaces being documented and tested.
10. Full admin control UI depends on the implemented backend APIs and blocks a true product release.

## Near-Term Priorities

- Implement Phase 12 pagination and scalable data tables so operational screens can navigate beyond the first API page.
- Complete manual release smoke testing with seeded demo data.
- Continue Phase 12 large-dataset browser verification.
- Expand authorization tests around limited roles.
- Add richer frontend coverage for critical workflows.
- Improve OpenAPI generation or validation automation.
- Add production deployment examples for common hosting setups.

## Future Features

- Payment gateway integrations.
- Email and SMS notification channels.
- WhatsApp integration.
- Mobile-friendly field delivery app.
- OCR-assisted document intake.
- Advanced public reporting pages.
- Multi-language and RTL hardening.
- Advanced audit retention controls.
- Import tools for legacy spreadsheet data.

These are not part of the current MVP unless accepted into a scoped issue.

## Phase 11: Admin Control System Frontend

This phase is release-blocking.

Required scope:

- Admin shell with sidebar, topbar, breadcrumbs, and notification access.
- Permission-aware navigation and action visibility.
- Module pages for organization, branches, users, roles, permissions, beneficiaries, case files, donors, campaigns, donations, warehouses, inventory, stock, aid batches, distributions, reports, exports, public portal settings, notifications, and audit logs.
- Create/edit/detail screens for core records.
- Workflow actions for review, approval, rejection, donation confirmation, receipt generation, stock receipt/adjustment, batch approval, delivery confirmation, failure, rescheduling, and proof upload.
- Frontend validation, loading, empty, error, unauthorized, and success states.
- Browser-based demo walkthrough without relying on Postman.

Checklist:

- [x] Build admin shell and sidebar navigation.
- [x] Move dashboard to a real `/dashboard` route.
- [x] Add route map for all operational modules.
- [x] Expand identity frontend API clients with write operations.
- [x] Implement organization, branches, users, roles, permissions, and audit log screens.
- [x] Expand beneficiary and case frontend API clients with write operations.
- [x] Implement beneficiary, family member, case file, note, and document screens.
- [x] Expand finance frontend API clients with write operations.
- [x] Implement donor, campaign, donation, allocation, payment transaction, and receipt screens.
- [x] Expand remaining frontend API clients with write operations.
- [x] Implement warehouse, inventory item, stock lot, stock movement, low-stock, and expiring-stock screens.
- [x] Implement aid batch, distribution, item, stock-check, delivery, failure, reschedule, and proof screens.
- [x] Implement reports, exports, public portal settings, notifications, queue health, and scheduler screens.
- [x] Add permission-aware UI tests for limited roles.
- [x] Run browser manual demo walkthrough.
- [x] Capture seeded demo screenshots.
- [x] Update release checklist for the implemented control system.

## Phase 12: Scalable Data Tables and Pagination

This phase prevents growing organizations from being limited to the first 15-50 records returned by paginated APIs. See `docs/12-scalable-data-tables-and-pagination.md` for the complete implementation plan.

Checklist:

- [x] Inventory every paginated and intentionally unpaginated collection endpoint.
- [x] Add shared frontend pagination response and query parameter types.
- [x] Preserve Laravel `links` and `meta` in API clients instead of returning only `data`.
- [x] Add reusable pagination controls with page, total, range, and per-page selection.
- [x] Keep page, search, and supported filters in the URL query string.
- [x] Reset to page one when filters, search, sorting, or page size change.
- [x] Add pagination to identity and audit screens.
- [x] Add pagination to beneficiary and case screens, including nested collections.
- [x] Add pagination to finance screens, including related donations, allocations, and transactions.
- [x] Add pagination to inventory and stock screens.
- [x] Add pagination to aid batch, eligibility, distribution, and item screens.
- [x] Add pagination to exports, notifications, and public campaigns.
- [x] Keep fixed-size configuration and aggregate datasets intentionally unpaginated.
- [x] Add loading, empty, error, boundary, and permission-aware pagination states.
- [x] Add frontend tests for navigation, URL state, filters, and page-size changes.
- [x] Add backend tests for page bounds, `per_page` limits, scoping, and filter combinations.
- [ ] Run a seeded large-dataset browser walkthrough.
- [x] Update API documentation, demo walkthrough, and release checklist.
