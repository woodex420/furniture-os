# Furniture homepage preview: revision foundation

This is a first CMS foundation slice, **not** the full page model, all-page editor or visual page builder. It covers only the two first-slide text fields already connected to the real Reimagin preview. The owner-only editor is still behind `FURNITURE_HOME_EDITOR_ENABLED`, which defaults to false.

## Behavior

- The migration captures the existing singleton's exact current title, subtitle and revision in one `baseline` snapshot. Null fields remain null (the frontend's original fallback). Earlier audit rows did not contain text, so older versions cannot be reconstructed; the migration does not invent them.
- Each accepted edit writes current content, the existing audit record and a new immutable-by-application snapshot in one transaction. Actor permissions are rechecked inside the transaction; a stale submitted revision is rejected.
- Site Overview shows the 20 latest snapshots. An authorized restore copies the chosen snapshot into a **new** current revision, records its actor and source revision, and triggers the existing browser polling. Restoring never rewrites the prior snapshot. Text is escaped in the admin view. Save/restore require CSRF/session and are throttled.
- Baseline, edit and restore are not a general draft/publish pipeline. Browser-only refresh of the current preview is not proof of crawler-visible Astro HTML freshness or production publishing. The underlying MySQL/MariaDB concurrency behavior still needs a real target-database test; local tests run on synthetic SQLite.
- Migration rollback deletes the history table: back up the database before rolling back or deploying; the current home content row is not deleted by this migration's `down`. Do not claim older snapshots exist before the migration.

## Synthetic Arena login

The running port 5176 app is a disposable copy using synthetic SQLite; its explicitly public demo email/password are prefilled **only in that copy**, so visitors can select Sign in. Its temporary session settings use Secure + SameSite=None + Partitioned cookies for cross-site embedded previews; no production session setting or live database was changed. Local HTTPS iframe tests with third-party-cookie phaseout enabled passed login/logout and a save/restore round trip. Browser support and Arena's actual embedding environment may differ; opening the preview in a new tab is a useful fallback. Preview scripts, database and overrides are in `/tmp`, not a production configuration or permanent deployment artifact.

## Verification

Feature tests cover upgrade baseline, edit and restore, null fallback, validation, stale writes, owner/guest authorization, escaping, and transaction rollback when the snapshot write fails. The real storefront and the synthetic embedded admin were exercised together. Native local PHPUnit needs a patched launcher because this sandbox lacks `xmlwriter`; hosted CI runs the standard suite. Further release gates and next steps are in `WOODEX-FURNITURE-WEBSITE-STUDIO-MASTER-PLAN.md`.
