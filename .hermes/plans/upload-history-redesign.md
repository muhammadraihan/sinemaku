# Upload Form Redesign & Upload History

## Goal
Make report upload easier to understand and provide a durable history of uploaded files.

## Background
The current flow uses one generic modal for XLSX/PDF uploads and keeps preview state in short-lived cache tokens. Operators need the original filename, uploader, and upload time without mixing upload history with canonical report rows.

## Scope
### In
- Redesign the existing Blade upload modal without changing parser/preview/confirm contracts.
- Persist one upload-history record only after the canonical report rows are successfully committed.
- Track provider, original filename, uploader, import time, imported row count, and safe message.
- Do not create history records for failed, abandoned, expired, blocked, or unconfirmed previews.
- Add authenticated history endpoint and an informative history panel/table on the Pelaporan page.
- Cover legacy XLSX, NSC XLSX, Cinepolis PDF, and Platinum PDF flows.

### Out
- Storing uploaded binary files permanently.
- Replacing the existing preview token mechanism.
- Changing canonical Pelaporan schema or manual reporting.
- Adding destructive history deletion.

## Assumptions
- Existing authenticated `web` user and `users.uuid` are the uploader identity source.
- History is application-wide for authorized backoffice users, ordered newest first.
- Original filename is metadata only; no credentials or file contents are stored.
- Preview metadata stays only in the user-bound cache token. A history row is created inside the same successful confirm transaction as canonical `pelaporans` insertion, with status `Berhasil diimport`.

## Files / Systems Likely to Change
- New upload histories migration/model.
- PelaporanController preview/confirm/history methods.
- Upload routes.
- Pelaporan Blade upload modal and history panel/scripts/styles.
- Feature tests for persistence, status transitions, user identity, and UI contract.

## Acceptance Criteria
- Preview creates no history record and exposes no history identifier.
- Successful confirm creates exactly one `Berhasil diimport` history record with original filename, provider, uploader, import timestamp, and inserted row count in the same transaction as the report rows.
- Failed, duplicate, abandoned, expired, or blocked previews create no history record.
- History endpoint returns newest records with uploader name and formatted metadata, without exposing cache tokens or file contents.
- UI clearly separates upload action, supported formats/20MB limit, preview-before-save explanation, and recent upload history.
- Existing preview/confirm behavior and all current tests remain passing.

## Verification
- TDD feature test red then green for history creation/status update.
- Laravel feature/unit suite, PHP lint, route list, Blade cache, git diff check.
- Run local server and inspect the Pelaporan route with browser/curl; verify history endpoint contract and rendered page source.

## Risks / Open Questions
- Existing isolated feature tests create minimal schemas; history writes must be guarded or their schemas extended in new history-specific tests.
- Confirm methods are provider-specific, so status update must be centralized and called only after the canonical transaction succeeds.
