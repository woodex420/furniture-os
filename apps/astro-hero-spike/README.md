# Astro shared-hero prototype

This is an isolated **noindex**, static Astro + React + Tailwind 3 experiment. It renders the **same** `HeroSlider` component used by the actual Reimagin Vite homepage, including its temporary Furniture `/storefront-api/home` polling integration. It is not the completed Astro storefront, visual builder, production release or hosting decision. There is deliberately no production canonical, catalogue or deployment setup here. Its CTA points to a storefront route not provided by this one-page prototype.

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

The backend address is used **only by the dev server**, not by visitors' browsers. Omit `FURNITURE_PREVIEW_API` to see the original hero fallback without a backend. Arena preview uses `ARENA_PREVIEW=1` to allow the preview host and configure its HMR port. The Reimagin app remains on port 5174; the separate Furniture admin runs on port 5176. The prototype depends on sibling source and packages; CI installs both.

## Verified and not verified

- Astro prerenders the actual hero markup, fallback text, five valid emitted images and noindex metadata. React hydrates it for carousel interaction. The shared Tailwind config is adapted from Reimagin. The source asset imports use `?url` because Astro's normal image imports produce metadata objects rather than the strings the original React component expects.
- The current hero reads the first title/subtitle from Laravel in the browser; it polls while the tab is visible. Its **initial static HTML always contains the build-time fallback**, not the current Laravel revision. This is not sufficient for production SEO or immediate crawler-visible publishing. CMS schema, full templates and save/publish pipeline remain future work.
- Local static tests assert output and emitted assets. Manual automated browser tests compared hero geometry at 320/375/768/1440 widths, image loading, carousel and a simulated API update; an authenticated synthetic admin save/update/restore also propagated to both preview pages. Browsers under localhost may log the expected Arena HMR websocket warning because that proxy is not present in localhost tests. Actual Arena browser, Hostinger and MySQL have not been independently verified.
- Astro's own locked dependency audit returned zero advisories when checked. Reimagin's separate inherited dependencies still have advisories including `xlsx`, and its pre-existing TypeScript errors remain. This experiment must not be deployed without a fresh audit and full release gates.
