# Woodex Furniture OS — API inventory

Generated 2026-11-05 from static source analysis. This is a companion to `MASTER_AUDIT_REPORT.md`. The complete machine-readable list of every discovered action is in `API_INVENTORY.json`.

## 1. New backend endpoints

| Endpoint | Auth | Purpose | Source |
|---|---|---|---|
| `POST /api/admin.php` | X-WX-ADM signed token; roles owner/admin/editor/sales/support; per-action permissions | Main Admin API dispatcher | `os:public_html/api/admin.php` |
| `POST /api/builder.php` | X-WX-CSRF builder token or X-WX-ADM admin token | Live page builder: pages, backups, media, theme, blocks | `os:public_html/api/builder.php` |
| `GET/POST /api/forms.php` | Public; honeypot, IP rate limit, optional Cloudflare Turnstile | Contact/lead, booking config/slots/create, WhatsApp click stats | `os:public_html/api/forms.php` |
| `POST /api/frontend-auth.php` | Cookie wx_fe_session or Bearer wxfe_; X-WX-FE-CSRF for password change | Customer registration/login/session/password | `os:public_html/api/frontend-auth.php` |
| `POST /api/chat.php` | Public visitor chat_id + token | Live chat cfg/send/poll/typing/file/Telegram link | `os:public_html/api/chat.php` |
| `POST /api/mcp.php` | Authorization: Bearer wxmcp_… | Model Context Protocol JSON-RPC server; read tools + safe drafts | `os:public_html/api/mcp.php` |
| `GET /api/quote-view.php?id=&t=` | Signed HMAC link token | Client-facing quotation viewer | `os:public_html/api/quote-view.php` |
| `GET/POST /api/whatsapp.php` | Meta verify token + X-Hub-Signature-256 | WhatsApp Cloud API webhook | `os:public_html/api/whatsapp.php` |
| `POST /api/telegram.php` | X-Telegram-Bot-Api-Secret-Token | Telegram bot webhook | `os:public_html/api/telegram.php` |
| `GET /api/wa-cron.php?key=` | Cron key in query string | WhatsApp campaigns, social/SEO agents, offsite backups | `os:public_html/api/wa-cron.php` |
| `POST /api/r404.php` | Public beacon | 404 monitor logging | `os:public_html/api/r404.php` |

## 2. Admin action dispatcher

`POST /api/admin.php` is a single action dispatcher. It contains 297 admin/library action strings and 350 unique action strings across all OS API files. Actions are implemented across `admin.php` plus ~30 `*-lib.php` modules; the dispatcher falls through to library action handlers in `admin.php:640`.

