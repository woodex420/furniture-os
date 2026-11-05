# Woodex Furniture OS — audit appendix index

**Main deliverable:** [`../MASTER_AUDIT_REPORT.md`](../MASTER_AUDIT_REPORT.md)  
**Generated:** 2026-11-05  
**Scope:** static audit of the three accessible Google Drive archives; fourth 150 MB archive still required for full four-archive coverage.

## Reading order

1. `../MASTER_AUDIT_REPORT.md` — executive summary, architecture, frontend/backend/database/security audit, roadmap, target architecture.
2. `SECURITY_FINDINGS.md` — 22 findings with severity, evidence paths/lines, impact, remediation, and positive controls.
3. `API_INVENTORY.md` — endpoint table, admin action groups, MCP tools, legacy endpoint groups.
4. `DATABASE_SCHEMA.md` — all 22 `wx_*` tables column-by-column, drift, demo seed, legacy DB notes.
5. `ROUTES.md` — all `RewriteRule` lines from the three `.htaccess` files.
6. Machine-readable JSON/CSV: `API_INVENTORY.json`, `DATABASE_AUDIT.json`, `STATIC_SCAN.json`, `SOURCE_INVENTORY.csv`.

## File guide

| File | Format | What it contains |
|---|---|---|
| `API_INVENTORY.md` | Markdown | Human-readable endpoint/action/MCP/legacy API summary. |
| `API_INVENTORY.json` | JSON | 350 unique OS API actions with `action`, `file`, `line`; grouped action counts; 11 endpoint docs; 18 MCP tools; 112 legacy endpoint paths. |
| `DATABASE_SCHEMA.md` | Markdown | Full `wx_*` schema, keys/indexes, drift, design observations, demo seed, legacy database notes. |
| `DATABASE_AUDIT.json` | JSON | Machine-readable schema, primary keys, indexes, foreign keys, drift, runtime migrators, legacy table references. |
| `SECURITY_FINDINGS.md` | Markdown | Critical/high/medium/low findings, evidence, remediation, positive controls, static-scan interpretation. |
| `STATIC_SCAN.json` | JSON | Regex counts, redacted secret-scan summary, archive SHA-256, release-manifest verification. |
| `ROUTES.md` | Markdown | Line-numbered `RewriteRule` inventory for `os`, `site`, and `frontstore`. |
| `SOURCE_INVENTORY.csv` | CSV | Per-tree, per-extension file counts and byte sizes. |
| `MASTER_AUDIT_REPORT.html` | HTML | Print-friendly HTML rendition used to create the Google Doc copy. |

## Drive copies

- Markdown: <https://drive.google.com/file/d/1xmDZDboz7kkxCiFGNV8w_l3zHfKRBimH/view?usp=drivesdk>
- Google Doc: <https://docs.google.com/document/d/1kKPbj_1GxJOhGMe2-BKS5kswuKXCVrSE85cAiIDLXPo/edit?usp=drivesdk>
- Appendices ZIP: <https://drive.google.com/file/d/1D4kjw7l3mAa6omgExfXVdn6joqQHghQs/view?usp=drivesdk>

## Handling notes

- No password, API key, token, or database credential value is included in these files. Config values are referenced by file/line only and marked redacted.
- Paths use `tree:relative/path` notation (`os:`, `site:`, `frontstore:`).
- Regex counts are indicators, not confirmed vulnerabilities. See `SECURITY_FINDINGS.md` for manually verified findings.
- The original archives were downloaded to a temporary workspace and are not committed to Git. Local copies were removed after checksum calculation; the authoritative copies remain in the user's Drive folder.
- To complete the audit, supply `woodexfurniture.pk (1).zip` (`1wR1YlJ477N_ICp8JBe6sbePX3ThgQYyA`, 150,458,517 bytes) in parts under 95 MB, then issue an addendum.
