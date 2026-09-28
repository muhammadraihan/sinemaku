# Editable import preview — implementation and verification

## Audited active flows

| Provider | Intake | Cached source | Confirm/remap |
|---|---|---|---|
| XXI | PDF via XxiPdfParser; Excel via parseLegacyExcel | legacy_excel_preview, source_type flag | confirmLegacyExcel; quickMasterLegacy; assignXxiFreeShow |
| CGV | Excel via parseLegacyExcel, paid/free fan-out | legacy_excel_preview | confirmLegacyExcel; quickMasterLegacy |
| SAMS STUDIOS | Excel via parseLegacyExcel, REGULAR/BOGOF/FREE PASS | legacy_excel_preview | confirmLegacyExcel; quickMasterLegacy |
| NSC | Excel via NscXlsxParser, paid/BOGOF and pending Free | legacy_excel_preview | confirmLegacyExcel; quickMasterLegacy; assignNscFreeShow |
| CINEPOLIS PDF | CinepolisPdfParser | cinepolis_pdf_preview parsed rows | confirmCinepolisPdf; quickMasterCinepolis |
| PLATINUM PDF | PlatinumPdfParser | platinum_pdf_preview parsed rows | confirmPlatinumPdf; quickMasterPlatinum |

Two existing preview renderers are retained. Both now mount the shared inline editor and client-side DataTable. Wizard heights/layout remain unchanged.

## Contract and boundaries

Authenticated POST `pelaporan.import-preview.correct` accepts only provider, token, stable row_id, a whitelisted changes map, and a required reason. Canonical UUIDs, gross/net/tax, original rows and source totals are not editable input. PDF per-row editable fields: date, time, studio, ticket type, price, admissions (show is supported server-side). Legacy fields additionally include film, source cinema/city and show. PDF report-level cinema/film continue through existing source mapping rather than per-row overrides.

Original normalized rows are preserved before edits. Correction history stores before/after, original row, user, reason and timestamp; successful atomic confirmation writes it into the existing upload-history message field when that table exists. No schema migration required.

Thirty-minute original expiry is retained across refreshes; a global shared-cache import mutation lock serializes correction, quick-master, free allocation and confirmation. Deployment must use a shared lock-capable cache for multi-worker/multi-host coordination (array cache is only suitable for isolated tests).

All remaps retain correction issues and IDs. Free allocation creates a new ID rather than inheriting the paid row's identity/issues. Confirm remaps from server-held source rows and rechecks duplicate/mapping gates, then writes canonical rows and history in one transaction and consumes the token.

XXI PDF prices remain dated-master-controlled. Other legacy prices/gross/net retain the existing master tax contract. PDF financial recalculation preserves original effective tax/net ratios, including Platinum inclusive-net/deduction semantics. Any admission/price change remains explicitly blocked against the immutable original detail; restoring original values clears that issue. Operators must upload corrected source evidence rather than overriding source totals. This conservative policy deliberately does not offer a totals-bypass control.

## Verification

- Full host PHP Laravel suite: 129 passed (isolated SQLite/array cache feature tests).
- Actual Cinepolis PDF fixture upload → inline correction → confirm tested; Platinum controller uses its existing synthetic parser fixture; parser unit tests run in full suite.
- XXI PDF synthetic parser fixture proves price override rejection and correction→confirm keeps dated-master price.
- CGV/SAMS/NSC real synthetic workbooks exercise correction→quick-master remap→confirm.
- Foreign/expired token, malformed date/time/numeric values, canonical field injection, financial reconciliation, restored source values, distinct allocated-free IDs, no preview writes and durable correction audit tested.
- Blade view compilation, PHP syntax, JS syntax and git diff whitespace checks passed.
- Isolated Chromium UI harness uses actual repository jQuery/DataTables/editor assets, mocked HTTP and Swal/theme adapters: search/sort, edit/cancel with no request, failed save/retry, successful redraw, stable row identity, 12 retained rows, one action header and zero browser errors passed.
- Browser tool could not access localhost (private-address block). No authenticated live import/confirm was attempted; no live reporting database writes were performed. Isolated browser evidence is not production E2E verification.

Rollback copies of pre-edit controller/view/routes: `/Users/mumuraihan/.hermes/cache/scratch/import-preview-backup`. Existing uncommitted user work was preserved. No commit or push.
