# WOODEX Furniture — Reimagin v1 & Website Studio master plan

**Purpose:** Upgrade the Reimagin v1 storefront to **Astro + React + Tailwind CSS**, connect it to the separate Furniture Laravel admin, and build the complete visual Website Studio described by the founder. Woodex Interior is a functional and visual reference, not the Furniture database or deployment target.

**Status:** Architecture and phased implementation plan; not a claim that the site, builder, SEO system or integrations are complete. Phase 1 has started with an isolated Astro shared-hero experiment; see `MARKETINGWOODEX-RESOURCE-AND-ASTRO-SPIKE.md`. The existing two-field home preview now also has revision history and restore; see `FURNITURE-HOME-REVISION-FOUNDATION.md`. A limited actual-storefront canvas for those fields, including versioned title/subtitle order, a first-slide button, five bundled background choices, and fixed-width viewport previews, is available when configured; see `FURNITURE-HOME-VISUAL-CANVAS.md`. The general page schema and full visual builder are still pending. This document supersedes older builder proposals that make the `woodex-2030` database authoritative for Furniture. Maintain the existing approved Furniture admin shell and the Reimagin v1 storefront appearance unless the founder changes the design direction.

## 1. Agreed product direction

1. **One Furniture system:** Furniture has its own domain, admin, data, media and users. Laravel owns Furniture's business data and authorization. Woodex Interior remains a separate company application under the Woodex umbrella; use its screens and implementation as references, not its live data or secrets.
2. **Real Reimagin pages in the editor:** The builder must edit the actual Furniture storefront's page components/content, not a visually similar sample document or a separate hardcoded `/lab` page. A change to an editable component must render in the canvas immediately and in the actual storefront according to the publishing mode.
3. **All storefront templates:** Build editing support for home, shop, category, product, content, service, city/location, blog, portfolio/project, forms, policies and system templates. Commerce facts and actions remain governed by Laravel, not arbitrary page markup.
4. **Publishing preference:** The founder selected immediate publishing rather than a mandatory separate publish step. Implement immediate publication of accepted changes with clear save/publish/error states, revision history, conflict detection and rollback. Support draft, preview, scheduled and unlisted modes where requested for new articles/pages without forcing an additional publish step on ordinary live edits. No real hosting action is authorized by this preference alone.
5. **Target stack:** Astro for the storefront's HTML/routing, React for interactive islands and visual editor, Tailwind CSS for shared tokens and utility styling, Laravel/Livewire for admin and backend. The current preview is still React/Vite/Tailwind 3; the Astro migration has not yet happened. Choose and document a compatible Tailwind version during the first spike, with a migration path from current Tailwind 3 classes.
6. **Hostinger later:** Target the founder's Hostinger Business shared environment, but verify the actual PHP version/extensions, MySQL/MariaDB version, web root, rewrite rules, storage, scheduler, process limits and deployment capabilities before choosing a final runtime. Do not assume a permanent Node server, WebSocket server or queue worker exists. No production access, real-data import, schema upload or hosting deployment is part of this plan approval.

## 2. Evidence and what is actually working

