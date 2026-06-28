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

## Near-Term Priorities

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
