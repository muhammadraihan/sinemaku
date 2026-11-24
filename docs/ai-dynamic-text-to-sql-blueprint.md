# Sinemaku Dynamic Text-to-SQL AI Blueprint

**Status:** Architecture approved for implementation planning; no production code is included in this document.

**Created:** 2026-11-09

**Goal:** Make WhatsApp the conversational interface for Sinemaku so authorized users can ask natural-language questions about reports, monitoring, and performance while Sinemaku controls schema access, authorization, SQL validation, execution, provenance, and audit.

---

## 1. Executive decision

Sinemaku will use **dynamic Text-to-SQL with a Sinemaku-controlled executor**.

The AI may generate a new read query for a question that has not been anticipated by a fixed endpoint. The AI must not receive unrestricted production-database access. Sinemaku will:

1. identify the user from the WhatsApp identity;
2. apply Sinemaku roles, permissions, and data scope;
3. expose only an approved analytics schema/catalogue;
4. validate generated SQL structurally;
5. execute it through a read-only connection with resource limits;
6. verify the result and distinguish actual from estimated data;
7. generate an answer with period, filters, source references, and data-quality warnings;
8. save an auditable query record.

OpenWA is a transport and WhatsApp session service. It is not the owner of Sinemaku authorization, business rules, reporting truth, or database credentials.

---

## 2. Product scope

### In scope

- WhatsApp chatbot for authorized Sinemaku users.
- Natural-language questions about approved Sinemaku reporting data.
- Dynamic SQL generation for questions not covered by prebuilt queries.
- Daily monitoring and executive summaries.
- Operational report-quality and import-status questions.
- Conversation context and follow-up questions.
- Query provenance, SQL audit, result metadata, and delivery audit.
- Read-only AI access to analytics views.
- Human-readable explanations of anomalies and reconciliation issues.
- OpenWA integration through REST API/webhooks and queues.

### Out of scope for the first release

- AI directly confirming report imports.
- AI changing `pelaporans`, master cinema, film, studio, capacity, or price records.
- AI guessing missing price, cinema mapping, source-row ownership, show allocation, or financial totals.
- Unrestricted SQL against every production table.
- Sending credentials, session secrets, API keys, passwords, or OTPs to the model or chat.
- Treating seatmap or city-performance estimates as distributor-authoritative actuals.
- Automatic financial decisions based solely on an LLM answer.

---

## 3. Current Sinemaku context

The existing application is Laravel/PHP with SmartAdmin/Blade backoffice, Spatie roles/permissions, report import previews, provider-specific parsers, upload history, finance insight, trend analysis, city-performance estimation, and seatmap monitoring.

Relevant existing areas:

- `app/Models/Pelaporan.php`
- `app/Models/ReportUploadHistory.php`
- `app/Http/Controllers/LaporanController.php`
- `app/Http/Controllers/PelaporanController.php`
- `routes/web.php`
- `database/migrations/*pelaporan*`
- `database/migrations/*report_upload_histories*`
- `database/migrations/*city_performance*`
- `database/migrations/*seatmap_monitoring*`
- `config/queue.php`
- `app/Console/Kernel.php`

Current analytics logic already calculates admissions, gross, net, Total PH, ATP, occupancy, leaderboard values, trends, and audit issues. The AI layer must reuse and centralize these business definitions rather than reimplementing them inconsistently in prompts.

Important data-quality constraint: several legacy report values are stored as strings and converted with SQL `CAST`/`REPLACE`. The analytics layer must normalize these values before exposing them to Text-to-SQL.

---

## 4. Target architecture

