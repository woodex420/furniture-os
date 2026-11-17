# Furniture project map and preview entry points

**Development workspace, not a complete project, deployment package or production architecture.** Furniture and Interior remain separate. This map organizes the existing folders without moving, deleting or automatically adding reference files or changing existing data. The branch remains the Arena-assigned working branch; do not rename or switch it during this session.

## Where to work

| Folder | Purpose | Status |
|---|---|---|
| `apps/furniture-platform/` | Furniture Laravel app: owner admin, API, migrations, Blade/Livewire and tests | Independent application foundation; many operational modules are pending. |
| `apps/reimagined-storefront/` | Actual Reimagin React/Vite/Tailwind storefront preview | Partial Furniture API integration; remaining demo content/adapters are not authoritative business data. Not yet the final Astro renderer. |
| `apps/astro-hero-spike/` | Separate Astro/React hero prototype | Research only, not the complete storefront. |
| `apps/dashboard/`, `shared/` | Approved visual admin design reference | Standalone reference UI; not authenticated Furniture admin. |
| `apps/laravel-admin-source/` | Locally available sanitized Interior-derived reference | Untracked and separate; do not import business data, deploy, modify, delete or stage it automatically. |
| `docs/` | Requirements, audits, previews and release backlog | Older documents can be historical; use the [current readiness checklist](FURNITURE-PRODUCTION-READINESS-TODO.md) and [module integration report](FURNITURE-V21-MODULE-INTEGRATION-REPORT.md) for current status. |
| `.github/workflows/furniture-validation.yml` | Pinned no-deploy CI | Validation, not hosting, MySQL or production acceptance. Other pre-existing untracked files are not part of this change. |

Dependencies, build products, temporary databases and preview credentials do not belong in the project commit. Local `node_modules`, `vendor`, caches and `/tmp` preview runtime are not a deployable package. Existing unrelated modifications and untracked material are preserved; moving or cleaning them requires an inventory and an explicit safe migration plan.

## Current preview

- **5174 storefront** opens the actual React storefront. Its development banner links **Open Furniture admin** to `/admin`. The opt-in Vite development proxy forwards `/admin`, `/login`, `/logout`, admin built assets, Livewire and `/storefront-api` to the same synthetic Laravel backend. Browser URLs stay on the storefront origin. Auth, authorization and CSRF remain Laravel's responsibility; the proxy is not for production or public deployment.
- **5176 admin** is a separate synthetic Laravel preview. Its root goes to `/admin` and then `/login` if signed out. This root behavior is explicitly enabled only in the temporary preview using `FURNITURE_ADMIN_PREVIEW_LANDING`; it is false by default. Normal Laravel `/` still returns 503 because Laravel does not yet serve the finished storefront. Nothing here changes production domain routing.
- **5177** is the isolated Astro hero proof, not the complete storefront. Preview credentials are synthetic only; never reuse them outside the preview. Data in the temporary SQLite database may disappear when the sandbox is reset. Saving published content affects this preview, not a live Furniture domain.
- The running backend is a disposable, separately prepared app copy with locked dependencies, synthetic SQLite and preview-only cookie/session settings. Its runtime overrides are not the source configuration. The storefront uses `FURNITURE_PREVIEW_API` only in the Vite **server** process to reach that backend; the internal URL is not shipped to browsers. With no configured backend, the proxy is off and the preview admin link cannot work. Do not set that variable to a real-data service or run this development server as a public admin.
- Browser access and cookies can differ between an embedded Arena frame and direct tabs. The local nested HTTPS test covered both entry points, login/logout, Laravel CSS, Blog navigation and Livewire search; actual Arena and hosting behavior still need confirmation. No Hostinger deployment was performed.

## Next phase started: make the preview navigable and maintainable

This change provides a single storefront-to-admin path, an admin root entry, bounded proxy routing tests, a preview folder map and clear separation between prototype, reference and runtime. It does **not** complete the overall project or reorganize uncertain files. Continue by finishing reproducible clean-checkout setup (PHP extensions and lockfiles, synthetic data provisioning without secrets), completing the route/data-source inventory, then implementing the shared page/content model and richer services/blog/location flows. Preserve revisions and existing content while replacing temporary bridges. Production freshness, typecheck/dependency remediation, MySQL and hosting verification remain open in the readiness checklist.
