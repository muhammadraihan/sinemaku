# Sinemaku AI + OpenWA Dynamic Text-to-SQL Implementation Plan

> **For Hermes:** Implement this plan task-by-task with TDD and review each phase against `docs/ai-dynamic-text-to-sql-blueprint.md`. Do not commit or push unless Mumu explicitly requests it.

**Goal:** Deliver an authorized WhatsApp and backoffice AI reporting assistant that dynamically generates safe read-only SQL against a typed Sinemaku analytics schema, plus scheduled monitoring and executive summaries.

**Architecture:** OpenWA remains an isolated WhatsApp transport. Laravel owns identity, permission, semantic definitions, SQL validation, policy enforcement, read-only execution, provenance, audit, and response delivery. Dynamic queries run only against approved analytics views through a restricted connection.

**Tech Stack:** Existing Laravel/PHP/Blade/SmartAdmin application, MySQL-compatible analytics views, Laravel queue/scheduler, OpenWA REST/webhooks, an approved LLM provider adapter, and a real SQL AST parser compatible with the deployed PHP runtime.

**Authoritative blueprint:** `docs/ai-dynamic-text-to-sql-blueprint.md`

---

## Mandatory constraints

- Preserve existing report preview, mapping, reconciliation, exclusion/restore, and atomic confirm behavior.
- AI is read-only for reporting and master data in the first release.
- Never send/store credentials, passwords, API keys, OTPs, connection strings, or OpenWA session material in prompts, responses, fixtures, or logs.
- Do not commit original distributor files; use synthetic test fixtures.
- Do not let OpenWA connect to the Sinemaku database.
- Do not expose raw production tables as an unrestricted AI schema.
- Distinguish distributor actuals from seatmap/city-performance estimates in every query/result path.
- All user-facing popup/operational feedback must follow the project's SweetAlert2 convention.
- Preserve SmartAdmin visual language and implement the assistant as a standalone backoffice page.

---

## Phase 0 — Decisions and baseline

### Task 0.1: Record deployment decisions

**Objective:** Resolve the open decisions in blueprint section 18 before choosing dependencies.

**Modify:**
- `docs/ai-dynamic-text-to-sql-blueprint.md`

**Record:**
- first LLM provider/model;
- primary restricted views versus analytics replica;
- permitted roles and data scopes;
- SQL engine/version in local and production;
- approved PHP SQL parser;
- retention and cost limits;
- dedicated WhatsApp number/group policy;
- external model data-processing constraints.

**Verification:** No unresolved decision blocks dependency selection or access-control design.

### Task 0.2: Capture current analytics baseline

**Objective:** Lock existing finance/trend results before refactoring formulas into views.

**Read/inspect:**
- `app/Http/Controllers/LaporanController.php`
- relevant report/capacity/cinema migrations and models
- existing finance/trend feature tests

**Create tests:**
- `tests/Integration/AnalyticsBaselineContractTest.php`

**Test cases:**
- audience, gross, tax, net, Total PH;
- ATP and occupancy;
- top cinema/city/province;
- audit issue counts;
- daily trend values;
- actual versus estimate fixtures.

**Run:**
```bash
php artisan test tests/Integration/AnalyticsBaselineContractTest.php
php artisan test
```

**Expected:** Baseline fixtures pass and the full suite remains green before implementation begins.

---

## Phase 1 — Typed analytics and semantic layer

### Task 1.1: Define metric catalogue

**Objective:** Create one authoritative definition for every AI-visible metric.

**Create:**
- `config/analytics.php` or a versioned catalogue following the project's preferred config convention
- `app/Services/Reports/MetricCatalogue.php`
- `tests/Unit/Services/Reports/MetricCatalogueTest.php`

**Metrics:** admissions, gross, tax, net, Total PH, ATP, capacity, occupancy, report count, blockers, reconciliation issues, estimated admissions range.

**TDD verification:** Unknown metric/dimension combinations are rejected; actual/estimate status and display rules are available for every metric.