```text
WhatsApp user
    |
    v
OpenWA service (separate deployment)
    | webhook / REST, session-scoped API key
    v
Sinemaku WhatsApp integration boundary
    |
    +-- inbound event verification
    +-- identity and chat allowlist
    +-- inbound queue
    +-- outbound queue and retry
    v
Sinemaku AI Orchestrator
    |
    +-- conversation context
    +-- role/scope enforcement
    +-- semantic metric catalogue
    +-- schema catalogue
    +-- prompt builder and redaction
    +-- Text-to-SQL provider adapter
    +-- SQL AST validator
    +-- policy injection / scope enforcement
    +-- read-only query executor
    +-- result verifier and provenance builder
    +-- answer formatter
    v
Analytics database or restricted analytics connection
    |
    +-- analytics_report_facts
    +-- analytics_daily_performance
    +-- analytics_cinema_performance
    +-- analytics_film_performance
    +-- analytics_city_performance
    +-- analytics_report_quality
    +-- analytics_upload_completeness
    +-- analytics_metric_catalogue
    v
Sinemaku audit and response delivery
```

### Deployment boundary

OpenWA should run as a separate service/container. Do not install its Node.js runtime, WhatsApp sessions, Chromium state, or Baileys state inside the Laravel application process.

Sinemaku communicates with OpenWA only through authenticated HTTP/webhook boundaries. OpenWA must not connect directly to the Sinemaku database.

### Database boundary

Preferred production architecture:

```text
Sinemaku production database
        |
        | approved replication/ETL
        v
Analytics database / read replica
        |
        | read-only analytics credential
        v
AI query executor
```

MVP fallback: use a separate read-only database user against the same database, restricted to approved views. Upgrade to a read replica or analytics database before high-volume usage.

---

## 5. User experience

### 5.1 Natural-language report question

```text
User: Berapa admissions film X dari tanggal 1 sampai 15 Oktober, dan cinema mana kontribusinya paling besar?

Bot:
Periode 1–15 Oktober 2026:
- Admissions: ...
- Gross: ...

Cinema dengan kontribusi terbesar:
1. ...
2. ...
3. ...

Sumber: laporan aktual yang sudah approved.
Catatan kualitas: ...
Query reference: AIQ-...
```

### 5.2 Follow-up question

```text
User: Bandingkan dengan 15 hari sebelumnya.

Bot:
Mempertahankan film dan filter cinema sebelumnya, lalu membandingkan dengan periode 16–30 September 2026.
```

Conversation context must be scoped to the WhatsApp chat and Sinemaku user, and must expire after a configurable period (initially 30–60 minutes).

### 5.3 Daily executive summary

A scheduled job computes deterministic metrics first. The AI receives the structured result and writes the narrative. The AI must not independently invent the underlying numbers.

Summary sections:

- reporting completeness;
- admissions;
- gross, net, and Total PH;
- ATP and occupancy;
- top and bottom cinemas/cities/films;
- period-over-period changes;
- anomaly list;
- unresolved report/import issues;
- actual-versus-estimate disclaimer.

### 5.4 Operational questions

Examples:

- `Laporan apa yang belum masuk hari ini?`
- `Apa blocker import terakhir?`
- `Provider mana yang paling sering punya reconciliation issue bulan ini?`
- `Cinema mana yang occupancy-nya di atas kapasitas?`
- `Jelaskan mengapa total KCM Pamekasan masih diblokir.`

These questions may use report-upload and audit views, but must not expose credentials, raw session data, or unrelated user records.

---

## 6. Analytics semantic layer

Do not expose raw legacy tables directly as the primary AI schema. Create stable views or materialized analytics tables with typed fields and documented definitions.

### 6.1 Proposed views

#### `analytics_report_facts`

Minimum fields:

- `report_id`
- `upload_history_id`
- `provider`
- `report_date`
- `film_id`
- `film_name`
- `cinema_id`
- `cinema_name`
- `city`
- `province`
- `category`
- `studio_id`
- `studio_name`
- `show_number`
- `show_time`
- `ticket_type`
- `audience`
- `capacity`
- `ticket_price`
- `gross`
- `tax`
- `net`
- `total_ph`
- `atp`
- `occupancy_rate`
- `is_actual`
- `is_estimate`
- `has_audit_issue`
- `source_provider`
- `source_row_reference`

#### `analytics_daily_performance`

One row per reporting date and approved scope:

- `report_date`
- `provider`
- `film_id`
- `cinema_id`
- `city`
- `audience`
- `gross`
- `tax`
- `net`
- `total_ph`
- `capacity`
- `atp`
- `occupancy_rate`
- `report_count`
- `audit_issue_count`
- `is_actual`

