# Woodex Furniture OS — security findings

Generated 2026-11-05. Static source audit only: no live application, database, or authenticated penetration test was performed. **No credential, password, token, or secret value is reproduced in this report.** Severity reflects potential impact if the archived code is deployed as-is and reachable from the internet.

Companion machine-readable scan counts: `STATIC_SCAN.json`. Complete path/line evidence is in the source files under the three extracted trees.

## Severity summary

| Severity | Count | Headline |
|---|---:|---|
| Critical | 3 | Hardcoded DB credentials; unauthenticated legacy admin writes; unrestricted CKEditor upload |
| High | 5 | Debug artifacts in web root; unauthenticated installer/reconnect; default/demo credentials; web-root private secrets; legacy CSRF |
| Medium | 10 | Conflicting rewrites, missing CSP/HSTS, wildcard MCP CORS, cron secret in URL, fail-open Turnstile, SSRF surface, SVG upload, no legacy rate limiting, CDN without SRI, EOL/unlocked dependencies |
| Low | 4 | Status fingerprinting, no CI/secret scanning, inconsistent config copies, schema drift |

## Critical findings

### C-01 — Hardcoded production database credentials
**Evidence:** `site:lbpanel/config/connection.php:3-6`; `frontstore:connection.php:23-25`; `frontstore:lbpanel/config/connection.php:23-25`; `site:error_log:1-2` (a second DB username/password appears inside uncaught `mysqli` stack traces). `frontstore:config/connection-real.php:5-10` describes a "verbatim original" connection file. Values are redacted here.

**Impact:** Anyone who obtains the archives or the deployed files can read or modify the legacy ecommerce database. The `error_log` leak means credentials may also be exposed through web-accessible log files.

**Remediation:** Rotate every database user/password and any API keys that may have shared those credentials. Move configuration to environment variables or a secret store outside `DocumentRoot`. Delete `error_log`, `php.ini`, `.user.ini`, `session_test.php`, `.DS_Store`, and `sessions/` from deployable artifacts. Replace the archives in Drive with sanitized copies.

### C-02 — Legacy admin write endpoints lack an authentication guard
**Evidence:** `site/lbpanel` contains 45 PHP files calling `session_start()`; only 17 contain a recognizable `$_SESSION` authorization check. A static scan found 26 write-capable endpoints with no auth check, including `php/process_add_product.php:1-5`, `php/process_add_coupon.php:1-5`, `brand_management.php`, `edit_product.php`, `add-users.php`, `pos/api/save_transaction.php:1-7`, and `includes/ckeditor/upload.php`. No `lbpanel/.htaccess` or front-controller guard was found in the archive.

**Impact:** Unauthenticated product, coupon, user, order, POS transaction, and content mutations. `login.html` also posts to `login_process.php`, which is not present in the archive, while `index.php` is the real login handler.

**Remediation:** Add one mandatory auth/authorization include to every `lbpanel` and `pos/api` file. Deny direct HTTP access to `lbpanel/config`, `lbpanel/includes`, and `pos/api` at web-server level. Rotate admin passwords and review the legacy `users`, `products`, `coupons`, `orders`, and `pos_transactions` tables for unauthorized changes.

### C-03 — Unrestricted, unauthenticated CKEditor upload
**Evidence:** `site:lbpanel/includes/ckeditor/upload.php:1-26`. The handler accepts `$_FILES['upload']`, performs no auth check, no extension/MIME allowlist, no filename randomization, and moves the file to `$_SERVER['DOCUMENT_ROOT']/admin/includes/ckeditor/uploads/` using the client-supplied `$filename`. Line 25 also interpolates `$_GET[CKEditorFuncNum]` into inline JavaScript without sanitization.

**Impact:** Potential arbitrary file upload / webshell, plus reflected XSS through the CKEditor callback parameter. Combined with `upload_max_filesize = 9999M` and `display_errors = On`, this is the highest-risk single file in the legacy tree.