### Task 1.2: Build `analytics_report_facts`

**Objective:** Expose typed, stable reporting facts without repeated legacy string casts in AI queries.

**Create:**
- versioned migration/view definition according to the database convention selected in Task 0.1
- `database/views/analytics_report_facts.sql` if SQL files are adopted
- `tests/Integration/AnalyticsReportFactsViewTest.php`

**Requirements:**
- typed numeric fields;
- stable IDs and source references;
- provider/date/film/cinema/city/studio/ticket dimensions;
- actual/estimate flags;
- audit issue state;
- formula parity with current finance insight.

**Verification:** Compare aggregates against Task 0.2 baseline fixtures.

### Task 1.3: Add aggregate and quality views

**Objective:** Support common questions without forcing expensive raw-fact aggregation.

**Create definitions/tests for:**
- `analytics_daily_performance`
- `analytics_cinema_performance`
- `analytics_film_performance`
- `analytics_report_quality`
- `analytics_upload_completeness`
- `analytics_city_performance`

**Verification:**
- actuals never silently merge with estimates;
- coverage/confidence follows estimates;
- report-quality rows retain audit/source references;
- indexes support period/provider/film/cinema/city filters.

### Task 1.4: Configure restricted analytics connection

**Objective:** Ensure query execution cannot write or access unauthorized tables.

**Modify:**
- `config/database.php`
- `.env.example` with placeholder names only, never real credentials

**Create:**
- `tests/Integration/AnalyticsReadOnlyConnectionTest.php`

**Verification:**
- approved view `SELECT` succeeds;
- attempted write fails;
- raw sensitive table access fails;
- missing configuration fails closed.

---

## Phase 2 — Controlled dynamic SQL executor

### Task 2.1: Define structured Text-to-SQL contract

**Objective:** Make model output parseable and reject malformed responses.

**Create:**
- `app/Data/AI/GeneratedQuery.php` or equivalent DTO convention
- `app/Contracts/AI/TextToSqlProvider.php`
- `app/Services/AI/TextToSqlResponseParser.php`
- `tests/Unit/Services/AI/TextToSqlResponseParserTest.php`

**Contract fields:** intent, assumptions, clarification flag/question, SQL, bound parameters, requested metrics, requested dimensions.

### Task 2.2: Add provider adapter

**Objective:** Isolate the selected AI provider behind one interface.

**Create/modify:**
- `config/ai.php`
- provider implementation under `app/Services/AI/Providers/`
- `tests/Unit/Services/AI/Providers/*Test.php`

**Requirements:** timeout, retry policy, structured output, secret redaction, usage metadata, mock/fake support in tests.

### Task 2.3: Build schema catalogue and prompt builder

**Objective:** Give the model only approved views, metrics, dimensions, and scope rules.

**Create:**
- `app/Services/AI/SchemaCatalogue.php`
- `app/Services/AI/PromptBuilder.php`
- corresponding unit tests

**Tests:**
- sensitive tables absent;
- only allowed columns included;
- actual/estimate rules present;
- user content cannot override system policy;
- no secret/config value enters the prompt.

### Task 2.4: Implement AST SQL validator

**Objective:** Reject unsafe or out-of-contract SQL structurally.

**Create:**
- `app/Services/AI/SqlAstValidator.php`
- `app/Exceptions/UnsafeAiQueryException.php`
- `tests/Unit/Services/AI/SqlAstValidatorTest.php`

**Must reject:** writes/DDL, multiple statements, non-allowlisted relations, file/system functions, procedures, unsafe cross joins, statement chaining/comments as bypass, unbounded result sets, forbidden time ranges.

**Must accept:** parameterized `SELECT` and reviewed `WITH ... SELECT` queries against approved views.

### Task 2.5: Implement scope/policy enforcement

**Objective:** Apply user authorization server-side rather than trusting generated predicates.

