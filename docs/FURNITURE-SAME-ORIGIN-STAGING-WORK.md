# Same-origin Furniture staging integration — work in progress

The owner authorized a test on `staging.blackibex.net`; `woodexfurniture.pk` is the separate real website and is not a deployment target. This work prepares the GitHub branch, not a Hostinger action. It is not a complete application package and has not been deployed to Hostinger.

## Implemented foundation

- Laravel can optionally serve the built Reimagin HTML shell at its known storefront URLs on the same origin as `/storefront-api/*`, `/login`, Livewire and `/admin`. It is disabled by default using `FURNITURE_STOREFRONT_BUNDLE_ENABLED=false`. With the flag enabled, a missing shell returns 503. Unrelated routes are not swallowed. The existing synthetic admin landing flag still takes precedence for `/`.
- The shell comes only from the fixed private application file `storage/app/storefront-preview/index.html`, not a path from a request. HTML responses are marked no-store/noindex. The feature flag is not authentication. Staging requires independent whole-site access protection and synthetic data.
- `scripts/assemble-storefront-preview.mjs` creates a **new** output directory containing `public/assets`, `public/images`, `public/og-cover.jpg`, a disallow-all `robots.txt`, and a private `storefront-preview/index.html`. It rejects executable/unknown extensions and symlinks, never copies the source sitemap or rewrite file, and refuses to overwrite an existing output. This is only a storefront fragment, not an installable ZIP or a Hostinger-specific layout.
- GitHub CI builds the actual storefront, assembles the fragment, and tests it with Laravel in a disposable same-origin PHP/SQLite HTTP environment, including a nested URL, an asset, the homepage content API, admin redirect and unknown route. This is not Apache, MariaDB, an authenticated browser test, or proof of actual Hostinger compatibility.

## Remaining before a useful Hostinger test package

Assemble the Laravel app, locked vendor dependencies, Laravel Vite assets and the storefront fragment into a versioned artifact with the app/config/storage outside the actual web root and a tested public entry point. Test the actual Hostinger rewrite/document-root arrangement and private app paths; include a matching SQL import path, safe owner setup, no secrets, site protection, and simple rollback instructions. This CI proof does not implement the complete Astro storefront, business platform or production SEO. Most inherited catalog, commerce and forms remain demo/partial; do not enter real orders or data. The open-access 5188 preview must not be packaged.

The retained GitHub CI SQL files are synthetic evidence only, not installation instructions or a final release database. MariaDB/MySQL round-trip validation remains separate from the SQLite same-origin smoke; neither confirms the real hosting environment.
