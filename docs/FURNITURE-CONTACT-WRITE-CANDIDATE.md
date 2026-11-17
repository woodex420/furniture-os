# Contact creation candidate — isolated and unrouted

**Status: development candidate only.** The customer UI remains read-only. There is no contact write route, Livewire action, API, import, deployment or provider call. No real customer information or hosting database was used. This work does not approve the proposed policy for business use.

## Candidate behavior

- `CreateContactCandidate` requires an authenticated user with both explicit `contacts.view` and `contacts.create` abilities, currently owner-only. It re-reads and locks the actor inside the transaction before checking those abilities again; this is not a completed staff/record-level permission model.
- It validates a name, optional email and phone, and a caller-supplied UUID idempotency key. It trims fields, normalizes email to lowercase, and provisionally accepts ASCII email addresses only. A repeated request by the same actor with the same key and normalized payload returns the original contact; reuse with different details fails. A matching normalized email is rejected, not merged or overwritten. Missing email is permitted and does **not** prevent duplicate names or phones. Phone normalization, Unicode email, cross-owner visibility and duplicate/merge behavior require explicit business and security review.
- A singleton guard row serializes this service's duplicate check on databases with working row locks. This does not protect against direct SQL or other write paths. The transaction writes the contact, idempotency record and a minimal `contact.created` audit event atomically. The event stores actor and contact identifiers, not the customer's name, email or phone. Idempotency records contain a SHA-256 payload digest; retention and privacy policies are pending.
- The guard, idempotency and audit tables are provisional. Their migration has not been validated on Hostinger's actual MySQL/MariaDB version, against existing installations, or for backup/restore. Its down migration drops records and must not be used as a data rollback plan.

## Evidence and release gates

The temporary PHP 8.4/SQLite modified-runner suite passed **44 tests / 394 assertions**. Synthetic tests cover guest/non-owner denial, stale owner recheck, missing guard, validation, idempotent retries, key conflicts, duplicate email rejection, no-email behavior and rollback on injected audit failure. This runner bypasses the missing local `xmlwriter` check. GitHub [run 44029166522](https://github.com/woodex420/furniture-os/actions/runs/44029166522) passed standard native locked validation for code SHA `7937498a107b6d7a70137d06dbf3088c1fff20ee`. These tests do **not** prove concurrent locking in MySQL, a deployed session, or absence of other write paths.

Before any UI or route is enabled, agree the field and duplicate rules, define record-level staff visibility and concurrency behavior, test concurrent requests and retries on the verified MySQL/MariaDB version, review privacy/audit retention, add HTTP CSRF/rate-limit and end-to-end security tests, and obtain any required business approvals. Keep hosting, schema upload, real data and deployment behind the existing separate approval gates.