**Create:**
- `app/Services/AI/QueryPolicyService.php`
- `app/Data/AI/QueryScope.php`
- tests covering provider/city/cinema/category and actual/estimate access.

**Verification:** Generated SQL cannot remove or broaden server-owned scope predicates.

### Task 2.6: Implement read-only executor

**Objective:** Execute validated queries with strict resource budgets.

**Create:**
- `app/Services/AI/ReadOnlyQueryExecutor.php`
- `app/Data/AI/QueryResult.php`
- unit/integration tests

**Enforce:** prepared parameters, timeout, row limit, byte limit, date-range limit, concurrency/rate limit, cancellation, sanitized errors.

### Task 2.7: Implement result verification and provenance

**Objective:** Prevent unsupported or misleading answers.

**Create:**
- `app/Services/AI/ResultVerifier.php`
- `app/Services/AI/ProvenanceService.php`
- unit tests

**Verify:** requested columns, period/filter state, actual/estimate classification, empty/incomplete data, audit warnings, source report references, suspicious values.

---

## Phase 3 — Audit, conversations, and backoffice assistant

### Task 3.1: Add audit models and migrations

**Objective:** Persist an inspectable record of every AI question and query attempt.

**Create:**
- models/migrations for `ai_conversations`, `ai_queries`, and later `ai_message_deliveries`
- factories and model tests

**Requirements:** query reference such as `AIQ-*`, sanitized SQL/parameters, views accessed, timings, row count, validator status, model usage, answer, actual/estimate state.

**Security test:** fixture secrets represented as `[REDACTED]` and never persisted verbatim.

### Task 3.2: Build conversation context service

**Objective:** Support scoped follow-up questions without leaking across chats/users.

**Create:**
- `app/Services/AI/ConversationService.php`
- unit tests for expiry, reset, user/chat isolation, filter inheritance, and clarification state.

### Task 3.3: Build AI orchestrator

**Objective:** Connect identity/scope, Text-to-SQL, validation, execution, verification, answering, and audit.

**Create:**
- `app/Services/AI/ReportingAssistant.php`
- `tests/Feature/AIReportingAssistantTest.php`

**Scenarios:** successful dynamic question, clarification, follow-up, unauthorized dimension, unsafe SQL, timeout, empty result, estimate-only response, provider failure.

### Task 3.4: Add standalone SmartAdmin page

**Objective:** Prove and debug the assistant in backoffice before WhatsApp.

**Create/modify:**
- `app/Http/Controllers/AIReportingController.php`
- `resources/views/ai-reporting/index.blade.php`
- `routes/web.php`
- menu/permission seed/config following existing project conventions
- feature/browser tests

**UI:** restrained chat area, active filters, query reference, source/data-quality badges, actual/estimate badge, history, reset context. Use SweetAlert2 for errors/loading/operational feedback where applicable.

### Task 3.5: Add authorized query audit page

**Objective:** Let permitted admins inspect question → SQL → parameters → source → answer.

**Create:** standalone SmartAdmin page, routes, permission checks, pagination/filtering, secret-safe display.

---

## Phase 4 — OpenWA transport

### Task 4.1: Add WhatsApp identity pairing

**Objective:** Link an OpenWA sender identity to an active Sinemaku user.

**Create:**
- `WhatsappIdentity` model/migration/service
- pairing controller/UI
- tests for one-time expiry, replay rejection, disabled users, and group sender validation.

**Constraint:** pairing code/OTP is not stored in database or logs after use.

### Task 4.2: Add OpenWA client and webhook verification

**Objective:** Establish a narrow authenticated boundary.

**Create/modify:**
- `config/services.php`
- `app/Services/Integrations/OpenWaClient.php`
- `app/Http/Middleware/VerifyOpenWaWebhook.php`
- `app/Http/Controllers/OpenWaWebhookController.php`
- API route
- tests with fake HTTP responses

**Verify:** authentication, idempotency, timestamps/replay window, sanitized errors, no direct DB credentials in OpenWA payloads.

