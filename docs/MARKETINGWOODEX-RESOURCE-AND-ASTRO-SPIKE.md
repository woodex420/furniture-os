# Founder-supplied marketingwoodex reference and implementation start

## Reference boundary

The founder authorized reusing useful material from `https://github.com/marketingwoodex-cloud/-marketingwoodex`, while leaving the original untouched. Read-only GitHub API inspection resolved `main` to commit `63868e0f5120dae3214806aa33db0757b4981112` (the branch may move later). Its README describes two website generations, a React/TypeScript admin (`admin-v25`), PHP API (`api/v2`) and a separately packaged `frontend-v1` builder. The tree includes reference screens for global settings, heroes, pages, content, templates, media, forms, estimator, projects and SEO; builder source and PHP endpoints are present under `frontend-v1`. These are implementation ideas and interaction references, **not evidence that the Furniture app already implements them**. The source repository itself states that host publication and rollback need a real host gate. Its API/files/data model are not Laravel's and its static-file writes are not an Astro publishing pipeline.

No code or customer/content records have been copied from this repository and no original repository settings, branches or files have been changed. No root license file was found in the tree/API. Founder permission is noted, but third-party assets and vendor licenses must be reviewed before any copying. Do not import Interior business data, credentials, keys, media or outbound integrations into Furniture automatically.

## First implementation slice

- Extracted the real Reimagin hero into `apps/reimagined-storefront/src/components/home/HeroSlider.tsx`; the existing homepage now uses that component. Its first-slide Laravel bridge remains intact. Slider labels now say “slide”; its shop CTA uses a regular anchor so it works without a React Router context in the Astro prototype (this means a full-page navigation on the Vite site).
- Added `apps/astro-hero-spike`, a separate Astro 7 + React 18 + Tailwind 3 experiment that imports the **same component**. It renders fallback hero HTML at build time and hydrates it; noindex protects the prototype. It does not replace the existing 5174 preview.
- The static output has no way to render the latest Laravel changes for crawlers until rebuilding. Browser polling is only a temporary preview. Hosting/runtime/freshness decision, full Astro conversion, builder, persistence for remaining content and commerce remain open.
- Reimagin and prototype builds, static output checks, local browser component parity and synthetic admin update/restore have been tested. `apps/astro-hero-spike/README.md` contains reproducible setup and limitations. CI has a separate build and test job for this slice; this is not a hosting release gate.

## Next slices

1. Inventory actual Reimagin routes, data sources and section/component fields with explicit demo and commerce boundaries; implement at least one representative content and catalogue rendering path in the Astro spike.
2. Build a versioned, strictly validated Laravel Furniture page model and revision/audit service, migrating the existing first-slide fields without loss. Test concurrent writes and the target MySQL/MariaDB before calling it production ready.
3. Wire a visual selection/inspector canvas to the same real components and owner-protected save API; progress beyond the temporary two-field form only when save, reload, conflicts, rollback and storefront parity work.
4. Continue Website Studio modules, SEO and media according to `WOODEX-FURNITURE-WEBSITE-STUDIO-MASTER-PLAN.md`. Test a practical publication strategy on Hostinger separately; no deployment or real-data migration has happened.
