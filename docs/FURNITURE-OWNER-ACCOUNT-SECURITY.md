# Furniture owner account security — first implementation slice

The founder selected the **full business platform** as the intended first-release scope and **one owner account first**. Build frontend, backend and Furniture-owned database together. Existing reference/demo claims and data are not automatically approved Furniture content. Preparing the project does not authorize changing Hostinger or importing data; confirm the target and authorization before deployment. Furniture and Interior stay separate.

## Implemented in tracked Laravel source

- Authenticated owner can open **Account security** and change their own password. The current password is required; the new password must match its confirmation, be different, contain at least 12 characters and fit within 72 bytes. This matches the initial owner provisioning bounds. Passwords are never populated into forms or deliberately flashed back.
- The form is under the existing owner gate and normal Laravel CSRF protection, and submissions are rate-limited. Successful change rotates the current session ID, CSRF token and remember token. The current signed-in session is retained.
- Laravel's `AuthenticateSession` web middleware binds sessions to the password hash. Once bound, a session with the old hash is signed out on its next web request. A normal new login gets bound automatically. **Important deployment transition:** a session created before this middleware was added, which never visited the updated app, is not yet bound and may bind on its first request after a password change. At a controlled deployment, invalidate existing sessions/remember tokens with a verified store-specific procedure; test it on staging. This implementation does not claim instant termination of in-flight requests or a complete session-management UI.
- The code is not copied into the temporary no-login 5188 preview. That intentionally open synthetic runtime must not expose account security controls as if it were authenticated. It is separate from the source application and unsuitable for real data.
- Verification: local partial PHP/SQLite suite passed 127 tests / 2,033 assertions; Pint passed for changed PHP files. A separate disposable browser test used two independent sessions: changing the password retained the current session, rejected the other session and allowed sign-in with the new password. This test did not use the open preview or real data.

## Still required before release

Account recovery, MFA decision, staff roles/record policies, account disablement, shared-store rate limiting, security audit and real HTTPS/proxy/session verification remain pending. The owner's initial password must be unique and provisioned privately; never put it in Git, shared previews, command history or chat. SQLite feature tests are not MySQL, hosting or independent security verification. No real owner account or deployment was modified.
