# Furniture lead creation — preview increment

The founder requested the next workflow while keeping the page builder on hold. The Furniture admin now has experimental owner-only manual lead creation in the isolated preview. **Do not start the page builder until the founder has been asked about its Reimagin v1 requirements.** Nothing in this increment changes the page builder or deploys to Hostinger.

## Behavior and limits

- The feature is enabled in the synthetic preview and disabled by default elsewhere (`FURNITURE_LEAD_CREATION_ENABLED=false`). The owner may enter a required title, optional source and optional existing contact. Leads begin at `new`; editing, status changes, assignment, imports, conversion and automatic deduplication remain unimplemented. Different request keys can create leads with the same title.
- The contact selector shows at most the 100 most recently added contacts; selection/search for older records still needs a scalable UI. If `contacts.view` is denied, no contacts are queried or shown and forged contact IDs are rejected before lookup. A selected contact must still exist when submitted.
- The form uses session protection and the framework's request-forgery middleware, a rate limit, validation, an actor-scoped UUID idempotency key and private/no-store headers. The service rechecks the persisted actor and relevant capabilities inside a transaction. Contact visibility is required again for linked leads.
- A shared customer-write guard serializes cooperating service writes, and lead, request and minimal identifier-only audit event are committed atomically. The guard does not cover direct SQL or independent write paths. MySQL/MariaDB concurrency and target hosting behavior are not yet proven. Idempotency digests and audit retention need review.
- A down migration removes the new audit/idempotency tables; it is not a data recovery plan. Validate migrations, locking, backup/restore, authorization and retention on the actual target database before enabling this workflow for a hosted deployment.

Synthetic SQLite tests exercise access, validation, contact permission, retry/conflict, a stale owner, missing guard, rate limit and audit rollback. A local browser test through a simulated internal-host proxy checks login, responsive form, creating and linking a synthetic contact, search, logout and tokenless request rejection without browser fetch metadata. This does not prove the actual Arena proxy, hosted MySQL or production readiness. Furniture and Interior remain separate Woodex applications; no Interior data or credentials were used.