### Task 4.3: Add asynchronous inbound/outbound jobs

**Objective:** Prevent OpenWA or model latency from blocking webhook requests.

**Create:**
- `app/Jobs/ProcessAiQuestion.php`
- `app/Jobs/DeliverOpenWaMessage.php`
- delivery audit model/migration
- queue/retry/idempotency tests

**Deployment:** move production from synchronous processing to a supervised queue worker for these queues.

### Task 4.4: Exercise end-to-end WhatsApp flow

**Objective:** Verify user question to delivered answer through OpenWA without production writes.

**Test:** mocked HTTP feature flow first, then authorized staging number/session. Verify duplicate messages, disconnect, retry, timeout, unauthorized identity, follow-up context, and actual/estimate label.

---

## Phase 5 — Monitoring and executive summaries

### Task 5.1: Implement deterministic daily summary builder

**Objective:** Generate all numbers and anomaly inputs without the LLM.

**Create:**
- `app/Services/Reports/DailyExecutiveSummaryService.php`
- typed summary DTO
- unit/integration tests against known fixtures.

### Task 5.2: Implement AI narrative formatter

**Objective:** Convert structured summary into concise narrative without inventing numbers.

**Create:** provider prompt/validator and tests that reject output containing numeric claims absent from the payload.

### Task 5.3: Add subscriptions and scheduler

**Objective:** Deliver scoped summaries on a user-configured schedule.

**Create:**
- subscription model/migration/admin form
- `BuildDailyExecutiveSummary` and `DeliverDailyExecutiveSummary` jobs
- scheduler entry in `app/Console/Kernel.php`
- tests for Asia/Jakarta default, role/scope, disabled subscription, duplicate prevention.

### Task 5.4: Add operational monitoring

**Objective:** Make failures visible.

**Monitor:** OpenWA session health, queue backlog, failed jobs, model failures, query timeout/rejection, delivery failures, daily summary freshness, analytics replica freshness.

---

## Release gates

### Gate A — Analytics parity

- Existing finance/trend fixture totals equal analytics-view totals.
- Actual and estimate sources remain separated.
- Full existing suite passes.

### Gate B — Executor security

- Read-only connection cannot write.
- Every forbidden SQL class is covered by tests.
- Scope bypass tests fail closed.
- Secrets are absent from prompt/audit/response fixtures.
- Timeout, row, byte, and rate limits are demonstrated.

### Gate C — Backoffice pilot

- Representative question catalogue passes.
- User can inspect query reference and provenance.
- Ambiguous questions request clarification.
- Empty/incomplete data is disclosed.

### Gate D — WhatsApp staging

- Dedicated staging number/session only.
- Identity pairing and group sender checks pass.
- Duplicate/retry/disconnect behavior is verified.
- OpenWA has no direct database access.

### Gate E — Production summaries

- Deterministic metrics match approved fixtures.
- Narrative introduces no unsupported values.
- Subscriber permission/scope is enforced.
- Failure monitoring and rollback/disable switches exist.

---

## Final verification

Run focused tests throughout, then:

```bash
php artisan test
php artisan route:list
php artisan schedule:list
php artisan view:cache
php artisan queue:work --once
git diff --check
```

Also verify in staging:

1. new unanticipated natural-language question;
2. follow-up retaining context;
3. unsafe prompt/SQL refusal;
4. unauthorized identity denial;
5. actual and estimate separation;
6. query timeout response;
7. duplicate webhook idempotency;
8. executive summary delivery;
9. no writes to reporting/master tables;
10. no secrets in logs, prompts, responses, or audit pages.

---

## Definition of done

An authorized Sinemaku user can ask a previously unmodeled reporting question in backoffice and WhatsApp, receive a correct traceable answer from dynamically generated read-only SQL, and automated/staging verification proves that the same path cannot access secrets, escape user scope, mutate reports/master data, confuse estimates with actuals, or overload the reporting database.