#### `analytics_report_quality`

- `upload_history_id`
- `provider`
- `report_date`
- `status`
- `preview_rows`
- `imported_rows`
- `blocker_count`
- `missing_mapping_count`
- `reconciliation_issue_count`
- `completed_at`
- `uploaded_by`

#### `analytics_upload_completeness`

- expected provider/report period;
- received status;
- latest upload;
- missing report reason/status;
- freshness timestamp.

#### `analytics_city_performance`

Must retain:

- source;
- collection run;
- coverage ratio;
- confidence;
- estimate lower/mid/upper;
- `is_estimate = 1`.

### 6.2 Metric catalogue

Create a machine-readable and human-readable metric catalogue. Each metric must define:

- name;
- business meaning;
- SQL expression/view column;
- allowed dimensions;
- actual/estimate status;
- rounding/display rule;
- source tables/views;
- data-quality caveats;
- example questions.

Initial metrics:

- admissions;
- gross;
- tax;
- net;
- Total PH;
- ATP;
- capacity;
- occupancy;
- report count;
- blocker count;
- reconciliation issue count;
- estimated admissions range.

The controller's existing finance insight formulas must be reconciled with this catalogue before production AI release.

---

## 7. Dynamic Text-to-SQL pipeline

### Step 1: Receive and normalize message

- Verify OpenWA webhook signature or authenticated delivery.
- Normalize message text and metadata.
- Reject unsupported message types or oversized payloads.
- Assign an idempotency key.
- Store minimal inbound event metadata.

### Step 2: Resolve identity

- Map WhatsApp identity to a Sinemaku user.
- Require active user status.
- Validate group allowlist if the message comes from a group.
- Apply role and data scope.
- Unpaired or unauthorized numbers receive a generic response without internal details.

### Step 3: Load context

Load only the current chat's non-expired context:

- prior question;
- filters;
- selected film/cinema/provider;
- active period;
- last query reference;
- unresolved clarification.

Never place credentials, session tokens, or raw sensitive configuration into context.

### Step 4: Build AI request

Provide the model with:

- allowed schema catalogue;
- metric definitions;
- user scope;
- data-status rules;
- current question;
- safe examples;
- instruction to ask clarification when period, film, provider, or actual/estimate status is ambiguous.

### Step 5: Generate structured result

Prefer a structured model response:

```json
{
  "intent": "aggregate_report_query",
  "assumptions": [],
  "clarification_needed": false,
  "sql": "SELECT ...",
  "parameters": {
    "start_date": "2026-10-01",
    "end_date": "2026-10-15"
  },
  "requested_dimensions": ["cinema_name"],
  "requested_metrics": ["audience", "gross"]
}
```

The model output is untrusted input. Do not execute it directly.

### Step 6: Validate SQL structurally

Use a real SQL parser/AST validator, not regex-only checks.

Reject:

- multiple statements;
- non-`SELECT` statements;
- `INSERT`, `UPDATE`, `DELETE`, `DROP`, `ALTER`, `TRUNCATE`, `GRANT`, `COPY`, or procedure execution;
- access to non-allowlisted tables/views;
- file-system functions;
- dynamic SQL execution;
- unbounded result sets;
- unsafe cross joins;
- comments/statement chaining used to bypass validation;
- queries outside the allowed time range without explicit permission;
- hidden access to raw credentials, sessions, roles, or secrets.

### Step 7: Apply server-side policy

Permission filters must be added by Sinemaku, not trusted to the model. Examples:

- permitted providers;
- permitted cities/cinemas;
- permitted report categories;
- actual versus estimate access;
- maximum historical range;
- row and aggregation limits.

The executor must verify that policy predicates remain present after rewriting/compiling the query.

### Step 8: Execute safely

- Use a separate read-only connection.
- Use prepared parameters.
- Set statement timeout.
- Set maximum rows and response bytes.
- Use a query cost/time budget.
- Use queue workers for expensive queries.
- Cancel timed-out queries.
- Apply concurrency/rate limits per user and chat.
- Never run AI-generated queries inside the import confirmation transaction.

