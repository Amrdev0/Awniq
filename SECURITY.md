# Security Policy

## Supported Versions

Awniq is currently pre-1.0. Security fixes are accepted against the `main` branch until the first stable release line is created.

## Reporting a Vulnerability

Do not open a public GitHub issue for a suspected vulnerability.

Use GitHub private vulnerability reporting if it is available on the repository, or contact the maintainers privately. Include:

- A clear description of the issue.
- Steps to reproduce.
- Affected endpoints, screens, roles, or data models.
- Impact assessment.
- Suggested fix if known.

Maintainers should acknowledge a valid report within a reasonable time and coordinate a fix before public disclosure.

## Sensitive Areas

Please be especially careful with:

- Authentication and Sanctum tokens.
- Role and permission checks.
- Organization scoping and cross-organization access.
- Beneficiary, case, donor, receipt, and delivery proof data.
- Public transparency endpoints.
- File upload, storage, and document download paths.
- Export generation and CSV downloads.
- Scheduler and queue health endpoints.

## Safe Testing Rules

- Use only local demo data or data you are authorized to test.
- Do not attempt to access third-party systems.
- Do not run destructive tests against public or production deployments.
- Do not include secrets, tokens, or real personal data in reports.
