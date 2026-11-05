# Woodex Furniture OS — database schema & audit appendix

Generated 2026-11-05. Source: `os:database/schema.sql` plus runtime DDL discovered in PHP. Machine-readable version: `DATABASE_AUDIT.json`.

- **Declared `wx_*` tables:** 22
- **Foreign keys:** 0
- **Engine/charset:** all `ENGINE=InnoDB DEFAULT CHARSET=utf8mb4`; no explicit collation.
- **Schema style:** additive `CREATE TABLE IF NOT EXISTS`; no migration version table, no `DROP`, no rollback scripts.

## 1. Declared tables

### `wx_users`

- Columns: 9
- Primary key (inline): `id`
- Unique (inline): `email`

| Column | Type | Definition |
|---|---|---|
| `id` | `INT` | `id INT AUTO_INCREMENT PRIMARY KEY` |
| `name` | `VARCHAR(120)` | `name VARCHAR(120) NOT NULL` |
| `email` | `VARCHAR(190)` | `email VARCHAR(190) NOT NULL UNIQUE` |
| `role` | `VARCHAR(20)` | `role VARCHAR(20) NOT NULL DEFAULT 'editor'` |
| `pass_hash` | `VARCHAR(255)` | `pass_hash VARCHAR(255) NOT NULL` |
| `active` | `TINYINT(1)` | `active TINYINT(1) NOT NULL DEFAULT 1` |
| `pw_ver` | `INT` | `pw_ver INT NOT NULL DEFAULT 1` |
| `created_at` | `DATETIME` | `created_at DATETIME NOT NULL` |
| `last_login` | `DATETIME` | `last_login DATETIME NULL` |

### `wx_activity`

- Columns: 7
- Primary key (inline): `id`
- Table keys/indexes: `INDEX(created_at)`

| Column | Type | Definition |
|---|---|---|
| `id` | `INT` | `id INT AUTO_INCREMENT PRIMARY KEY` |
| `user_id` | `INT` | `user_id INT NULL` |
| `user_name` | `VARCHAR(120)` | `user_name VARCHAR(120) NULL` |
| `action` | `VARCHAR(60)` | `action VARCHAR(60) NOT NULL` |
| `target` | `VARCHAR(255)` | `target VARCHAR(255) NULL` |
| `ip` | `VARCHAR(64)` | `ip VARCHAR(64) NULL` |
| `created_at` | `DATETIME` | `created_at DATETIME NOT NULL` |

### `wx_settings`

- Columns: 2
- Primary key (inline): `k`

| Column | Type | Definition |
|---|---|---|
| `k` | `VARCHAR(80)` | `k VARCHAR(80) PRIMARY KEY` |
| `v` | `TEXT` | `v TEXT NULL` |

### `wx_throttle`

- Columns: 3
- Primary key (inline): `ip`

| Column | Type | Definition |
|---|---|---|
| `ip` | `VARCHAR(64)` | `ip VARCHAR(64) PRIMARY KEY` |
| `n` | `INT` | `n INT NOT NULL` |
| `t` | `INT` | `t INT NOT NULL` |

### `wx_leads`

- Columns: 32
- Primary key (inline): `id`
- Table keys/indexes: `INDEX(stage)`, `INDEX(created_at)`, `INDEX(client_id)`, `INDEX wx_leads_next (next_at)`