**Remediation:** Disable this endpoint immediately. Replace with an authenticated, CSRF-protected uploader that allowlists image types, verifies content with `getimagesize()`/`finfo`, generates random filenames, stores outside web root, and sanitizes the CKEditor callback. Remove any existing files from that upload directory and scan for PHP/JS payloads.

## High findings

### H-01 — Debug and runtime artifacts shipped inside the web root
**Evidence:** `site:php.ini:6` `display_errors = On`; `site:php.ini:14` `upload_max_filesize = 9999M`; `site:.user.ini:7,14,15` same settings plus `session.save_path = "/home/u128159657/domains/woodexfurniture.pk/public_html/sessions"`; `site:session_test.php:3` prints session status/id; `site:error_log` (568 KB); `site:api/.DS_Store`; `site:sessions/` contains 258 session files.

**Impact:** Stack traces, filesystem paths, and database credentials can be disclosed; session files can be read if the directory is not denied; extremely large uploads amplify DoS and malware risk.

**Remediation:** Remove all listed artifacts from deployment. Set `display_errors = Off`, `log_errors = On`, `upload_max_filesize`/`post_max_size` to business-appropriate values (e.g. 10–20 MB), and store sessions outside `DocumentRoot` with restrictive permissions. Explicitly deny `.ini`, `.log`, `.sql`, `.DS_Store`, and `sessions/`.

### H-02 — Unauthenticated installer and database reconnect actions
**Evidence:** `os:public_html/api/admin.php:387-410`. `setup` is allowed when `_private/db.json` does not exist; `db_reconnect` is allowed when the existing DB connection is broken. Both are guarded only by an optional builder password (if `_private/builder.json` exists). The success message at line 401 references installing with `admin / admin`. `admin.php:329` exposes an unauthenticated `status` action.

**Impact:** If the config file is deleted, corrupted, or an attacker can influence the DB connection, an unauthenticated caller may reinitialize the database or create an owner account. `status` discloses `needsSetup`, `dbError`, and builder-lock state.

**Remediation:** Require a bootstrap secret supplied only through the server environment. Delete or disable `setup`/`db_reconnect` after installation behind a filesystem flag outside the web root. Restrict installer/reconnect to localhost or an admin IP allowlist. Remove the default-login hint and force a password change on first login.

### H-03 — Default and demo credentials are documented and seedable
**Evidence:** `os:README.md` staging Admin/Frontend credentials; `os:database/demo-seed.sql:4-5` inserts bcrypt-hashed demo admin and customer accounts; `os:docs/FRONTEND-INTEGRATION.md` repeats the login pair; `os:public_html/api/admin.php:401` mentions `admin / admin`.

**Impact:** Account takeover if the demo seed or documentation is used in production, or if the default admin password is never changed.

**Remediation:** Never import `demo-seed.sql` into production. Remove credentials from docs, generate random initial passwords, force rotation on first login, and add a startup check that refuses to run with known demo hashes.

### H-04 — Private secrets are inside `DocumentRoot` and depend on `.htaccess`
**Evidence:** `os:public_html/_private/db.example.json` shows the runtime location for `db.json`, `config.json`, `security.json`, `mcp.json`, `builder.json`, and `approvals.json`. `os:public_html/api/security-lib.php:10` defines `SEC_FILE = _private/security.json`. `public_html/.htaccess:8-10` blocks `_private` with `mod_rewrite`; `_private/.htaccess` also uses `Require all denied`/`Deny from all`.

**Impact:** If `AllowOverride` or `mod_rewrite` is disabled, JSON files containing DB credentials, HMAC secrets, TOTP seeds, recovery codes, MCP token hashes, and approval settings may be directly downloadable.

**Remediation:** Move `_private` outside `DocumentRoot` (e.g. `/var/woodex/private`) and reference it via an absolute path. Add a server-level `<Directory>`/nginx `location` deny that does not depend on `.htaccess`. Verify by requesting `/_private/config.json` on every environment.