### Step 9: Verify result

Check:

- returned columns match the requested metrics;
- date/provider/cinema filters are present;
- result is not mixing actual and estimate unintentionally;
- empty result is reported as empty;
- audit issues and incomplete coverage are surfaced;
- values are formatted from typed numeric data;
- source references are attached;
- suspicious results are marked for review rather than silently corrected.

### Step 10: Generate answer

The final answer should contain:

- direct answer;
- period and filters;
- actual/estimate status;
- source report count or query reference;
- relevant data-quality warnings;
- concise explanation;
- optional follow-up suggestions.

### Step 11: Deliver and audit

- Enqueue response through OpenWA.
- Retry safely with idempotency.
- Record delivery status.
- Save query, parameters, validation result, execution metadata, answer, and model metadata.

---

## 8. Database safety model

### Preferred permissions

The AI database user may read only approved analytics views. It must not read:

- users;
- passwords or reset tokens;
- sessions;
- roles and permissions unless represented by a deliberately safe scope view;
- OpenWA session/auth storage;
- API keys;
- seatmap source account credentials;
- encrypted integration credentials;
- arbitrary raw upload storage;
- application configuration.

### Direct database questions

The system may support new questions dynamically, but “AI can query the database” means “AI can query the approved analytics contract,” not “AI can inspect every production table.”

If a question cannot be answered from the catalogue:

1. identify the missing field or view;
2. return a clear limitation or ask for clarification;
3. record a proposed analytics requirement;
4. add a reviewed view/metric later;
5. do not bypass the allowlist for one question.

---

## 9. OpenWA integration contract

### Inbound webhook

OpenWA sends to Sinemaku:

```json
{
  "event_id": "opaque-id",
  "session_id": "opaque-session-id",
  "chat_id": "opaque-chat-id",
  "sender_id": "opaque-sender-id",
  "message_id": "opaque-message-id",
  "message_type": "text",
  "text": "Berapa admissions kemarin?",
  "received_at": "2026-11-09T00:00:00Z"
}
```

Do not persist raw OpenWA session secrets in this payload or in logs.

### Outbound message

Sinemaku calls OpenWA with:

- scoped session identifier;
- destination chat identifier;
- text/media payload;
- idempotency key;
- expiry;
- audit reference.

The OpenWA API key must be stored only in deployment secret management and represented as `[REDACTED]` in documentation/log output.

### Failure handling

- webhook duplicate: ignore after idempotency check;
- OpenWA unavailable: queue and retry with backoff;
- WhatsApp session disconnected: mark delivery pending and notify admin through another channel;
- AI provider timeout: send a concise retry message, not an internal stack trace;
- query timeout: explain that the query was too broad and ask for a narrower period/filter;
- unauthorized user: do not reveal whether a target report exists.

---

## 10. Daily monitoring and executive summary design

### Deterministic collection job

Create a scheduled job that calculates a typed summary for each configured audience/scope. It must use SQL/query services, not an LLM.

Inputs:

- reporting date and comparison period;
- providers;
- user/subscriber scope;
- actual/estimate policy;
- completeness expectations.

Outputs:

- structured summary JSON;
- anomaly list;
- unresolved blockers;
- source report references;
- data-quality state.

### AI narrative job

The AI receives only the structured summary JSON and approved metric definitions. It produces a narrative with no new numeric facts beyond the payload.

### Subscriber model

Future table/configuration:

- `user_id`;
- WhatsApp identity;
- summary type;
- schedule/timezone;
- provider/city/cinema scope;
- enabled flag;
- last delivery status.

Timezone default: `Asia/Jakarta`, unless explicitly configured otherwise.

---

## 11. Audit and observability

Proposed tables:

### `ai_conversations`

- UUID;
- user ID;
- WhatsApp chat reference hash/opaque ID;
- status;
- context expiry;
- created/updated timestamps.

### `ai_queries`

- UUID/query reference;
- conversation ID;
- user ID;
- question;
- normalized intent;
- interpreted filters;
- generated SQL (redacted where necessary);
- bound parameter metadata;
- validator status/reason;
- views accessed;
- execution duration;
- row count;
- result status;
- actual/estimate classification;
- model/provider identifier;
- token/cost metadata;
- answer;
- created timestamp.