| Column | Type | Definition |
|---|---|---|
| `id` | `INT` | `id INT AUTO_INCREMENT PRIMARY KEY` |
| `created_at` | `DATETIME` | `created_at DATETIME NOT NULL` |
| `source` | `VARCHAR(30)` | `source VARCHAR(30) NOT NULL` |
| `page` | `VARCHAR(200)` | `page VARCHAR(200) NULL` |
| `name` | `VARCHAR(120)` | `name VARCHAR(120) NOT NULL` |
| `phone` | `VARCHAR(40)` | `phone VARCHAR(40) NULL` |
| `email` | `VARCHAR(190)` | `email VARCHAR(190) NULL` |
| `service` | `VARCHAR(190)` | `service VARCHAR(190) NULL` |
| `message` | `TEXT` | `message TEXT NULL` |
| `fields` | `TEXT` | `fields TEXT NULL` |
| `stage` | `VARCHAR(20)` | `stage VARCHAR(20) NOT NULL DEFAULT 'new'` |
| `assigned_to` | `INT` | `assigned_to INT NULL` |
| `followup` | `DATE` | `followup DATE NULL` |
| `value` | `BIGINT` | `value BIGINT NOT NULL DEFAULT 0` |
| `lost_reason` | `VARCHAR(190)` | `lost_reason VARCHAR(190) NULL` |
| `client_id` | `INT` | `client_id INT NULL` |
| `tags` | `VARCHAR(400)` | `tags VARCHAR(400) NULL` |
| `is_read` | `TINYINT(1)` | `is_read TINYINT(1) NOT NULL DEFAULT 0` |
| `ip` | `VARCHAR(64)` | `ip VARCHAR(64) NULL` |
| `company` | `VARCHAR(120)` | `company VARCHAR(120) NULL` |
| `designation` | `VARCHAR(80)` | `designation VARCHAR(80) NULL` |
| `location` | `VARCHAR(160)` | `location VARCHAR(160) NULL` |
| `line` | `VARCHAR(20)` | `line VARCHAR(20) NULL` |
| `lead_type` | `VARCHAR(20)` | `lead_type VARCHAR(20) NULL` |
| `project_type` | `VARCHAR(60)` | `project_type VARCHAR(60) NULL` |
| `budget` | `VARCHAR(60)` | `budget VARCHAR(60) NULL` |
| `area` | `VARCHAR(30)` | `area VARCHAR(30) NULL` |
| `priority` | `VARCHAR(10)` | `priority VARCHAR(10) NULL` |
| `quote_status` | `VARCHAR(20)` | `quote_status VARCHAR(20) NULL` |
| `last_contact` | `DATETIME` | `last_contact DATETIME NULL` |
| `next_at` | `DATETIME` | `next_at DATETIME NULL` |
| `next_type` | `VARCHAR(20)` | `next_type VARCHAR(20) NULL` |

### `wx_lead_notes`

- Columns: 8
- Primary key (inline): `id`
- Table keys/indexes: `INDEX(lead_id)`

| Column | Type | Definition |
|---|---|---|
| `id` | `INT` | `id INT AUTO_INCREMENT PRIMARY KEY` |
| `lead_id` | `INT` | `lead_id INT NOT NULL` |
| `t` | `DATETIME` | `t DATETIME NOT NULL` |
| `user_name` | `VARCHAR(120)` | `user_name VARCHAR(120) NULL` |
| `text` | `TEXT` | `text TEXT NOT NULL` |
| `sys` | `TINYINT(1)` | `sys TINYINT(1) NOT NULL DEFAULT 0` |
| `kind` | `VARCHAR(20)` | `kind VARCHAR(20) NULL` |
| `outcome` | `VARCHAR(190)` | `outcome VARCHAR(190) NULL` |

### `wx_clients`

- Columns: 15
- Primary key (inline): `id`

| Column | Type | Definition |
|---|---|---|
| `id` | `INT` | `id INT AUTO_INCREMENT PRIMARY KEY` |
| `name` | `VARCHAR(120)` | `name VARCHAR(120) NOT NULL` |
| `phone` | `VARCHAR(40)` | `phone VARCHAR(40) NULL` |
| `email` | `VARCHAR(190)` | `email VARCHAR(190) NULL` |
| `company` | `VARCHAR(120)` | `company VARCHAR(120) NULL` |
| `city` | `VARCHAR(80)` | `city VARCHAR(80) NULL` |
| `address` | `VARCHAR(300)` | `address VARCHAR(300) NULL` |
| `notes` | `TEXT` | `notes TEXT NULL` |
| `created_at` | `DATETIME` | `created_at DATETIME NOT NULL` |
| `type` | `VARCHAR(20)` | `type VARCHAR(20) NULL` |
| `source` | `VARCHAR(40)` | `source VARCHAR(40) NULL` |
| `tags` | `VARCHAR(400)` | `tags VARCHAR(400) NULL` |
| `line` | `VARCHAR(20)` | `line VARCHAR(20) NULL` |
| `designation` | `VARCHAR(80)` | `designation VARCHAR(80) NULL` |
| `updated_at` | `DATETIME` | `updated_at DATETIME NULL` |