| Source | Findings | Limits |
|---|---|---|
| `apps/reimagined-storefront` | Reimagin v1 React/Vite/Tailwind frontend, copied from `blackibexofficial-blip/woodex-reimagined` commit `e8502b0d…`; port **5174** serves this snapshot. It has home, shop, product, room packages, series, projects, services, B2B, about, contact, design, quotation, showroom, materials, warranty, blog and checkout routes. | Frontend source exists; data is largely seed/demo or adapter-driven, not a completed Laravel integration. `npx vite build` passes; inherited TypeScript errors remain. This is not yet Astro. |
| `apps/furniture-platform` | Independent Laravel 13 / Livewire Furniture foundation. Port **5176** runs the isolated synthetic admin. Login, owner-only CRM reads, experimental contact/lead creation and a first homepage-slide title/subtitle editor exist. | Only the first-slide title, subtitle, relative order, button text/internal destination and selected bundled background flow from Laravel into the actual React homepage preview. An opt-in private owner-only PNG/JPEG upload/listing experiment exists separately; it is not connected to the storefront or a complete media library. See `FURNITURE-PRIVATE-MEDIA-PREVIEW.md`. The full CMS, visual builder and business workflows are not implemented. SQLite preview and flag-gated actions are not proof of MySQL or hosted readiness. |
| Furniture validation | [GitHub validation run 44036367249](https://github.com/woodex420/furniture-os/actions/runs/44036367249) passed Laravel's locked native tests, asset builds and the Reimagin Vite bundle at commit `9ae2731`. | This is not an Astro, browser-on-Hostinger, MySQL concurrency, production-data or complete security test. |
| Interior screenshots supplied by founder | Target reference for visual selection toolbar and for SEO dashboards/editor, media library, client logos, testimonials, FAQs, templates, city/service pages, portfolio, blog and AI-assisted article UX. | Screenshots show desired behavior; they do not prove every action works or establish the contents of Furniture. Examples mentioning Interior, its cities, clients and page counts must not be copied as Furniture records. |
| Interior Node/SQLite reference | `woodexinterior` branch `arena/d89528a7-woodexinterior` resolved to `fd59a5f…`; its SQLite schema defines pages, revisions, navigation, media, insights, projects, services, enquiries and activity. Its code has 14 block types including hero, image/text, FAQ, cards, CTA and form. | It is SQLite, **not a MySQL import file**. The inspected branch is not proven to be the exact screenshot runtime. No Interior records or credentials were read into Furniture. |
| Sanitized Interior Laravel reference | `apps/laravel-admin-source/platform/app/Domain/Website/` has builder, media, page, template, version, SEO, theme and Studio code. `BuilderApi` maps actions; `SaveHtmlFile` writes publicly served HTML, makes snapshots and checks conflicts. | It is reference code, not the running Furniture app. Its `StudioApi` explicitly retires or leaves some actions unported, including separate FAQ/testimonial/logo actions. Public static files can shadow DB-backed pages. Do not assume the screenshot feature list is fully implemented or safely reusable. |
| Reimagined Puck demo | Existing source contains a Puck editor, registry and inspector; its `/lab` document is distinct from the hardcoded homepage, and the public-document validator supports only a limited subset of block types. | The old demo editor routes have been removed from the active storefront preview. Neither Puck documents nor old Interior HTML should silently become the Furniture production CMS. |

**Analysis boundary:** this is a source and screenshot analysis of the available snapshots, not an audit of the actual Interior installation, live infrastructure, all data, exact Hostinger account, or every browser path. No full Interior runtime behavior is claimed. Existing detailed notes: `docs/FURNITURE-REIMAGIN-INTEGRATION.md` and `apps/reimagined-storefront/PREVIEW-NOTES.md`.

## 3. Target architecture and single source of truth

```text
Furniture visitors ──► Astro storefront HTML + React islands + Tailwind design system
                                  │ published, read-only content / catalogue
                                  ▼
Furniture staff ──────► Laravel admin / Website Studio (React editor inside admin)
                                  │ auth, roles, validation, CSRF, revision checks
                                  ▼
                  Laravel services ──► Furniture MySQL/MariaDB + media storage
                       │                         │
                       └── render / release / cache invalidation / revisions ──► site
```

- **Laravel is the only writer.** No parallel demo/localStorage, Supabase, Interior or Node API may silently own Furniture production records. The Reimagin demo adapters remain reference code until replaced by tested Furniture contracts. Server-side price, availability, orders and permissions are authoritative.
- **Shared renderer contract:** Define a versioned Furniture page schema with stable IDs, typed components/slots, content, responsive style tokens, media IDs, entity references, accessibility and SEO metadata. The editor canvas and storefront must resolve the same registered Reimagin components and schema. Preserve unknown data on load/save or reject a document explicitly; never silently drop blocks.
- **One route owner:** Establish a path registry and collision rules across actual pages, product/category routes, redirects, files, articles and error routes. Public static files must not silently shadow CMS changes. Public navigation, sitemaps, preview and canonical links must reflect that registry.
- **Current bridge migration:** Preserve the existing two-field homepage data. On an isolated database, migrate it into the first-slide document with stable identity, test round-trip and rollback, then retire the temporary bridge only after the new renderer is verified. Do not discard or overwrite existing Furniture edits.
- **Contracts:** Define versioned Laravel endpoints/services separately for public published content, admin editing, scoped preview, media, catalogue, intake and operations. Specify payload schemas, error shapes, pagination, auth, cache headers, idempotency and concurrency. The existing `/storefront-api/home` is a limited preview endpoint, not the finished API.

## 4. Visual page builder requirements

### Workspace and real-time canvas

- Left rail: Pages, Sections, Elements, Layers, Theme, SEO; searchable library with sections, reusable sections, UI kit, templates and approved media. Start blank, duplicate a page or apply a compatible template without silently replacing its content.
- Center: actual Reimagin page rendering, with shared header/footer and real component structure. Desktop/tablet/mobile widths; zoom, reload and responsive layout checks. Use synthetic product data until a tested catalogue exists. Preserve keyboard accessibility, focus, undo/redo and reduced-motion behavior.
- Selecting a section **or a supported element** shows an anchored toolbar like the founder's screenshot: select parent, drag/move (plus keyboard up/down), duplicate, delete, and applicable edit/style options. Do not claim unrestricted pixel-level editing until supported. Element editing covers text, links, images, buttons, columns, spacing and responsive style controls as each component is registered.
- Right inspector: Content / Style / Advanced, with accessible labels, media picker, alt text, link validation, theme tokens, responsive settings and semantic HTML. No unrestricted scripts, iframes or raw styles from untrusted users; validate and sanitize on the server.
- Autosave: editable preview changes immediately in the local canvas; accepted server revisions are published immediately per the founder's choice. Serialize/coalesce updates, show saving/saved/publishing/failed/conflict, never mark failed work as published. Use optimistic revision numbers, record actor/version, retain last known good version, support history and restore. Avoid a request for every keystroke and avoid overwriting a colleague's newer change.
- Shared regions (header/footer, reusable sections, theme) show usage and scope before changing. A failed global update must not leave a mixture of incompatible versions. Draft preview is authorized and non-indexable; a visible 'preview' banner is not authorization.

### Templates and page lifecycle

- Template library covers all Furniture page classes, supports versioning, preview, copy, apply, add/remove/reorder sections, and protected required slots. Template application must define whether it changes layout only or content too; show a diff and create a recoverable revision.
- New pages: unique normalized path, type, template, visibility (public/unlisted/draft where appropriate), metadata, preview, delete/restore and redirect handling. Unlisted is not private: if content must be private, require authentication.
- Imported pre-existing Furniture routes must be inventoried and mapped; do not treat Interior's page URLs or sample content as Furniture. Flag unsupported components instead of replacing them.

## 5. Website Studio feature and integration matrix

The following are **target requirements**, not current completed features. Every module needs Laravel persistence, owner/staff policy, validation, audit, responsive UI, empty/error/loading states, and storefront rendering or a truthful disabled state.

| Module | Furniture behavior and acceptance |
|---|---|
| **Header & footer / navigation** | Manage logos, menus, nested links, CTA, mobile menu, legal/footer columns, global visibility and reusable content. Same data rendered by actual storefront. Preview all pages, validate targets and prevent redirect/menu loops; global changes are versioned and reversible. |
| **Hero slides** | Manage all slides, images, alt text, overlay, copy, CTA/link, order, mobile crops, pause controls, responsive preview and page associations. Migrate existing first-slide bridge without losing edits. |
| **All pages / service pages** | Inventory real routes, search, create, duplicate, edit, add/remove, select template, version and redirect. Page templates manage presentation; service facts and CTA forms use Laravel data contracts. No silent URL overwrites. |
| **City/location pages** | Structured location pages based on controlled templates, localized content/SEO and unique paths. Prevent mass-created near-identical pages and misleading location claims. Batch creation previews the changes; no Interior locations imported by default. |
| **Blog / insights** | List/search/filter, rich-text editor with safe blocks, article template chooser (cost guide, how-to, project story, ideas, comparison, blank), hero image, category/tags, author, reading estimate, SEO, preview, public/unlisted/draft, scheduling and revision restore. Validate timezone and scheduler availability. Import only authorized Furniture content using a reversible, deduplicated mapping. |
| **Portfolio / projects** | Create/list/edit project stories, structured details, photos, galleries, related products/services, featured status, SEO and publish/restore. Distinguish public portfolio stories from internal operational projects and permissions. |
| **Page templates** | Versioned template library for home, shop, category, product, service, city, article, project and standard pages; add/duplicate/edit/apply/remove/export/import safely. Layout changes preserve page data; show affected pages and rollback. |
| **Forms** | Configure fields, consent, success/error states and routing to Furniture Leads/Inbox. Server-side validation, CSRF where appropriate, anti-spam, rate limits, idempotent submission, truthful confirmation and retry; no live email/WhatsApp/provider action until configured and tested. |
| **Estimator** | Furniture-specific rate books, units, options, minimums and validity; money in fixed precision, server-side calculation and immutable snapshots. Quote/lead conversion must be idempotent. Prices, tax, availability and disclaimers require owner policy and server revalidation. |
| **FAQs** | Reusable groups, add/reorder/edit/remove Q&A, page associations, heading and accessible accordion. Structured data only where content is visible and eligible; do not promise Google rich results. |
| **Testimonials** | Manage name, role/company, optional project/city/photo, rating, quote, consent/evidence, ordering and visibility. Use actual authorized Furniture records, not Interior examples. No fabricated review schema or unsupported claims. |
| **Client logos** | Manage logos, accessible names, optional safe links, sort, section heading, visibility and grid/slider settings. Asset rights and permission must be confirmed; no Interior client logos imported automatically. |
| **Team** | Public staff profiles and ordering are separate from private admin user accounts. Media, role/title and publication controlled with appropriate authorization and consent. |
| **Design system / theme** | Shared logo, colors, typography, spacing, container/grid, component states, breakpoints, dark mode where chosen, focus/reduced motion and reusable sections. Version global changes; mobile, contrast and keyboard checks. |
| **Media library** | Search/filter, image grid/list, upload, folders, alt/caption, dimensions, sizes, usage, responsive renditions and safe replacement. Trash/delete only after usage/revision checks. Validate MIME and decoded image, size and pixel limits; strip unsafe metadata, generate safe filenames, prevent traversal/executable uploads and do not trust remote URLs. Confirm host supports image processing before promising WebP/AVIF. |
| **SEO and site health** | See section 6; one per-route metadata source, indexable initial HTML, truthful scores, structured data, sitemap, redirects and operational scans. |
| **AI-assisted writing** | Optional and disabled until a provider is intentionally configured. Keep keys server-side outside source/browser and encrypted where appropriate; scope access, redact logs, limit spend and validate outputs. AI suggestions require human review; no invented product claims or client data. No provider account will be accessed during development without authorization. |

## 6. Complete SEO system

- **Per page:** title, description, canonical URL, index/noindex, Open Graph/Twitter title/description/image and alt, focus and related keyphrases, headings, image alt audit, internal/external links, preview snippet, schema selection and status. Use route-aware defaults from Furniture data; validate lengths with advisory ranges, not misleading guarantees.
- **SEO overview:** searchable page inventory with title/description length, keyphrases, estimated SEO/readability checks, issues, filter and drill-down. Explicitly distinguish 'not scanned', pending, failed and valid from a score of zero. A score is a heuristic, not a Google ranking promise. Support safe bulk title/description changes with preview, limits, conflict checks and rollback.
- **Site audit:** crawl only approved local Furniture routes through an allowlisted bounded worker; avoid arbitrary URL fetching/SSRF. Check response status, missing/duplicate metadata, robots, canonicals, noindex, structured data, links and orphan/redirect chains. Persist timestamp and status; never display invented health metrics.
- **Crawler-visible output:** Astro initial HTML must include correct page-specific canonical, robots, metadata and JSON-LD without waiting on React effects. Serve accurate XML sitemap, robots.txt, pagination and canonical/redirect status codes; exclude drafts, unlisted/private pages, noindex and unsuitable query combinations. Escape JSON-LD safely. Only emit Product/Review/FAQ/LocalBusiness schema supported by visible, verified data and current search-engine rules.
- **Verification:** test raw HTTP HTML without JS, sitemap counts vs published route inventory, canonical host and staging noindex, 200/301/404/410, broken links, image alts, schema and mobile performance. Validate URLs and redirect paths to prevent loops, open redirects and internal network access. Search engine indexing or ranking cannot be guaranteed.

## 7. Astro + Hostinger rendering and immediate publication decision

Immediate publishing and indexable initial HTML must work together; fetching JSON only in a browser after load is **not** a complete SEO solution. The current React preview uses polling for its limited first-slide content and is a temporary proof, not the final architecture. Compare the following with a measured, isolated proof before committing:

| Candidate | How it works | Constraint / decision |
|---|---|---|
| **A. Astro static release + Laravel content** | Build Astro HTML/assets from a specific validated published revision and atomically switch the generated artifact. Laravel continues serving admin and same-origin APIs. | Does not update crawler HTML until a successful rebuild/deploy. Prove build trigger, duration, coalescing, filesystem access and cache invalidation on the actual plan. If it cannot meet immediate freshness, do not label it immediate. |
| **B. Astro on-demand SSR** | Astro produces server-rendered HTML from current published Laravel content, with React islands hydrated in the browser. | Requires demonstrably supported Node/Astro adapter and reliable lifecycle on the actual Hostinger plan. Do not assume shared hosting supports it. |
| **C. PHP/Laravel runtime with Astro-built assets** | Laravel serves current SEO HTML/content using a deliberately defined shared component/data contract; Astro build supplies static assets/islands where feasible. | Need a prototype proving exact editor/public parity and avoiding two divergent renderers; this is not automatic Astro SSR in PHP. |

**Selection rule:** pick the first option that proves real page parity, safe publication, crawler-visible content and a measured publish-to-visible time on the actual plan. If none meets the founder's immediate requirement, report the limitation and ask for a hosting/freshness decision rather than silently claiming it works. Establish p50/p95 targets with the founder; record failure states and preserve the last working revision/artifact. This plan does not authorize changing hosts.

## 8. Data model and integration work

Proposed domains, subject to schema review rather than copy/paste from Interior:

- `pages`, route registry, page type/status/visibility, page document and schema version; page revisions and published revision; reusable sections and usages; page templates and revisions; global navigation and theme tokens.
- Typed content entities for slides, blog, project stories, services, locations, FAQ groups/items, testimonials, client logos, public team members and forms; per-page associations and ordering. Keep operational CRM/project records separate from public content.
- Media assets, variants, usage references, accessibility text, storage disk/path and lifecycle; never store arbitrary executable paths. SEO metadata, redirects, sitemap snapshots, audit runs and optional suggestions.
- Laravel catalogue/taxonomy/products/variants/materials/price/availability; carts, enquiries, quotations, orders, fulfillment and warranty. Treat money as decimal/minor units and preserve historical price/quote/order snapshots. Link forms to CRM with anti-duplication, consent, idempotency and honest error states.
- Database constraints, indexes, transactions, optimistic revisions, role policies, audit events, migration and rollback/backup plans. Use migrations as the source of schema; later generate a reviewed **Furniture schema-only** SQL export from the verified target DB/version if needed. Never import the supplied Interior SQLite schema as MySQL or copy its customer/content data by default.

## 9. Phased roadmap and exit criteria

| Phase | Deliverables | Done only when |
|---|---|---|
| **0. Baseline & contracts** | Inventory current Reimagin routes/components/data, define ownership and design tokens, record frontend TypeScript/build issues, map screenshot requirements, define test fixtures and permissions. | Versioned inventory and traceability exist; source/provenance and known gaps are explicit. No external services or production data touched. |
| **1. Astro parity + hosting spike** | Create isolated Astro + React + Tailwind prototype for real home hero, one category, one product and an editorial page; keep 5174/5176 working. Prove raw HTML SEO and compare A/B/C rendering strategies. | Mobile/desktop parity with the actual current components, working interactions and a documented route/render/freshness decision. Hosting support stays unverified until separately tested. |
| **2. Laravel CMS foundation** | Furniture page schema, versions, routing, role checks, public read/admin write contracts and migration of existing home fields. | Schema round-trips and unknown blocks are protected; conflict/auth/rollback/SQLite and matching MySQL tests pass. |
| **3. Real visual builder** | React canvas using actual Reimagin components, toolbar, sections/elements/layers, inspector, responsive modes, history and immediate-publish flow. Start home; progressively cover all template types. | Changes in canvas and actual storefront match, including save/reload, stale edits, errors, undo/redo, supported nested selection, global section impacts and keyboard access. |
| **4. Content & Website Studio modules** | Header/footer, heroes, pages, blog, portfolio, services, cities, templates, FAQs, testimonials, logos, team, forms and estimator. | Each module's owner/staff permissions, validation, CRUD, public rendering, revisions, failure paths and integration tests are complete; no placeholder is labeled working. |
| **5. Media, SEO & design system** | Media pipeline/library, per-page SEO and audits, sitemap/redirects, theme system and accessibility. | Verified file safety, usage/restore, raw HTML SEO, route correctness, mobile/accessibility and truthful audit reports. |
| **6. Commerce & integrations** | Laravel contracts for product/catalogue, search, quote, cart/order/checkout, CRM forms, fulfilment and other needed workflows; remove demo adapters from live paths. | End-to-end tests verify accurate price/stock, authorization, consent, idempotency and error/unknown outcomes. External providers remain off until explicitly configured and tested. |
| **7. Acceptance and Hostinger** | Synthetic matching MySQL/MariaDB tests, security review, backups/restore, performance, documented deployment/release/rollback, schema-only export if needed. | Exact commit passes all release gates; owner separately authorizes staging, then production. No automatic overwrite of a current site/database. |

Work may proceed in parallel where contracts are stable, but no slice should be marked complete because a screenshot, link or frontend mock exists. Show progress and keep incomplete controls visibly disabled or labeled.

## 10. Release-quality test matrix

- **Contracts/database:** migrations fresh/upgrade/rollback and restore against verified MySQL/MariaDB; FK/index/uniqueness, concurrent revisions, atomic publish/audit, interrupted releases, stale sessions and authorization at both route and service layers.
- **Builder:** same component/data on canvas and storefront, every supported page/template, add/drag/keyboard move/duplicate/delete, select parent/child, styles and media, save/reload, autosave conflicts, undo/redo, global changes, restore, request failure and immediate-publish visibility.
- **Security:** authenticated/denied users, CSRF/session behavior, preview isolation, XSS/rich content, upload and media path safety, SSRF, redirects, no secret in frontend, content tampering, rate limits, permissions, privacy/retention and dependency review.
- **Storefront:** current route inventory, direct navigation/refresh, 404/redirects, responsive 320–1440+, keyboard and screen readers, product variants, cart, quote/order, forms, inaccurate totals, stock race, provider timeout and retry without duplicate transactions.
- **SEO/performance:** no-JS initial HTML, canonical/robots/schema/sitemaps, publication freshness, indexation safeguards; Core Web Vitals targets LCP ≤2.5s, INP ≤200ms, CLS ≤0.1 at field p75, measured on representative devices rather than promised.
- **Operations:** exact build from lockfiles, actionable logs, shared-host scheduler behavior, backups including media and revisions, restore drill, atomic deployment, failure recovery, cache invalidation, rollback and post-cutover transactions.

## 11. Decisions to resolve during implementation

- Actual Hostinger-supported execution/deployment model and measurable immediate-publishing target (Phase 1/7; no credentials needed in chat).
- Furniture's real domains, canonical URLs, verified public content/assets, allowed data sources and migration scope before any live import.
- Staff roles and who may edit, publish, upload, restore or perform money-related actions; initial owner-only preview is not the final RBAC policy.
- Exact Furniture estimator, quotation, tax, payment, delivery, warranty and consent rules before real business transactions.
- AI provider, storage, spend limits and enabled functionality if the founder decides to use AI; no provider key should be pasted into chat or committed.

**Next implementation action:** begin Phase 0–1 with an isolated Astro/React/Tailwind parity and hosting-feasibility spike for the actual Reimagin homepage and representative content/catalogue templates, while preserving running previews. Then build the shared Laravel content contract and real builder against that renderer. Development is authorized; production hosting remains a later, separately authorized phase.