### `ai_message_deliveries`

- query/conversation reference;
- OpenWA event/message ID;
- destination hash/opaque ID;
- delivery status;
- attempt count;
- provider error code/message sanitized;
- sent/delivered timestamp.

Never log passwords, API keys, OTPs, connection strings, WhatsApp session material, or raw credential fields.

---

## 12. Security requirements

- OpenWA runs outside the Laravel process and has no direct database connection.
- All webhook calls are authenticated and idempotent.
- Every WhatsApp identity is explicitly paired to a Sinemaku user.
- Every query runs under the user's Sinemaku scope.
- AI database access is read-only and view-restricted.
- SQL is parsed and validated structurally.
- Server-side policy filters cannot be removed by the model.
- Query timeout, row limit, byte limit, and rate limit are enforced.
- AI responses never contain secrets or raw internal errors.
- Actual and estimated data are clearly separated.
- Import, mapping, exclusion, restore, and confirm remain human-approved Sinemaku workflows.
- Prompt injection in report text, film names, cinema names, or WhatsApp messages is treated as untrusted input.
- External AI providers receive only minimized, authorized data; private/self-hosted inference remains an option for sensitive deployments.

---

## 13. Implementation phases

### Phase 0 — Contract and proof

- Document metric definitions and data-status rules.
- Identify all current finance/trend formulas.
- Create a safe analytics schema catalogue.
- Build a local prototype that answers a fixed test set with generated SQL but no WhatsApp.
- Define expected answers for representative questions.

### Phase 1 — Analytics layer

- Normalize typed report facts.
- Create analytics views/materialized tables.
- Add indexes for date/provider/film/cinema/city dimensions.
- Add report-quality and completeness views.
- Reconcile results against existing finance insight/trend endpoints.

### Phase 2 — Controlled SQL executor

- Implement structured Text-to-SQL response contract.
- Add SQL parser/AST validation.
- Add allowlisted schema catalogue.
- Add server-side permission predicates.
- Add read-only connection.
- Add query timeout, row/byte limits, and rate limits.
- Add query audit tables and services.

### Phase 3 — Backoffice AI assistant

- Create standalone SmartAdmin/Blade page.
- Add question history and query reference.
- Add SQL/result audit view for authorized admins.
- Test clarification, empty result, timeout, permission denial, actual/estimate separation, and follow-up context.

### Phase 4 — OpenWA chatbot

- Deploy OpenWA separately.
- Add WhatsApp identity pairing.
- Add webhook endpoint and idempotency.
- Add inbound/outbound queue jobs.
- Add chat/session scoping and delivery retry.
- Reuse the same AI orchestrator as the backoffice assistant.

### Phase 5 — Monitoring and executive summaries

- Add scheduled deterministic summary jobs.
- Add anomaly rules.
- Add AI narrative generation.
- Add subscriber scopes and Asia/Jakarta schedule defaults.
- Add delivery and failure monitoring.

### Phase 6 — Controlled expansion

- Add report-upload status questions.
- Add quarantined document intake through WhatsApp.
- Add preview-ready notifications.
- Keep confirm and financial/master-data mutations inside authenticated Sinemaku UI.

---

## 14. Proposed file/module map

Names are proposed; inspect existing conventions before implementation.

### Configuration

- `config/ai.php`
- `config/services.php` for OpenWA and AI provider endpoints, with secrets supplied only by environment/secret manager
- `config/database.php` for a separate analytics/read-only connection
- `config/queue.php` for asynchronous AI/OpenWA jobs

### Domain/services

