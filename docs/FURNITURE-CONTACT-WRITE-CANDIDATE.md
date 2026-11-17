# Contact creation — experimental Furniture workflow

The founder requested that development actions be unblocked. The Furniture admin now includes an **experimental owner-only contact creation form**, enabled in the isolated synthetic preview. The form is off by default in application configuration (`FURNITURE_CONTACT_CREATION_ENABLED=false`); it must be explicitly enabled for development/testing. No Hostinger deployment, real database connection or Interior data access was performed. Editing, imports, staff access and other customer writes are not implemented.

## Behavior

- A logged-in owner with `workspace.view`, `contacts.view` and `contacts.create` can submit name, optional email and phone. The HTTP form uses session/CSRF protection, a 20-per-minute user throttle, bounded validation, private/no-store responses and a UUID request key. No public write API is exposed.
- `CreateContactCandidate` re-reads and locks the actor and checks the two contact abilities inside the transaction. Reusing the same actor's key and normalized payload returns the original contact; reuse with different details fails. The success page links to the contact detail.
- Name is required. Email is trimmed, lowercased, provisionally ASCII only, and rejected if a case/whitespace-normalized match exists; it is not merged or overwritten. Missing email is allowed and does not deduplicate name or phone. Phone normalization, international email, and duplicate/merge policy still need design.
- A singleton guard serializes the duplicate check where the database supports row locks. It does not protect against direct SQL or other write paths. The transaction creates the contact, actor-scoped idempotency record and minimal `contact.created` audit event together. Audit rows contain identifiers, not name/email/phone; idempotency records contain a SHA-256 payload digest. Retention and privacy policies remain to be designed.

## Evidence and remaining work

The isolated synthetic SQLite suite includes service and HTTP tests for authorization, validation, duplicate handling, retries, audit rollback, disabled-by-default behavior and throttling. Browser testing exercises the form and CSRF through a simulated proxy. SQLite tests do not establish MySQL/MariaDB concurrency behavior. Before enabling this feature on any actual hosted database, validate migrations and concurrent requests on the target MySQL/MariaDB version, review staff/record-level authorization, privacy and retention, backup/restore, and deployment configuration. Do not use the migration's down operation as a data rollback plan.

Furniture and Interior are separate Woodex applications. Interior remains a reference; no data, credentials, migrations or live actions have been copied or connected.
