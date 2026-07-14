# Release Checklist

Use this checklist before tagging a public release.

## Required

- [x] License file exists.
- [x] Contribution guide exists.
- [x] Code of conduct exists.
- [x] Security policy exists.
- [x] Pull request template exists.
- [x] Issue templates exist.
- [x] Root README documents setup, demo users, tests, API docs, and known limitations.
- [x] Deployment guide exists.
- [x] Architecture notes exist.
- [x] Database notes exist.
- [x] Demo walkthrough exists.
- [x] Release notes exist.
- [x] OpenAPI snapshot exists.
- [x] Postman collection exists.
- [x] Demo seed data exists.
- [x] `/api/v1/health` exists.
- [x] `/api/v1/version` exists.
- [x] Full admin control system exists with sidebar navigation, module routes, forms, detail pages, workflow actions, and permission-aware controls.
- [x] Growing operational collections use server-driven pagination with scoped totals, search, URL state, and bounded page sizes.

## Verify Before Tagging

- [x] `composer lint`
- [x] `composer test`
- [x] `php artisan migrate:fresh --seed`
- [x] `npm run lint`
- [x] `npm run test`
- [x] `npm run build`
- [x] Postman collection JSON parses successfully.
- [ ] Postman collection imports successfully.
- [ ] OpenAPI snapshot validates.
- [x] Automated MVP release smoke workflow passes.
- [x] Limited-role authorization smoke checks pass.
- [x] Automated pagination, search, page-boundary, page-size, and large-dataset checks pass.
- [x] Browser-based admin workflow passes without Postman.
- [x] Manual demo walkthrough passes.
- [x] Screenshots are captured from seeded demo data.

## Release Notes

- [x] Version number is final.
- [x] Known limitations are current.
- [x] Any breaking changes are documented.
- [x] Deployment notes are current.
- [ ] Tag is created only after the repository is clean.