- `app/Services/AI/ConversationService.php`
- `app/Services/AI/IntentResolver.php`
- `app/Services/AI/PromptBuilder.php`
- `app/Services/AI/SchemaCatalogue.php`
- `app/Services/AI/MetricCatalogue.php`
- `app/Services/AI/TextToSqlService.php`
- `app/Services/AI/SqlAstValidator.php`
- `app/Services/AI/QueryPolicyService.php`
- `app/Services/AI/ReadOnlyQueryExecutor.php`
- `app/Services/AI/ResultVerifier.php`
- `app/Services/AI/AnswerFormatter.php`
- `app/Services/AI/ProvenanceService.php`
- `app/Services/Integrations/OpenWaClient.php`
- `app/Services/Integrations/OpenWaWebhookService.php`
- `app/Services/Reports/AnalyticsFactService.php`
- `app/Services/Reports/DailyExecutiveSummaryService.php`

### Jobs

- `app/Jobs/ProcessAiQuestion.php`
- `app/Jobs/DeliverOpenWaMessage.php`
- `app/Jobs/BuildDailyExecutiveSummary.php`
- `app/Jobs/DeliverDailyExecutiveSummary.php`

### HTTP/API

- `app/Http/Controllers/AIReportingController.php`
- `app/Http/Controllers/OpenWaWebhookController.php`
- `app/Http/Middleware/VerifyOpenWaWebhook.php`
- `routes/ai.php` or explicit additions to `routes/web.php`/`routes/api.php` following existing project conventions

### Models/migrations

- `app/Models/AiConversation.php`
- `app/Models/AiQuery.php`
- `app/Models/AiMessageDelivery.php`
- `app/Models/WhatsappIdentity.php`
- `app/Models/ExecutiveSummarySubscription.php`
- corresponding migrations under `database/migrations/`

### Analytics SQL/views

- `database/views/analytics_report_facts.sql`
- `database/views/analytics_daily_performance.sql`
- `database/views/analytics_report_quality.sql`
- `database/views/analytics_upload_completeness.sql`
- `database/views/analytics_city_performance.sql`
- `database/seeders/AnalyticsMetricCatalogueSeeder.php` or a versioned catalogue file

### Tests

- `tests/Unit/Services/AI/SqlAstValidatorTest.php`
- `tests/Unit/Services/AI/QueryPolicyServiceTest.php`
- `tests/Unit/Services/AI/ResultVerifierTest.php`
- `tests/Feature/AIReportingAssistantTest.php`
- `tests/Feature/OpenWaWebhookTest.php`
- `tests/Feature/OpenWaIdentityPairingTest.php`
- `tests/Feature/DailyExecutiveSummaryTest.php`
- `tests/Integration/AnalyticsQueryContractTest.php`

---

## 15. Acceptance criteria

### Dynamic query

- [ ] A permitted user can ask a new natural-language report question without a prebuilt endpoint.
- [ ] The AI produces structured intent, parameters, and SQL.
- [ ] Invalid/non-SELECT SQL is rejected before execution.
- [ ] Non-allowlisted views are rejected.
- [ ] Server-side scope filters cannot be removed by AI output.
- [ ] Query timeout and row/byte limits are enforced.
- [ ] The answer includes period, filters, actual/estimate status, and query reference.
- [ ] Empty, incomplete, or suspicious results are disclosed.

### Security

- [ ] AI cannot access credentials, sessions, tokens, or unrestricted user tables.
- [ ] OpenWA cannot connect directly to the Sinemaku database.
- [ ] Webhook duplicates do not create duplicate processing.
- [ ] Unauthorized WhatsApp identities receive no internal data.
- [ ] Secrets are absent from prompts, responses, and logs.
- [ ] Prompt injection in untrusted data does not alter executor policy.

### Monitoring

- [ ] Daily summary numbers come from deterministic structured queries.
- [ ] AI narrative cannot introduce numbers absent from the summary payload.
- [ ] Summary subscribers are scoped by Sinemaku permission.
- [ ] Failed delivery and disconnected session states are observable.

### Reporting integrity

- [ ] AI never directly writes reporting or master-data records.
- [ ] Existing preview, mapping, reconciliation, exclusion, restore, and confirm rules remain authoritative.
- [ ] Actual distributor reports and estimates are clearly separated.
- [ ] Every answer can be traced to query, parameters, views, and source report references.

---

## 16. Test question catalogue

Use synthetic fixtures and committed test data; never commit original distributor files or secrets.

### Basic aggregates

