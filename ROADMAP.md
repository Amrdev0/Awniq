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

The backend/API foundation exists, but the current admin frontend is still a single read-only overview page. Awniq should not be considered a usable product MVP until staff can operate every core module from the browser through a sidebar-driven control panel.

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

- Build the full admin control system frontend with sidebar navigation, routed module pages, forms, detail pages, workflow actions, and permission-aware controls.
- Complete manual release smoke testing with seeded demo data.
- Add real screenshots from the seeded demo.
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
- [ ] Expand remaining frontend API clients with write operations.
- [ ] Implement warehouse, inventory item, stock lot, stock movement, low-stock, and expiring-stock screens.
- [ ] Implement aid batch, distribution, item, stock-check, delivery, failure, reschedule, and proof screens.
- [ ] Implement reports, exports, public portal settings, notifications, queue health, and scheduler screens.
- [ ] Add permission-aware UI tests for limited roles.
- [ ] Run browser manual demo walkthrough.
- [ ] Capture seeded demo screenshots.
- [ ] Update release checklist when complete.