### `wx_notify_log`

- Columns: 8
- Primary key (inline): `id`
- Table keys/indexes: `INDEX(t)`

| Column | Type | Definition |
|---|---|---|
| `id` | `INT` | `id INT AUTO_INCREMENT PRIMARY KEY` |
| `t` | `DATETIME` | `t DATETIME NOT NULL` |
| `event` | `VARCHAR(12)` | `event VARCHAR(12) NOT NULL` |
| `ref` | `VARCHAR(120)` | `ref VARCHAR(120) NULL` |
| `name` | `VARCHAR(160)` | `name VARCHAR(160) NULL` |
| `channel` | `VARCHAR(8)` | `channel VARCHAR(8) NOT NULL` |
| `dest` | `VARCHAR(190)` | `dest VARCHAR(190) NOT NULL` |
| `result` | `VARCHAR(255)` | `result VARCHAR(255) NOT NULL` |

### `wx_wa_seen`

- Columns: 2
- Primary key (inline): `mid`

| Column | Type | Definition |
|---|---|---|
| `mid` | `VARCHAR(190)` | `mid VARCHAR(190) PRIMARY KEY` |
| `t` | `DATETIME` | `t DATETIME NOT NULL` |

### `wx_templates`

- Columns: 3
- Primary key (inline): `id`
- Table keys/indexes: `UNIQUE(name)`

| Column | Type | Definition |
|---|---|---|
| `id` | `INT` | `id INT AUTO_INCREMENT PRIMARY KEY` |
| `name` | `VARCHAR(80)` | `name VARCHAR(80) NOT NULL` |
| `data` | `LONGTEXT` | `data LONGTEXT NOT NULL` |

### `wx_quotes`

- Columns: 4
- Primary key (inline): `id`
- Table keys/indexes: `INDEX(no)`

| Column | Type | Definition |
|---|---|---|
| `id` | `INT` | `id INT AUTO_INCREMENT PRIMARY KEY` |
| `no` | `VARCHAR(30)` | `no VARCHAR(30) NOT NULL` |
| `status` | `VARCHAR(20)` | `status VARCHAR(20) NOT NULL` |
| `data` | `LONGTEXT` | `data LONGTEXT NOT NULL` |

### `wx_invoices`

- Columns: 4
- Primary key (inline): `id`
- Table keys/indexes: `UNIQUE(quote_id)`

| Column | Type | Definition |
|---|---|---|
| `id` | `INT` | `id INT AUTO_INCREMENT PRIMARY KEY` |
| `no` | `VARCHAR(30)` | `no VARCHAR(30) NOT NULL` |
| `quote_id` | `INT` | `quote_id INT NULL` |
| `data` | `LONGTEXT` | `data LONGTEXT NOT NULL` |

### `wx_projects`

- Columns: 2
- Primary key (inline): `id`

| Column | Type | Definition |
|---|---|---|
| `id` | `INT` | `id INT AUTO_INCREMENT PRIMARY KEY` |
| `data` | `LONGTEXT` | `data LONGTEXT NOT NULL` |

### `wx_chats`

- Columns: 20
- Primary key (inline): `id`
- Table keys/indexes: `INDEX(updated_at)`