| Domain | Action rows | Examples |
|---|---:|---|
| Core/admin/pages | 66 | status, setup, login, logout, me, schema_check, schema_repair, sys_check, db_reconnect, users, user_save, dashboard, activity, pages_list, page_meta_save, page_delete, global_menu, global_chrome, global_replace |
| Content/CMS | 30 | cms_list, cms_get, cms_save, cms_published, cms_delete, ai_run, cms_ai_models |
| Builder | 26 | pages, load, save, backups, restore, media, import_url, fetch_page, upload, theme, blocks_list, blocks_save, blocks_sync |
| MCP JSON-RPC | 23 | initialize, ping, tools/list, resources/list, prompts/list, tools/call |
| AI/SEO | 21 | seo_list, seo_page, seo_save, seo_scores, seo_ai, sag_scan, sag_propose, aic_get, aia_report |
| Live chat | 15 | chat_list, chat_assign, chat_note, chat_get, chat_reply, chat_mode, chat_close, chat_cfg_save |
| WhatsApp campaigns | 14 | wag_get, wag_audience, wag_seg_save, wag_camp_save, wag_camp_action, wag_tick, wag_test |
| CRM/leads | 11 | leads_list, lead_save, lead_note, lead_delete, leads_import, lead_convert |
| CRM/settings | 10 | See `API_INVENTORY.json` |
| Sales/projects | 10 | See `API_INVENTORY.json` |
| Social | 10 | See `API_INVENTORY.json` |
| Sales/quotes | 9 | quotes_list, quote_get, quote_save, quote_copy, quote_status, quote_send, quote_invoice |
| Telegram | 9 | tg_get, tg_save, tg_connect, tg_disconnect, tg_test, tg_link_code, tg_unlink |
| File manager | 8 | fm_list, fm_upload, fm_mkdir, fm_rename, fm_delete, fm_trash, fm_restore, fm_zip |
| Sales/invoices | 7 | See `API_INVENTORY.json` |
| WhatsApp automation | 7 | See `API_INVENTORY.json` |
| Bookings | 6 | bk_list, bk_slots, bk_save, bk_status, bk_cfg, bk_cfg_save |
| CRM/clients | 6 | See `API_INVENTORY.json` |
| Google/MCP | 6 | See `API_INVENTORY.json` |
| MCP/API keys | 6 | See `API_INVENTORY.json` |
| Notifications | 5 | See `API_INVENTORY.json` |
| Visitor chat | 5 | cfg, send, poll, typing, file, tglink |
| Frontend auth | 5 | register, login, me, logout, password |
| Media/library | 5 | media_list, media_upload, media_replace, media_trash, backup_run, backup_restore, health_scan, health_psi |
| Estimator/forms | 5 | See `API_INVENTORY.json` |
| Redirects | 5 | See `API_INVENTORY.json` |
| Google data | 4 | See `API_INVENTORY.json` |
| Database browser | 4 | dbx_tables, dbx_browse, dbx_row, dbx_export |
| Sales/templates | 4 | See `API_INVENTORY.json` |
| Sheets | 4 | See `API_INVENTORY.json` |
| WhatsApp webhook | 4 | See `API_INVENTORY.json` |
| Other | 2 | See `API_INVENTORY.json` |
| Maintenance | 2 | See `API_INVENTORY.json` |
| Sales/company | 2 | See `API_INVENTORY.json` |
| Sales/payments | 2 | See `API_INVENTORY.json` |
| WhatsApp | 1 | See `API_INVENTORY.json` |
| Sales17 | 1 | See `API_INVENTORY.json` |

## 3. MCP tools (`/api/mcp.php`)

| Tool | Mode | Purpose |
|---|---|---|
| `list_leads` | read | List leads by stage/search |
| `get_lead` | read | Full lead + notes |
| `add_lead_note` | write | Add AI-attributed lead note |
| `list_quotes` | read | List quotations |
| `get_quote` | read | Full quotation |
| `create_quote_draft` | write | Create quotation draft only |
| `list_clients` | read | List clients |
| `list_pages` | read | List published pages/SEO |
| `site_stats` | read | Dashboard stats |
| `monthly_report` | read | Monthly lead/quote report |
| `whatsapp_stats` | read | WhatsApp click stats |
| `list_content` | read | List articles/studies/city pages |
| `get_content` | read | Full content item |
| `list_templates` | read | List page templates |
| `save_content_draft` | write | Create/update content draft |
| `create_city_draft` | write | Create city page draft |
| `create_blog_draft` | write | Create blog draft |

## 4. Legacy / frontstore PHP endpoints

112 PHP endpoint-like files were found in the legacy trees (`api/` scripts plus root POST/GET handlers). Representative groups:

- **Cart:** `api/cart/add.php`, `api/cart/count.php`, `api/cart/get.php`, `api/cart/remove.php`, `api/cart/save_totals.php`, `api/cart/update.php`
- **Quote basket:** `api/quote/add.php`, `api/quote/count.php`, `api/quote/get.php`, `api/quote/remove.php`, `api/quote/update.php`
- **Search:** `api/search/suggest.php`
- **Reviews:** `api/reviews/submit-customer-review.php`
- **Product:** `api/product/quick-view.php`
- **Root form/checkout handlers:** `apply_coupon.php`, `fetch_products.php`, `filters_meta.php`, `generate_quotation_pdf.php`, `generate_quote_request_pdf.php`, `process_checkout.php`, `redirect.php`, `submit_contact.php`, `submit_quote_request.php`, `subscribe.php`
- **Public pages:** `checkout.php`, `contact.php`, `equotation.php`, `index.php`, `projects.php`, `quotation-basket.php`, `sale.php`, `servicedetail.php`, `shop.php`, `singleproduct.php`, `warranty.php`

Full per-file line-numbered action list: `API_INVENTORY.json`. Full Apache route list: `ROUTES.md`.
