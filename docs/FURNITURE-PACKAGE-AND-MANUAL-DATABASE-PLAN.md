# Furniture staging package and manual database plan

## Status: not ready to upload

The owner requested a complete ZIP, a separate SQL file, simple manual installation and completing pending functionality using Livewire where appropriate. This document records the plan, **not a deployable ZIP, SQL export or permission to access hosting**. The application, MySQL compatibility, full frontend and staging acceptance are incomplete. Do not upload the current workspace or the Interior reference as a release. No Hostinger access, remote database creation or production change has occurred.

A new CI-only disposable MySQL round-trip is being added: migrations run on a fresh synthetic database; a test SQL file is created without destructive DROP/CREATE DATABASE statements, then imported into another fresh database. It contains schema **plus** Laravel migration history and the small internal baseline/guard rows that migrations insert; a truly schema-only export would leave those required defaults missing. The check compares tables, columns, indexes and foreign keys, verifies no accounts/business rows are included, performs transactional synthetic lead writes, and tests a future additive migration. The SQL exists only temporarily on the CI runner; it is not a downloadable release. CI evidence on one MySQL version does not establish compatibility with the actual Hostinger database, existing data, concurrent workloads or deployment readiness. Confirm the exact job result for the release commit; do not assume it passed.

Furniture source: `woodex420/furniture-os`, session branch `arena/c3010afd-furniture-os`. The owner selected `main` as the eventual integration/release branch and `staging.blackibex.net` as the target. Work remains on the assigned session branch, with a reviewed pull request to `main` when a release is ready. The owner chose **Hostinger Git** as the deployment controller. Do not concurrently enable a separate Actions deployment to the same target, and do not assume every `main` merge should auto-deploy.

## Recommendation

1. Develop and validate the independent Furniture app. Once complete, produce a reviewed release artifact and checksums from a specific commit, plus a tested database package and a step-by-step Hostinger guide.
2. Use a manual first installation **only after** the actual staging path, document root, database version, storage, PHP/Composer and backups are verified and staging deployment is approved. The public document root must contain only the intended public entry point/assets; no source, `.git`, `.env`, private files or backups should be web-accessible. The screenshots do not establish the complete server state.
3. For subsequent releases, use one controlled Hostinger Git process **only if** its actual behavior supports safe persistent files, asset delivery, approved schema updates, rollback and a verified deployed SHA. Do not overlay a Git clone over an uploaded ZIP unless the transition is rehearsed and a non-destructive, rollback-capable process is approved; hPanel Git and a ZIP are different deployment layouts. If Hostinger cannot support a safe process, propose another compatible method first. Livewire is an application UI framework, not the mechanism that updates server code.

## ZIP — proposed contents after release gates pass

- Exact reviewed Furniture source and compatible locked dependencies; no Interior application, data or credentials.
- Frontend assets built from the same commit, a manifest of included paths and SHA-256 checksums, and documented runtime requirements.
- An installation/upgrade guide covering secure document root, staging configuration, storage, HTTPS, permissions, health verification and rollback. Actual layout must be tested for the owner's Hostinger plan; do not blindly copy Interior's root rewrite or deploy hooks.
- No `.env`, APP_KEY, real passwords, private keys, database backups, customer information, runtime session files, `.git/`, development-only demos or `node_modules`.

A ZIP containing source without the correct dependencies/assets or running installers is not a complete release. A checksum proves file identity, not security or functionality.

## SQL and database user

- Owner creates a **new dedicated** database and user in hPanel using the provider's actual naming rules. Record credentials privately in Hostinger's secure configuration; do not send passwords, tokens, keys or `.env` in chat or put them in a distributed SQL/ZIP. An SQL import generally cannot create an hPanel user with the correct hosting privileges; do not ship `CREATE USER`, passwords or hardcoded provider prefixes.
- Choose one schema authority. Prefer reviewed Laravel migrations for a new empty database; if the owner requires a phpMyAdmin upload, generate the SQL from a newly migrated disposable MySQL/MariaDB instance matching the verified host version. Include and validate the migration history and only the required internal migration-created baseline/guard rows; a strictly schema-only dump would not be a functional equivalent. Do not include user accounts or business records. Provide any approved synthetic/reference content separately. Never include real Interior/Furniture data by default.
- Re-import into a second empty disposable database of the same version and test schema, indexes, foreign keys, collation, application boot and subsequent additive migration. Verify the dump contains no destructive operations aimed at an existing installation. Record exact database engine/version and tested procedure with the artifact.
- For any existing staging database: preserve it, verify a backup and isolated restore, inspect pending schema changes and obtain specific approval. Do not run `migrate:fresh`, `db:wipe`, bulk imports or destructive seeders.

## Acceptance before providing files for upload

- [ ] Approved business workflows, Laravel/Livewire authentication, authorization and the screenshot-approved admin design are implemented and tested; Reimagin v1 storefront is complete and connected.
- [ ] Composer and frontend lockfiles, native PHP, synthetic MySQL and browser tests, dependency/security review, source/license review and immutable build all pass for the exact commit.
- [ ] Staging target and ownership, PHP/CLI extensions, MySQL version, private configuration/storage, HTTPS, backup/restore, document root, cron and release/rollback method verified.
- [ ] SQL export/re-import and schema/migration history tests pass; instructions match the actual Hostinger workflow and contain no secrets.
- [ ] Owner approves the specific staging installation. Record exact commit, checksums, migration state and hosted acceptance; production requires separate approval.