| Column | Type | Definition |
|---|---|---|
| `id` | `INT` | `id INT AUTO_INCREMENT PRIMARY KEY` |
| `token` | `CHAR(64)` | `token CHAR(64) NOT NULL` |
| `created_at` | `DATETIME` | `created_at DATETIME NOT NULL` |
| `updated_at` | `DATETIME` | `updated_at DATETIME NOT NULL` |
| `name` | `VARCHAR(120)` | `name VARCHAR(120) NULL` |
| `phone` | `VARCHAR(40)` | `phone VARCHAR(40) NULL` |
| `email` | `VARCHAR(190)` | `email VARCHAR(190) NULL` |
| `page` | `VARCHAR(200)` | `page VARCHAR(200) NULL` |
| `ip` | `VARCHAR(64)` | `ip VARCHAR(64) NULL` |
| `status` | `VARCHAR(10)` | `status VARCHAR(10) NOT NULL DEFAULT 'open'` |
| `mode` | `VARCHAR(10)` | `mode VARCHAR(10) NOT NULL DEFAULT 'ai'` |
| `agent_name` | `VARCHAR(120)` | `agent_name VARCHAR(120) NULL` |
| `unread` | `INT` | `unread INT NOT NULL DEFAULT 0` |
| `needs` | `TINYINT` | `needs TINYINT NOT NULL DEFAULT 0` |
| `last_text` | `VARCHAR(255)` | `last_text VARCHAR(255) NULL` |
| `lead_id` | `INT` | `lead_id INT NULL` |
| `alerted` | `TINYINT` | `alerted TINYINT NOT NULL DEFAULT 0` |
| `channel` | `VARCHAR(8)` | `channel VARCHAR(8) NOT NULL DEFAULT 'web'` |
| `vtype` | `DATETIME` | `vtype DATETIME NULL` |
| `atype` | `DATETIME` | `atype DATETIME NULL` |

### `wx_chat_msgs`

- Columns: 7
- Primary key (inline): `id`
- Table keys/indexes: `INDEX(chat_id, id)`

| Column | Type | Definition |
|---|---|---|
| `id` | `INT` | `id INT AUTO_INCREMENT PRIMARY KEY` |
| `chat_id` | `INT` | `chat_id INT NOT NULL` |
| `t` | `DATETIME` | `t DATETIME NOT NULL` |
| `who` | `VARCHAR(8)` | `who VARCHAR(8) NOT NULL` |
| `name` | `VARCHAR(120)` | `name VARCHAR(120) NULL` |
| `text` | `TEXT` | `text TEXT NOT NULL` |
| `att` | `TEXT` | `att TEXT NULL` |

### `wx_bookings`

- Columns: 17
- Primary key (inline): `id`
- Table keys/indexes: `INDEX (d)`

| Column | Type | Definition |
|---|---|---|
| `id` | `INT` | `id INT AUTO_INCREMENT PRIMARY KEY` |
| `created_at` | `DATETIME` | `created_at DATETIME NOT NULL` |
| `lead_id` | `INT` | `lead_id INT NULL` |
| `name` | `VARCHAR(120)` | `name VARCHAR(120) NOT NULL` |
| `phone` | `VARCHAR(40)` | `phone VARCHAR(40) NOT NULL` |
| `email` | `VARCHAR(190)` | `email VARCHAR(190) NULL` |
| `type` | `VARCHAR(20)` | `type VARCHAR(20) NOT NULL` |
| `city` | `VARCHAR(80)` | `city VARCHAR(80) NULL` |
| `address` | `VARCHAR(300)` | `address VARCHAR(300) NULL` |
| `d` | `DATE` | `d DATE NOT NULL` |
| `tm` | `CHAR(5)` | `tm CHAR(5) NOT NULL` |
| `dur` | `SMALLINT` | `dur SMALLINT NOT NULL DEFAULT 60` |
| `status` | `VARCHAR(12)` | `status VARCHAR(12) NOT NULL DEFAULT 'pending'` |
| `staff_id` | `INT` | `staff_id INT NULL` |
| `note` | `VARCHAR(1000)` | `note VARCHAR(1000) NULL` |
| `src` | `VARCHAR(12)` | `src VARCHAR(12) NULL` |
| `reminded` | `TINYINT` | `reminded TINYINT NOT NULL DEFAULT 0` |

### `wx_ai_events`

- Columns: 5
- Primary key (inline): `id`
- Table keys/indexes: `INDEX(t)`, `INDEX(chat_id)`

