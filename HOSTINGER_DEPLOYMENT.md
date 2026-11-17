# Hostinger deployment guide

This guide reflects the Hostinger dashboard screenshot supplied for this repository and the repository contents that were checked on 2026-11-17.

## Current dashboard configuration

The screenshot shows:

| Setting | Current value |
|---|---|
| Hostinger site | `staging.blackibex.net` |
| GitHub repository | `woodex420/furniture-os` |
| Deployment branch | `arena/bd434fbd-furniture-os` |
| Deployment root directory | `public_html` |
| Auto-deployment | Enabled |
| Last deployment | Completed (manual deployment) |

The Actions run provided (`44149141412`) has no failing jobs. The Hostinger dashboard also says the last deployment completed. These indicate that a deployment operation succeeded; they do not verify that the application is present, that PHP/database configuration is correct, or that the site passes a runtime health check.

## Important: the application is not in this Git branch yet

The branch shown in the screenshot was checked. Its tracked contents are a README, `.gitignore`, and audit/report documents; it does **not** contain the React frontend, Laravel backend, or a deployable PHP storefront. The audit report also describes the source archives as a custom PHP backend/admin and legacy PHP storefront—not a React/Laravel application. Therefore:

- Do not expect this branch to build a React app or run Laravel commands.
- Do not point production at it yet. Hostinger can successfully publish the current files without publishing a functioning application.
- Do not upload the old source archives unchanged. The audit report flags exposed/hardcoded credentials, debug artifacts, and serious legacy admin/upload security issues. Rotate any credentials that were ever present in those archives before deployment.
- First commit the intended, reviewed application source to Git. Decide whether the real target is the existing PHP system or a separately implemented React + Laravel application; do not mix assumptions about the two.

## Recommended next steps for the currently configured staging site

### 1. Keep staging isolated

Keep `staging.blackibex.net` as the test site. Do not change the production domain's DNS or document root as part of this staging setup. Use a separate staging database and credentials, with no production customer data.

### 2. Add the actual application source to Git

Before redeploying, commit the selected application to `woodex420/furniture-os` and ensure it is on `arena/bd434fbd-furniture-os`, the branch configured in Hostinger. Review the GitHub branch and commit shown on the Hostinger dashboard after pushing.

Only publish files intended to be web-accessible. Never commit or deploy `.env`, database passwords, API keys, SQL dumps containing personal data, logs, PHP sessions, `vendor/` or `node_modules/` from a developer's machine, or private application/configuration directories into the public web root. Keep secrets in Hostinger/server-side configuration, not in React's `VITE_*` variables (those are public in the built JavaScript).

### 3. Confirm what `public_html` means in this setup

The screenshot identifies `public_html` as the deployment root. Treat it as the destination/web root, not as evidence that the full application belongs there. Check Hostinger's Git deployment settings and File Manager after deployment to confirm the exact mapping. The web root should contain only the public entry point and public assets.

For a PHP application, keep private PHP source, configuration, uploads that should not be executable, and secrets outside the web root where the plan permits. The configured web root should expose only the application's public directory. If this cannot be configured safely on the shared plan, use deny rules as a secondary safeguard and do not publish secrets or private source there.

For a React/Vite-only deployment, publish the **contents** of the production build output (normally `dist/`) to the frontend web root, not the source tree. Add an SPA rewrite only if the app actually uses client-side routes. Confirm the deployment process builds the frontend; simply selecting the repository root does not run `npm run build` unless a build step is configured.

### 4. Configure the application and database

The screenshot shows a Hostinger Business plan, but does not show database connection details or confirm PHP extensions. In hPanel, create a staging MariaDB database and user, and copy the exact database name, username, host, and port shown there into the server-side application configuration. Do not assume a hostname from an example or reuse credentials from an archive.

For a PHP/Laravel backend, confirm the PHP version and required extensions in hPanel for the staging domain before installing dependencies. For Laravel, set production-style server settings (`APP_ENV=production`, `APP_DEBUG=false`, correct `APP_URL`), set database credentials outside Git, and make sure only Laravel's `public/` directory is web-accessible. Run Composer install and migrations only after the actual Laravel source exists, a staging database is backed up, and the migration plan is reviewed. Do not run example commands against production blindly.

For a custom PHP application, follow its own documented config/schema process. The repository currently has no deployable application or database migrations to execute.

### 5. Deploy and verify

After the application source and safe deployment layout are committed:

1. In Hostinger, verify repository `woodex420/furniture-os`, branch `arena/bd434fbd-furniture-os`, and the intended deployment root.
2. Start a manual deployment once, then inspect the deployment output and the deployed files in Hostinger File Manager.
3. Visit `https://staging.blackibex.net` and check the browser console and server/PHP error logs.
4. Exercise a harmless health check and the main read-only screens. Test authentication, uploads, email, webhooks, and any checkout/payment flow only with staging accounts and sandbox credentials.
5. Confirm HTTPS, error display disabled, writable storage limited to required directories, and that `.env`, source archives, reports, logs, backups, and private configuration are not downloadable from the site.
6. Take a backup and document a rollback commit before switching any production domain.

Auto-deployment is currently enabled. Once the source is ready, pushes to the configured branch may publish automatically; use a staging-only test first and check the Hostinger deployment record after every push.

## If the intended architecture really is React + Laravel

That architecture needs two independently configured applications, not just the current single `public_html` deployment:

```text
Frontend domain (staging first)  -> React production build output
API subdomain (staging first)    -> Laravel public/ directory only
Laravel                         -> Hostinger MariaDB
```

A suitable production shape is `woodex.com.pk` for the frontend and `api.woodex.com.pk` for the API, but the screenshot only confirms `staging.blackibex.net`; configure and verify the actual domains in hPanel rather than assuming these names are already attached. Build the React app with the API's HTTPS URL, restrict Laravel CORS to the exact frontend origin, and keep Laravel application files outside the API document root. Do not expose Laravel `.env`, `vendor/`, `app/`, `config/`, `database/`, `resources/`, `routes/`, or `storage/` over HTTP.

These are target-architecture instructions, not commands that can be run on the current branch: the React and Laravel projects and their build/dependency manifests are not present here.

## Before going live

- [ ] The actual application source is committed and reviewed; the deployed commit is the expected one.
- [ ] Staging deployment and runtime checks pass.
- [ ] All credentials found in old archives/logs have been rotated; production secrets are not in Git or public assets.
- [ ] Public document roots contain only public files.
- [ ] Staging and production use separate databases and credentials.
- [ ] Database backup and restore/rollback procedure have been tested.
- [ ] HTTPS, PHP error handling, storage permissions, and application logs are checked.
- [ ] Production DNS/domain cutover is planned separately from the staging deployment.
