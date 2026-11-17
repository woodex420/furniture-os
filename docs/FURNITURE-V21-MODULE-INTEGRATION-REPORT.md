# Furniture admin & storefront — v2.1 reference and module integration report

**Status: implementation started, not complete or production-ready.** This report inventories the requested areas in the source available in this workspace and records the first service-page slice. It is not a full security review or a guarantee that every inherited button or component has been audited. For the release checklist see [production readiness](FURNITURE-PRODUCTION-READINESS-TODO.md) and [Website Studio master plan](WOODEX-FURNITURE-WEBSITE-STUDIO-MASTER-PLAN.md).

## Reference and architecture boundary

- The supplied `marketingwoodex-cloud/-marketingwoodex` reference was previously inspected read-only at main commit `63868e0`; `apps/laravel-admin-source/platform/public/assets/admin-v21/PORT-NOTES.md` identifies its ported Website screens as the v2.1 admin source. It is an Interior-derived reference, not Furniture production state. The referenced original was not modified, and no Interior data or credentials were imported.
- The reference's `wx-embed.js` allows Blog, Portfolio, Forms and Estimator but blocks its old Service, City, FAQ and other database-only screens, explaining that those rows did not change its actual file-rendered public website. This is a source finding, not a runtime test of the original installation. Furniture needs its own tested data, routes and renderer rather than presenting disconnected v2.1 screens as completed.
- Furniture's own Laravel app and database own Furniture data. The approved admin shell remains the design baseline; v2.1 behavior informs module requirements, not automatic UI/data migration. The real current preview frontend is Reimagin React/Vite on 5174; 5176 is the synthetic Furniture Laravel admin. Astro production rendering and Hostinger freshness remain open, not solved by this bridge.

## Current storefront route inventory

Every route listed exists in the React router. **Exists does not mean live integration or production-ready content.** The table is a source-level trace; more nested components and actions still require an interaction/security audit.

| Routes | Current source/authority and limitation | Pending |
|---|---|---|
| `/` | Actual homepage; first hero title/subtitle/order/CTA/background and tagline use Laravel preview API, revisions and limited canvas. Remaining content is components/sample data. | Full section editing, component schema, initial HTML and page-level publication. |
| `/services`, `/services/:slug` | Seven bundled service definitions. The new Laravel bridge overrides only title and tagline for those existing slugs; five predefined listing cards use the overrides when applicable. Detail uses them for heading/tagline. Home service strip and other copy remain static. | Full services CRUD, active/order, image/media, overview, process, inclusions, FAQs, related links, case studies, metadata and builder; verified content; 404/redirect policy. |
| `/showrooms` | `Showrooms.tsx` uses `getAdminApi().listShowrooms()`; current default adapter is demo/localStorage. It has fallback contact text. | Approved Furniture locations and Laravel management, map/phone/hours validation, errors and real route/city SEO. Showrooms and service-area city pages are distinct concepts. |
| City/service-area routes | No dedicated city route in `App.tsx`; services have sample geographic claims. | Define city schema, slug collision/redirect rules, verified service areas and actual rendered city templates/admin. No invented locations or imported Interior records. |
| `/blog`, `/blog/:postId` | `useBlogPosts` uses `getAdminApi().listBlogPosts()` with bundled fallback; demo/localStorage by default. | Laravel article CRUD, safe content/media, editorial identity, slug/redirect, optional drafts, immediate publish, revisions, import, SEO and optional AI controls. |
| `/projects`, `/projects/:projectId` | `useProjects` and bundled content/adapter; not Furniture Laravel. | Portfolio/project CMS, verified permission to use client assets and public rendering/revisions. |
| `/shop`, `/shop/:productId`, `/series`, `/series/:seriesId`, `/room-packages` | Product/series static data and inherited adapter/UI. | Authoritative Laravel catalogue/variants/prices, media, stock, navigation, checkout and safe cache/concurrency. |
| `/quotation`, `/checkout`, `/contact`, `/custom-design`, `/b2b` | Existing UI/contexts and inherited submissions/adapters; cart/quote use browser localStorage. | Real Laravel intake/quote/order contracts, server pricing, consent, idempotency, provider-safe failures and approved business rules. Do not place real orders or send real enquiries in preview. |
| `/about`, `/virtual-showroom`, `/materials`, `/warranty` | Existing component and/or inherited page hooks/sample content. | Verified Furniture content, Laravel CMS contract, actual warranty policy and route/SEO/render tests. |
| `*` | React client-side NotFound route. | Server-level 404 and canonical/redirect behavior with selected production rendering strategy. |

The default Reimagin adapter is demo. Other inherited modes include Bagisto and Supabase. The presence of a mode or a connected Arena app is not a Furniture integration. A build can expose browser-visible `VITE_` values: never place an admin secret or provider credential there. Inventory and remove/disable remaining unintended live/demo adapters before release.

## Laravel admin module report

