# Astro shared-component prototype

This isolated **noindex**, static Astro + React + Tailwind 3 experiment is not the completed storefront, visual builder, release package or hosting decision. It reuses selected components from the actual Reimagin preview:

- `/` renders the real `HeroSlider`, including its temporary browser-side Furniture `/storefront-api/home` polling.
- `/experiments/product/` renders the real product `ImageGallery` with two synthetic example images. There are no prices, cart, stock or order actions. Its inherited 360° effect is only a visual effect, not a true product rotation.
- `/experiments/article/` renders a synthetic version-1 document via `DocumentSections` and the same plain-text `ArticleText` component used by the connected Reimagin blog detail page. Its HTML is escaped and server-rendered without a JavaScript island. A shared fixture checks equivalent markup against the separate Laravel PHP-only synthetic proof; it does not synchronize live data or guarantee full visual parity.

These are component experiments, **not** complete category, product or editorial pages. Prototype pages have no production canonical, authoritative catalogue or deployment setup. The hero's existing shop links are not supplied by this prototype.

## Run locally

Requires Node >= 22.23.4 (Astro 7) and the sibling Reimagin dependency install. No environment secrets or third-party services are required.

```sh
cd apps/reimagined-storefront
npm ci --legacy-peer-deps --ignore-scripts
cd ../astro-hero-spike
npm ci --ignore-scripts
npm run build
npm test
FURNITURE_PREVIEW_API=http://127.0.0.1:5176 npm run dev -- --host 0.0.0.0 --port 5177
```

The backend address is used **only by the dev server**, not by visitors' browsers. Omit `FURNITURE_PREVIEW_API` to see the original hero fallback without a backend. Arena preview uses `ARENA_PREVIEW=1` to allow the preview host and configure its HMR port. A static preview may be started with `ARENA_PREVIEW=1 ./node_modules/.bin/astro preview --host 0.0.0.0 --port 5201`. This is a synthetic open preview, not staging access control. The prototype depends on sibling source and packages; CI installs both.

## Verified and not verified

- Static output tests check noindex metadata, hero and gallery assets, and literal, escaped article text without JavaScript. A synthetic browser test exercises gallery hydration, thumbnail and previous-image controls and responsive widths of 320, 375, 768 and 1440 pixels, and blocks external requests. It does not establish full design parity, accessibility, all interactions or production readiness.
- The hero reads first-slide text from Laravel in the browser, but its **initial static HTML always contains the build-time fallback**, not the current revision. The article experiment uses a static fixture, not published Laravel content. Neither demonstrates crawler-visible immediate publishing, which remains a hard requirement.
- The existing Furniture preview remains React/Vite; Astro is not included in the staging candidate. Full shared page templates, category and product data, editorial publishing, safe catalog/commerce contracts, page schema, visual editor and hosting strategy remain open. Preserve the owner's requirement for immediate publication.
- Reimagin and Astro have separate lockfiles and dependency risks. Re-run audits for the actual release; see the current production readiness checklist. Tests use synthetic data and do not validate Hostinger.