### H-05 — No CSRF protection on legacy state-changing endpoints
**Evidence:** `site`/`frontstore` cart (`api/cart/*`), quote basket (`api/quote/*`), review submission, contact/subscription, coupon, checkout, and quote-request handlers rely on PHP sessions but no CSRF token validation was found. The new OS frontend-auth endpoint does use `X-WX-FE-CSRF`, and the builder uses `X-WX-CSRF`, but the legacy storefront does not.

**Impact:** Cross-site requests can modify carts, submit leads/reviews, apply coupons, or create orders using an authenticated victim's session.

**Remediation:** Add per-session CSRF tokens to all state-changing legacy forms and JSON endpoints; enforce `Origin`/`Referer`; set cookies with `SameSite=Lax` or `Strict`, `Secure`, and `HttpOnly`.

## Medium findings

| ID | Finding | Evidence / impact | Remediation |
|---|---|---|---|
| M-01 | Conflicting canonical host rules and typo | `site:.htaccess:16` forces `www`, line 20 redirects to misspelled `woodexurniture.pk`; `frontstore:.htaccess:16-22` and `os:.htaccess:11-16` force non-`www`. Causes redirect loops, split SEO signals, broken canonical links. | Choose one canonical host, fix typo, test all three deployments. |
| M-02 | No CSP; legacy lacks HSTS; deprecated header | `site:.htaccess:110-118`, `frontstore:.htaccess:200-209` set X-Frame-Options/nosniff/X-XSS-Protection/Referrer but no `Content-Security-Policy` or `Strict-Transport-Security`. OS sets HSTS but still no CSP. `X-XSS-Protection` is deprecated. | Add CSP with nonces, enable HSTS after HTTPS verification, remove `X-XSS-Protection`, add Permissions-Policy. |
| M-03 | Wildcard CORS on MCP endpoint | `os:public_html/api/mcp.php:17` sends `Access-Control-Allow-Origin: *`. Bearer tokens are not cookies, but any origin can attempt calls; token leakage would be broadly exploitable. | Allowlist known MCP client origins; add per-token rate limits and short-lived tokens. |
| M-04 | Secrets in query strings | `wa-cron.php:14` reads `?key=`; `quote-view.php` uses a signed `?t=` link. Query strings appear in access logs and referrers. | Use POST headers for cron; keep link tokens short-TTL and rotate; scrub logs. |
| M-05 | Fail-open Cloudflare Turnstile | `os:public_html/api/forms.php:80` accepts the lead if Cloudflare is unreachable rather than rejecting it. | Fail closed or queue for later verification; strengthen honeypot/rate-limit fallback. |
| M-06 | Builder SSRF surface | `os:public_html/api/builder.php:300-338` has good guards (HTTPS-only, DNS resolution, private-IP ranges, no redirects) but still fetches arbitrary public HTTPS URLs; DNS rebinding and allowlist gaps remain. | Domain allowlist, re-resolve DNS immediately before connect, egress firewall, disable `fetch_page` for non-owner roles. |
| M-07 | SVG upload / stored XSS | `os:public_html/api/p18h-lib.php:14,98` permits `.svg` and strips `<script>` with a regex. Regex filters can be bypassed with `foreignObject`, encoded payloads, or event handlers. | Disallow SVG uploads or sanitize with a proven library; serve user uploads from a separate cookieless origin; add CSP. |
| M-08 | No rate limiting on legacy endpoints | OS throttles login/forms/chat via `wx_throttle`; legacy cart, quote, review, login, and POS endpoints have no equivalent. | Shared rate-limit middleware, login lockout, WAF, anomaly alerts. |
| M-09 | CDN assets without Subresource Integrity | `frontstore` references `cdn.jsdelivr.net` (70), `cdnjs.cloudflare.com` (66), `code.jquery.com` (22); `site/lbpanel/login.html` loads Bootstrap 4/jQuery. Zero `integrity=` attributes were found. | Add SRI or self-host pinned versions; enforce CSP `script-src`. |
| M-10 | Vendored/EOL dependencies, no lock files | CKEditor 4.25.0-lts is included with an explicit notice that the open-source edition is no longer maintained; PHPMailer 6.9.3, Chart.js 4.4.1, a dompdf git snapshot, Bootstrap 5.3.0-alpha3, and jQuery 3.5.1 are vendored. No `composer.lock`/`package-lock.json` for the application. | Introduce Composer/npm lock files, replace CKEditor 4 with CKEditor 5 or LTS support, run SCA/SAST in CI. |

