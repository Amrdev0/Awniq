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

## Verify Before Tagging

- [ ] `composer lint`
- [ ] `composer test`
- [ ] `php artisan migrate:fresh --seed`
- [ ] `npm run lint`
- [ ] `npm run test`
- [ ] `npm run build`
- [ ] Postman collection imports successfully.
- [ ] OpenAPI snapshot validates.
- [ ] Manual demo walkthrough passes.
- [ ] Limited-role authorization smoke checks pass.
- [ ] Screenshots are captured from seeded demo data.

## Release Notes

- [ ] Version number is final.
- [ ] Known limitations are current.
- [ ] Any breaking changes are documented.
- [ ] Deployment notes are current.
- [ ] Tag is created only after the repository is clean.
