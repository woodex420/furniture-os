# Furniture builder document foundation — experimental, not published

This is the first internal storage/contract slice for the multi-page builder. It does **not** replace the existing home, service, city or blog bridges; create any public/admin route; migrate existing pages; or render/publish a document. No records are seeded. The existing Reimagin preview and Astro experiments remain separate. The final route registry, publishing strategy and general builder are still required.

## V1 contract

Laravel `BuilderDocumentV1` accepts a root with exactly `schema_version: 1` and `sections` (an ordered list of up to 10). A section has exactly `id`, `type`, and `props`; IDs are unique lowercase canonical v4 UUIDs. The only initial type is `article_text`, with exactly `summary` and `body` strings. These names match the actual Reimagin `ArticleText` component's props; TypeScript definitions in `src/pages-builder/furniture-document-v1.ts` express that compile-time relationship. Text is nonblank, valid UTF-8, limited to 300/12,000 Unicode characters, and encoded JSON is capped at 65,536 bytes. Text is retained as text, not interpreted as HTML. Empty section lists are allowed. Unknown versions, properties and section types are explicitly rejected rather than stripped. Types alone do not validate untrusted JSON; the Laravel contract is authoritative. Any future HTTP input must independently address request-size/depth limits and duplicate JSON object keys before decoding, and validate on the server.

This is deliberately narrow: it has no media, styling, links, layout nesting, route, SEO fields, catalogue data, page ownership or visibility. Adding them needs explicit schema versions/migration and shared renderer support. It is not a completed generic page model.

## Revisions and safety

`BuilderDocumentRevisions` is an internal service with no HTTP entry point. It rechecks the current actor under lock against the owner workspace and storefront-edit gates, validates every document, serializes writes in a transaction, checks the expected revision and uses a conditional update, then appends a per-document version. Restore creates a new revision after validating its source; it cannot use another document's history. Corrupt/unsupported stored data is rejected rather than overwritten. A failed snapshot rolls back the content change. This is storage history, not publishing or proof of a concurrent database workload.

The additive migration creates two new empty tables. Rolling it back **drops all documents and versions stored in these tables**. Back up first; never roll back real data without a tested recovery plan. The current home, city, service and blog tables are untouched. MySQL/MariaDB CI tests exercise synthetic create/edit/restore and SQL roundtrip; they do not prove concurrent behavior or actual Hostinger compatibility. No automatic production deployment is performed.

## Remaining before use

- Establish one route registry, conflict/collision handling, accessible typed component registry and shared renderer; connect actual Reimagin pages and the owner canvas.
- Design migration for the existing hero/media/tagline and all revisions, preserving identities and older clients; test isolated round trips and rollback before a cutover.
- Prove atomic accepted-save-to-visible publication and crawler-visible current HTML on the actual hosting model, including invalidation and rollback. No mandatory separate publish step for ordinary accepted edits.
- Add API/controller feature gating, authorization, CSRF, rate and request-size limits, audit/access rules, robust reads, concurrency tests and browser coverage before exposing this service.
- Add remaining templates, component types and visual controls; complete CRM and business modules separately. Passing unit tests does not mean the platform or builder is complete.