| Group | Furniture status | What remains |
|---|---|---|
| Authentication / System | Provisional owner session, throttling, private responses and CLI provisioning candidate. | Staff RBAC, recovery/MFA policy, lifecycle, security/hosting/operational review, settings, connections and audit UI. |
| Workspace | Approved-style shell and pending dashboard; menus intentionally marked. | Real scoped metrics, approvals, reports, notifications and role-aware navigation. |
| Customers | Owner-only Leads and Contacts reads/details and feature-flagged creation candidates. | Edits, assignment, tasks/visits, inbox, ownership/deduplication, concurrency, permissions and full CRM integration. |
| Sales / Commerce | Menu placeholders; storefront has demo catalog/quote/cart/checkout. | Estimates, quotes, invoices/payments, catalog, stock, order/fulfilment and warranty with verified rules and server authority. |
| Operations / Procurement / Production / Accounts | Menu placeholders. | Projects, vendors, handovers, purchasing, warehouse/stock ledger, production and financial controls. |
| Growth | Menu placeholders. | Consent-aware automations, sequences and approved providers; no real sends without authorization. |
| Website Studio | Site Overview (limited homepage), Media Library (limited private upload and selected hero publication), Service pages (narrow two-field bridge below). City pages, Blog, full builder, templates, navigation, SEO, portfolio, forms and design system remain pending. | Replace temporary bridges with shared page/content model and actual render/publish parity. Finish every v2.1-inspired module only when its output affects the real Furniture frontend. |

## Implemented slice — existing service title and tagline

- `FURNITURE_SERVICE_COPY_ENABLED` defaults **off**; the isolated 5176 preview explicitly enables it. Owner-only Website Studio → **Service pages** lists seven existing Reimagin service slugs. Each edit requires a bounded plain-text title and tagline, with optimistic revision, transactional actor-attributed history and restore (including revision 0's original bundled fallback). Only the latest 20 versions appear in the form; history is not pruned by that display.
- `GET /storefront-api/services/copy` returns only the seven slug/title/tagline/revision values with no-store and no session. The actual 5174 `/services` list and `/services/:slug` detail poll while visible. Up to five predefined list cards can display edits; all seven detail pages can. Other service sections, homepage strip, images and SEO metadata are not edited by this slice. The admin form does **not** have an unsaved visual canvas for services.
- This service path now reads bundled Reimagin definitions plus Furniture overrides, not the legacy demo adapter. Existing browser-local demo data is **not deleted** and can still affect other modules. Until overrides exist, bundled data (including potentially unverified claims/case studies) remains sample data. No Furniture data was seeded from Interior or presented as verified.
- Restore resets to the bundled frontend sample, not an approved production value. If the API is unreachable or returns malformed/incomplete data, the open page keeps its last valid copy; a fresh page shows bundled fallback. This is a preview choice, **not a production freshness/availability guarantee**. Service metadata/SEO, no-JS HTML and production publication remain unsolved.
- Migration creates service copy and versions; rollback drops them and loses edits/history. Before any real migration or rollback, back up and rehearse, and later migrate these records and revisions to the shared CMS. MySQL locking/concurrency and hosting remain unverified.

### Evidence for this slice

- Local modified-runner PHP 8.4/SQLite: 103 tests / 1,045 assertions, including 5 service tests (60 assertions); local PHP lacks `xmlwriter` and `pdo_mysql`, so this is partial evidence. New tests cover flag/owner access, validation, stale revisions, audit rollback, per-service isolation, restore and escaped admin output.
- `node --test tests/service-copy-parser.test.mjs`: 3 passed (valid snapshots, malformed/duplicate/unknown entries, bounds). Storefront `npx vite build` passed. Project-wide TypeScript still fails on inherited errors; no diagnostics reported in the changed TypeScript files.
- Local nested HTTPS browser simulation with third-party-cookie phaseout: owner login, save, unsaved isolation, plain-text/XSS rendering, live list/detail update and restore passed using synthetic SQLite; exact Arena embedded browser and actual Hostinger remain unverified. Synthetic preview was backed up before migration, and test content restored to the baseline (revision history advanced).
- Passing tests are not a declaration of readiness. Consult current GitHub Actions for the exact commit and full native PHP environment; browser, MySQL and infrastructure gates are not part of the CI jobs.

## Prioritized next implementation

1. **Services:** shared CMS model and route registry; real service create/edit/status/order, structured sections and media, related services, SEO, preview, immediate save/publication, version and rollback; align listing cards and homepage strip with the same data. Test all seven pages, missing/disabled routes, permission, revisions, malicious content, refresh and no-JS HTML.
2. **City pages and locations:** define separate city service-area pages versus physical showrooms; owner-supplied/verified Furniture location details, route/slug ownership, server-backed CRUD, matching templates, links and SEO, failure states, migration/history and browser tests. No guessed office addresses or copied Interior records.
3. **Blog:** Furniture-owned article model and unique slugs, structured safe content, image use, authors/categories, optional isolated drafts, immediate publication of accepted changes, history/restore, import safeguards, metadata and list/detail UI connected to the same published records; AI only after provider/spend/privacy choices.
4. **All other pages and admin:** complete the route/adapter inventory, replace demo/localStorage behavior with verified Laravel contracts, implement module workflows and permissions, migrate temporary bridges without losing revisions, and cover actual user journeys. The full backlog, including commerce and external provider boundaries, is in the production checklist.
5. **Release:** establish Astro/React/Tailwind rendering parity with crawlable current HTML and measured publish freshness; run full locked CI including typecheck, security, MySQL migration/upgrade/concurrency, browser/accessibility/performance and backup/restore. Verify Hostinger later and obtain separate staging/production authorization.