| Column | Type | Definition |
|---|---|---|
| `id` | `INT` | `id INT AUTO_INCREMENT PRIMARY KEY` |
| `t` | `DATETIME` | `t DATETIME NOT NULL` |
| `chat_id` | `INT` | `chat_id INT NOT NULL` |
| `kind` | `VARCHAR(12)` | `kind VARCHAR(12) NOT NULL` |
| `reason` | `VARCHAR(20)` | `reason VARCHAR(20) NULL` |

### `wx_ai_unans`

- Columns: 8
- Primary key (inline): `id`
- Table keys/indexes: `INDEX(status)`, `INDEX(qkey)`

| Column | Type | Definition |
|---|---|---|
| `id` | `INT` | `id INT AUTO_INCREMENT PRIMARY KEY` |
| `t` | `DATETIME` | `t DATETIME NOT NULL` |
| `last_t` | `DATETIME` | `last_t DATETIME NOT NULL` |
| `chat_id` | `INT` | `chat_id INT NULL` |
| `q` | `VARCHAR(500)` | `q VARCHAR(500) NOT NULL` |
| `qkey` | `VARCHAR(120)` | `qkey VARCHAR(120) NOT NULL` |
| `n` | `INT` | `n INT NOT NULL DEFAULT 1` |
| `status` | `VARCHAR(10)` | `status VARCHAR(10) NOT NULL DEFAULT 'open'` |

### `wx_tg_seen`

- Columns: 2
- Primary key (inline): `uid`

| Column | Type | Definition |
|---|---|---|
| `uid` | `BIGINT` | `uid BIGINT PRIMARY KEY` |
| `t` | `DATETIME` | `t DATETIME NOT NULL` |

### `wx_tg_map`

- Columns: 3
- Table keys/indexes: `PRIMARY KEY(msg_id)`

| Column | Type | Definition |
|---|---|---|
| `msg_id` | `BIGINT` | `msg_id BIGINT NOT NULL` |
| `chat_id` | `INT` | `chat_id INT NOT NULL` |
| `t` | `DATETIME` | `t DATETIME NOT NULL` |

### `wx_frontend_users`

- Columns: 8
- Primary key (inline): `id`
- Unique (inline): `email`

| Column | Type | Definition |
|---|---|---|
| `id` | `INT` | `id INT AUTO_INCREMENT PRIMARY KEY` |
| `name` | `VARCHAR(120)` | `name VARCHAR(120) NOT NULL` |
| `email` | `VARCHAR(190)` | `email VARCHAR(190) NOT NULL UNIQUE` |
| `phone` | `VARCHAR(40)` | `phone VARCHAR(40) NULL` |
| `pass_hash` | `VARCHAR(255)` | `pass_hash VARCHAR(255) NOT NULL` |
| `active` | `TINYINT(1)` | `active TINYINT(1) NOT NULL DEFAULT 1` |
| `created_at` | `DATETIME` | `created_at DATETIME NOT NULL` |
| `last_login` | `DATETIME` | `last_login DATETIME NULL` |

### `wx_frontend_sessions`

- Columns: 9
- Primary key (inline): `id`
- Unique (inline): `token_hash`
- Table keys/indexes: `INDEX(user_id)`, `INDEX(expires_at)`

| Column | Type | Definition |
|---|---|---|
| `id` | `BIGINT` | `id BIGINT AUTO_INCREMENT PRIMARY KEY` |
| `user_id` | `INT` | `user_id INT NOT NULL` |
| `token_hash` | `CHAR(64)` | `token_hash CHAR(64) NOT NULL UNIQUE` |
| `csrf_hash` | `CHAR(64)` | `csrf_hash CHAR(64) NOT NULL` |
| `expires_at` | `DATETIME` | `expires_at DATETIME NOT NULL` |
| `created_at` | `DATETIME` | `created_at DATETIME NOT NULL` |
| `last_seen` | `DATETIME` | `last_seen DATETIME NULL` |
| `ip` | `VARCHAR(64)` | `ip VARCHAR(64) NULL` |
| `user_agent` | `VARCHAR(255)` | `user_agent VARCHAR(255) NULL` |

