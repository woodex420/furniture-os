# Furniture customer read foundation

**Status: in progress, read-only, owner-only, not release-ready.** This is independent Furniture code. Interior remains reference-only: no Interior data, credentials, migrations or application actions have been copied or connected. No hosting access, real database, data import or deployment was performed.

## This slice

- Added provisional `contacts` and `leads` tables with a nullable contact relationship and no seed data. This migration is a development candidate, **not an approved production schema or SQL handoff**. Its rollback drops these tables and would destroy their data; never apply it to an existing installation without reviewed migrations, backup and restore validation.
- Authenticated `/admin/leads` and `/admin/contacts` reuse the approved workspace shell and read server-side records, 10 per page, with empty and loading/offline states. The owner can search leads by title/contact name and contacts by name/email/phone. Search input is limited to 100 characters, bound as a parameter, and LIKE metacharacters are escaped as literals. Pagination ordering has a stable ID tie-breaker. Search collation and non-ASCII case behavior depend on the database; full-table wildcard search has not been performance-tested.
- Both routes and Livewire renders check dedicated `leads.view` or `contacts.view` gates in addition to workspace access. Both gates are provisionally owner-only; the owner can see all Furniture CRM records. **There is no staff/record-level ownership policy yet.** The UI links are hidden if the corresponding ability is denied. Livewire updates use the protected workspace endpoint and private/no-store headers. Customer fields are HTML escaped.
- There are no customer create, edit, delete, merge, import, export or assignment routes; models are guarded against mass assignment. There are no provider calls or public customer APIs. UI explicitly marks these actions pending. This is not a completed CRM or authorization model.

## Evidence

- Temporary native PHP 8.4.21, synthetic SQLite and the locked dependency tree: **31 tests / 305 assertions passed**. Because local PHP lacks `xmlwriter`, this used the modified PHPUnit entry point documented in `FURNITURE-OWNER-AND-LOCK-PROGRESS.md`; it is not the standard complete runtime. Pint passed the nine changed PHP files. Route-cache test for the customer suite passed **8 tests / 105 assertions**. GitHub [run 44027031526](https://github.com/woodex420/furniture-os/actions/runs/44027031526) passed the standard native locked install, PHPUnit, Composer audit, frontend build and preview/preflight tests for SHA `6b13da76af591e8500849702beb1ff125c911472`.
- Tests cover guest/non-owner denial, separately revoked abilities, real Livewire HTTP refresh and no-store, search bounds and malformed values, literal wildcard/injection-looking input, escaping, nulls, pagination/reset, no default customer records and unavailable write routes. The tests do not establish a staff visibility or cross-owner policy.
- A local browser with a disposable SQLite database and synthetic records exercised authenticated lead/contact navigation, pagination, search, offline/reconnect indication, logout/guest redirect and 320/375/768/1440 widths without persistent root overflow. The local test server was stopped. This is not hosted HTTPS, a full accessibility assessment or real data acceptance.

## Required before expanding access or accepting data

1. Agree customer/lead fields and lifecycle, role and record-level visibility, assignment and cross-owner duplicate/merge rules. Prevent duplicate-related data leaks, define concurrency/idempotency and audit history before exposing writes. Do not reuse the reference `CaptureLead` action; its cross-owner behavior needs special care.
2. Review and test schema changes and migrations against the eventual verified MySQL/MariaDB version with synthetic data, upgrade, rollback, backup and restore. Do not use the destructive down migration as a data rollback plan.
3. Implement and verify create/edit/assignment/import/export, audit logs, permissions and comprehensive loading/error/offline and accessible browser states as separate slices. No live messaging, payment or other provider calls without separate approval.
4. Complete application, security review, exact-SHA CI, staging environment checks and separate hosting/deployment authorization before a ZIP, SQL export or Hostinger installation is treated as ready.
