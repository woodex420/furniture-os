# Reimagin v1 / Furniture integration progress

## Source and preview

The existing isolated Reimagin v1 frontend is imported into Furniture source control. It is a React/Vite/Tailwind snapshot of `blackibexofficial-blip/woodex-reimagined` commit `e8502b0d9c376a985b50ff610ed1d6019b6449a0`, with local preview modifications. Upstream `main` was checked at `5123af95643404649901d753eb8b4e8384c8b969`; the two commits since the snapshot only modified `roadmap.md`. This evidence applies to that comparison, not any other branch or repository. The old 2030 storefront preview process was stopped, not deleted; port 5174 now runs this Reimagin frontend and port 5176 remains the Furniture Laravel admin. The old Reimagined demo admin/Puck routes are no longer exposed in this preview but their source is preserved.

The request is for a full real-time visual builder over the real Reimagin pages with selection and movement controls. That is **not implemented yet**. The first integration slice edits the actual homepage's first slide title/subtitle from Furniture's owner-only Website Studio → Site Overview. A successful save is immediately visible at the public content API; the preview homepage polls it while visible. This is not the entire homepage, not drag/drop, not immediate keystroke publishing, and not every template or page. The original React homepage remains the renderer. The API and editor are disabled by default outside the synthetic preview (`FURNITURE_HOME_EDITOR_ENABLED=false`).

The write path validates bounded text, uses session authentication, request-forgery protection, authorization rechecked on the persisted owner, rate limiting, optimistic revision conflict detection, transactional updates and minimal change metadata. The read-only content endpoint is no-store and sessionless. Preview traffic to Laravel uses a Vite **server-side** proxy with a relative browser URL. Future deployment needs a separately reviewed same-origin backend mapping. Local browser testing through simulated proxy hosts checked sign-in, a real homepage image, owner save, visible homepage update, restoration of sample content, mobile widths and sign-out. No actual public site or Hostinger database was changed.

## Interior SQL reference

The supplied `woodex420/woodexinterior` branch `arena/d89528a7-woodexinterior` pointed to commit `fd59a5f93931196676494510f917759669fbd97b`; `woodex-os/src/schema.sql` blob `273ba24f84d9276d3db46e92f02b00f802d1136b`, SHA-256 `a4c9e9ae369fb71b736bc5070528190e758f9115409194ed21478a688ce442b2`, is **SQLite**, not MySQL. Its tables for pages, revisions, navigation, media and activity inform future Furniture design; SQLite pragmas, types and expressions cannot be imported as Hostinger MySQL SQL. No Interior data, credentials or live backend were connected or imported.

## Remaining

- Design the shared page/section/element model and render the exact Reimagin components inside the visual editor; support selectable nested elements, move/duplicate/delete, properties and responsive previews without losing unknown data.
- Bring each frontend route, content type, commerce operation and media pipeline onto reviewed Furniture Laravel contracts. Resolve the inherited TypeScript failures and test actual workflows rather than treating a bundle build as complete integration.
- Design versioning, concurrent edits and recovery appropriate to the founder's immediate-publishing preference. Test permissions, transactional behavior, security and backups on the actual MySQL/MariaDB version. No hosted deployment has been performed.
