# Furniture authentication foundation — isolated progress

**Historical checkpoint; superseded by `FURNITURE-OWNER-AND-LOCK-PROGRESS.md`.** This earlier authentication slice remains not safe for deployment. The owner approved beginning the isolated upgrade. This work is in `apps/furniture-platform/`, the independent Furniture foundation; it does not change or connect the Interior installation. No hosting, real database, provider, customer data or production operation occurred. The approved admin design remains unchanged and is not yet mounted in this Laravel app.

## Changes

- Added a named login limiter: five attempts/minute per normalized email + IP and 30 attempts/minute per IP across accounts. Cache keys use hashes rather than raw email addresses. This is a development baseline, not a complete credential-abuse, distributed-cache, account lockout or proxy-trust policy. Its effectiveness depends on correct client-IP/proxy and cache configuration on the actual host.
- Applied private, no-store and noindex headers to normal responses on login and authenticated workspace routes. This is not an access gate or proof all web-server/error responses are protected.
- Added synthetic checks for invalid/unknown accounts, no password flashed, session rotation, malformed input, per-account and cross-account IP throttling, and header behavior. Existing login/logout/guest/placeholder/no-default-user checks remain. The initial admin provisioning flow, reset/recovery, 2FA decision, roles and record-level authorization are still unimplemented.
- Extended read-only preflight to flag minimum CLI extension gaps; its list is not exhaustive, does not inspect Hostinger's web PHP, and never certifies readiness.

## Test evidence and limitations

1. Obtained temporary native PHP **8.4.21** from `NativePHP/php-bin` commit `c4d0b4ad6df1f0fef196c22b2cac0d26b4658b38`, verified downloaded ZIP Git blob `cc2591752b49bca8c73b897b5c933cb8122ab5d8`, and used it only in `/tmp`. This static build includes PDO SQLite but **does not include `xmlwriter` or `pdo_mysql`**. It is not the Hostinger runtime.
2. Obtained Composer **2.10.1** from the official `composer/getcomposer.org` repository and verified the archive against its adjacent SHA-256 (`345b9c6a98da5c30dcbd4b0d99fc8710bf0ae98a3898eea18f7b2ad9dec93f06`). `composer validate --no-check-lock --no-check-publish` passes for the Furniture manifest. Packagist is inaccessible from this sandbox; no Composer lock or locked native dependency install was generated.
3. A disposable copy of the Furniture app used temporary resolver-generated third-party dependencies from a prior experiment and a manually limited package-discovery cache. To execute the test suite with this incomplete native PHP build, a disposable PHPUnit entry point bypassed the standard extension-presence check: **11 tests / 122 assertions passed** with SQLite, PHPUnit 13.4.7 (the manifest asks for ^12.5) and no coverage. This is a useful synthetic regression check, **not** a standard PHPUnit run, a Furniture lockfile test or full security certification. The previous PHP-WASM process crashed on these combined cases; native success indicates the cases can execute in this experimental setup but does not establish the WASM root cause.
4. On localhost only, a temporary native PHP server and a newly migrated SQLite database with a synthetic account exercised the actual web middleware outside `testing` mode. Login without CSRF returned 419; valid login returned 302; authenticated `/admin` returned the expected pending 503; logout without CSRF returned 419; logout with a valid current XSRF token returned 302; `/admin` after logout redirected to login. This used the same unreviewed/unlocked dependency tree, no browser or HTTPS and no Hostinger data. The local server was stopped.
5. Updated preflight tests **4/4 passed**. With temporary native binaries on PATH, preflight finds missing `pdo_mysql`/`xmlwriter`, missing Furniture lock and vendor, and missing Astro app; its verdict remains **NO-GO**. A Vite build manifest in local ignored output is not a distributable release.

## Next gates

- Produce a reviewed Furniture Composer lock using reachable official package metadata and a complete PHP extension set, then run unmodified locked Composer install, normal PHPUnit, MySQL and security tests.
- Specify and implement secure first-admin provisioning, account recovery, 2FA policy, role/permission and record visibility; never create a default password or real account in a distributed SQL/ZIP.
- Connect the approved WOODEX shell to authenticated Laravel/Livewire without changing the design. Add browser tests for cookie security, CSRF, access denial, mobile/desktop, missing/stale sessions and pending states.
- Define Furniture Leads/Contacts data and duplicate/ownership rules before real record writes; external messaging/payment stays off. The staging SQL/ZIP and hosted tests remain later, separately approved release work.