## 2. Schema drift and runtime DDL

- `schema.sql` is missing **handoff, ext, assigned_to, tags** on `wx_chats`, but `admin.php:schema_requirements()` requires them. `schema_repair()` adds missing columns at runtime.
- 27 additional `CREATE TABLE IF NOT EXISTS` statements were found inside PHP code, including legacy tables such as `customer_reviews`, `footer_settings`, `footer_links`, `topbar_settings`, `sale_page_settings`, `quotation_requests`, `quotation_request_items` and `product_categories`.
- Runtime migrators: `admin.php:migrate`, `admin.php:schema_repair`, `crm-lib.php:crm_migrate`, `sales-lib.php:sales_migrate`, `chat-lib.php:chat_migrate`, `booking-lib.php:bk_migrate`, `frontend-auth.php:wx_fe_schema`, `p18h-lib.php:dbx_actions`, `frontstore:footer.php`, `frontstore:header.php`, `frontstore:api/reviews/submit-customer-review.php`, `frontstore:config/product_categories.php`.
- Consequences: deployments can diverge; `CREATE TABLE IF NOT EXISTS` does not reconcile changed column types/indexes; backups taken before a runtime migration may not match code expectations.

## 3. Design observations

- No `FOREIGN KEY` constraints anywhere. Logical links (`lead_id`, `client_id`, `chat_id`, `quote_id`, `user_id`) are enforced only in application code.
- Document data is stored as JSON in `LONGTEXT`: `wx_quotes.data`, `wx_invoices.data`, `wx_projects.data`, `wx_templates.data`; `wx_leads.fields` is JSON in `TEXT`; `tags` fields are comma-separated `VARCHAR`.
- Missing/useful indexes: `wx_chats.token`, `wx_leads.email`, `wx_clients.email`, `wx_bookings` (only `d`), composite `(lead_id, created_at)`, and `wx_quotes.no` is indexed but not unique.
- No `created_at`/`updated_at` on several document tables; timestamps live inside JSON. No soft deletes, no tenant/company scoping, no row-level audit fields.
- `wx_settings` is a key/value `TEXT` store used for configuration and integration secrets; values are not encrypted at rest.
- `wx_throttle` is shared by admin login, public forms, chat, auto-reply throttling and password reset. A single `ip` primary key with prefixed keys is used as a generic rate-limit table.
- `wx_frontend_users` / `wx_frontend_sessions` implement a separate customer identity system; admin users remain in `wx_users`.

## 4. Demo seed

- `os:database/demo-seed.sql` inserts one admin and one customer demo account with bcrypt hashes.
- Credentials are documented in `os:README.md` and `os:docs/FRONTEND-INTEGRATION.md` but are intentionally **not reproduced** in this report.
- The seed is staging-only and must never be imported into production.

## 5. Separate legacy ecommerce database

The legacy PHP storefront does **not** use the `wx_*` schema. It connects through `mysqli` to a separate MySQL database configured in `site:lbpanel/config/connection.php`, `frontstore:connection.php` and `frontstore:lbpanel/config/connection.php`. No schema/ERD/migration file for that database was found in the accessible archives.

Tables referenced by legacy code include:

`products`, `categories`, `product_variations`, `orders`, `order_items`, `product_categories`, `brands`, `attributes`, `attribute_values`, `blogs`, `faqs`, `reviews`, `customer_reviews`, `coupons`, `subscribers`, `contact_forms`, `hero_slides`, `hero_sliders`, `topbar_settings`, `sale_page_settings`, `footer_settings`, `footer_links`, `projects`, `url_redirects`, `quotation_requests`, `quotation_request_items`, `pos_transactions`, `pos_transaction_items`, `pos_refunds`, `pos_refund_items`, `users`.

Because the schema is undocumented, referential integrity, indexes, column types and row counts for the legacy database could not be audited. A `mysqldump --no-data` from the live legacy DB is required to complete that part of the audit.