1. `Berapa total admissions pada 2026-10-09?`
2. `Berapa gross XXI dari 1 sampai 15 Oktober 2026?`
3. `Cinema mana yang memiliki admissions tertinggi minggu ini?`
4. `Bandingkan CGV dan XXI di Jakarta bulan lalu.`

### Follow-up context

5. `Film mana yang paling tinggi minggu ini?`
6. `Kalau hanya Jawa Barat?`
7. `Bandingkan dengan minggu sebelumnya.`

### Data quality

8. `Laporan apa yang masih punya blocker?`
9. `Kenapa KCM Pamekasan belum bisa confirm?`
10. `Ada admissions yang melebihi kapasitas?`
11. `Provider mana yang paling sering mengalami reconciliation issue?`

### Security/abuse

12. Ask for passwords, API keys, sessions, or user data.
13. Ask the model to ignore policy and read raw tables.
14. Inject SQL through a film/cinema name.
15. Ask for an unbounded all-history export.
16. Send duplicate OpenWA webhook events.
17. Ask for a write operation such as deleting a report.

Expected behavior for all security cases is refusal or safe limitation with no sensitive disclosure.

---

## 17. Risks and mitigations

| Risk | Mitigation |
|---|---|
| Wrong SQL or wrong metric interpretation | Metric catalogue, typed views, result verification, source references |
| Data leakage across users | Identity mapping, server-side scope predicates, view restrictions |
| Expensive query affects production | Read replica/analytics DB, timeout, cost limits, queue, concurrency limits |
| Prompt injection from report text | Treat all database text as untrusted; never let data alter system policy |
| AI invents explanations | Answer only from structured result and provenance payload |
| Actual/estimate confusion | Explicit `is_actual`/`is_estimate`, required labels, result verifier |
| WhatsApp account/session failure | Separate service, health monitoring, retry, dedicated number, fallback web UI |
| AI provider data exposure | Minimize payload, redact sensitive fields, provider review, private model option |
| Query audit contains secrets | Redaction policy and tests for secret-free logs |
| Business formula drift | Central metric definitions reconciled with existing finance/trend calculations |

---

## 18. Open decisions before implementation

1. Which AI provider or self-hosted model will be used for the first pilot?
2. Is an analytics read replica available in the target deployment, or should MVP use restricted views on the primary database?
3. Which user roles may use WhatsApp AI, and may each role see all providers/cities/cinemas?
4. What is the initial report-history retention period for AI queries and conversation context?
5. Which WhatsApp number is dedicated to Sinemaku, and what is the fallback channel if its session disconnects?
6. Will summaries be sent only to individual users, or also to approved groups?
7. Which external AI data-processing restrictions apply to distributor and commercial data?
8. What SQL dialect/version is guaranteed in development and Hostinger production?
9. Which SQL parser/AST library is approved for the PHP runtime?
10. What daily query budget and model cost limit should apply per user/organization?

Do not begin production implementation until these decisions are recorded in the project task or deployment configuration.

---

## 19. Verification commands for implementation

The exact commands must be adapted to the project's runtime, but the implementation must eventually verify:

```bash
php artisan test
php artisan route:list
php -l app/Services/AI/SqlAstValidator.php
php artisan config:clear
php artisan view:cache
php artisan queue:work --once
php artisan schedule:list
git diff --check
```

Additional required verification:

- run SQL validator tests against allowed and forbidden statements;
- prove the AI read-only connection cannot write;
- prove unauthorized identities cannot query;
- prove policy predicates survive generated SQL;
- prove query timeout/cancellation;
- prove duplicate webhook idempotency;
- prove daily summary numbers equal deterministic query fixtures;
- prove actual/estimate labels in WhatsApp and backoffice;
- run the full existing Sinemaku suite to ensure import/report integrity is unchanged.

---

## 20. Definition of done for the first release

The first release is complete only when an authorized user can ask a new report question through the Sinemaku backoffice and WhatsApp, receive a correct and traceable answer from a dynamically generated read query, and the system can demonstrate through automated tests that the same path cannot access secrets, bypass permission, mutate reporting data, or overload the production database.