## Low findings

| ID | Finding | Evidence / impact | Remediation |
|---|---|---|---|
| L-01 | Unauthenticated status / system fingerprinting | `admin.php:329` `status`; `sys_check` returns server software, PHP extensions, writable paths. | Minimize `status`; require auth for diagnostics; remove absolute paths from responses. |
| L-02 | No tests, CI, or secret scanning | No PHPUnit, Playwright, GitHub Actions, Dockerfile, or pre-commit secret scan in the accessible trees. | Add unit/integration/security tests and CI with secret scanning on every commit. |
| L-03 | Inconsistent connection config copies | `frontstore:connection.php` and `frontstore:lbpanel/config/connection.php` contain hardcoded live fallbacks; `frontstore:config/connection-real.php` is sanitized but claims to be verbatim; `FRONTSTORE_USE_MOCK` is mentioned in comments but not implemented in code. `frontstore` PDF endpoints expect `vendor/autoload.php`, which is not in that archive. | One config loader, one source of truth, remove dead flags, document dependencies. |
| L-04 | Schema drift / runtime DDL | `schema.sql` omits four `wx_chats` columns required by `schema_requirements()`; 27 additional `CREATE TABLE IF NOT EXISTS` statements exist in PHP. | Versioned migrations; see `DATABASE_SCHEMA.md`. |

## Positive controls observed

These reduce risk and should be preserved during consolidation:

- New OS admin tokens are HMAC-signed (`uid.exp.sid.sig`), 12-hour expiry, revoked through live session IDs, with `password_hash`/`password_verify`, TOTP/recovery codes, IP throttling, and role/action permissions (`admin.php`, `security-lib.php`, `roles-lib.php`).
- Frontend customer sessions store SHA-256 token hashes (`wx_frontend_sessions`), use HttpOnly cookies, readable CSRF cookie, and bearer support (`frontend-auth.php`).
- WhatsApp webhook verifies `X-Hub-Signature-256` and fails closed; Telegram webhook checks `X-Telegram-Bot-Api-Secret-Token` (`whatsapp.php`, `telegram.php`).
- MCP tokens are hashed in `mcp.json`, `tools/call` exposes 18 tools, and write tools are limited to drafts/notes (`mcp.php`).
- Builder image uploads verify real image content, randomize filenames, and owner-only script additions are gated by `code_guard()`; `import_url`/`fetch_page` reject non-public/private targets (`builder.php`).
- `quote-view.php` uses `JSON_HEX_TAG|HEX_AMP|HEX_APOS|HEX_QUOT` before embedding data in `srcdoc`.
- `public_html/assets/uploads/.htaccess` disables the PHP engine and blocks script-like extensions.
- `RELEASE-MANIFEST.json` verified all 182 listed file hashes in the OS archive.

## Static scan interpretation

`STATIC_SCAN.json` records regex-based counts: 456 SQL string-interpolation candidates across 82 files, 151 SQL strings near superglobals across 58 files, 288 prepared-statement markers across 82 files, and 5 high-confidence literal secret assignments. **These are heuristics, not confirmed vulnerabilities.** Targeted manual review of cart, quote, review, checkout, search, and admin handlers found extensive use of `mysqli` prepared statements and no high-confidence direct `$_GET`/`$_POST`/`$_REQUEST` interpolation into an SQL string. Dynamic SQL builders (`fetch_products.php`, `filters_meta.php`, `footer.php`, `header.php`, redirect/auto-migration helpers) still require runtime/DAST testing with a staging database.

The 5 literal secret-like assignments were concentrated in `site/lbpanel/config/connection.php` (4) and `site/vendor/dompdf/lib/Cpdf.php` (1, a PDF library constant). The real credential risk is the hardcoded DB configs and `error_log` stack traces described in C-01, not a generic regex count.
