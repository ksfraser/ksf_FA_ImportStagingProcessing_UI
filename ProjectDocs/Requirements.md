# Requirements Specification — ksf_FA_ImportStagingProcessing_UI

> **Module Code**: ISU
> **Version**: 1.0.0
> **Status**: Draft

---

## 1. Business Context

FrontAccounting (FA) needs to import financial transactions from multiple
sources — Square POS, WooCommerce, and future sources — into a unified
staging pipeline before posting to the ledger. The module provides a
**source-agnostic staging backend** with CSV upload, transaction staging,
customer/counterparty matching, and processing into FA sales invoices,
payments, and bank transfers.

Importing modules (ksf_FA_Square, ksf_FA_Woocommerce) own their
source-specific configuration (GL accounts, bank accounts, API tokens)
and delegate staging/processing to this module via the
`hook_invoke` interface.

---

## 2. Functional Requirements

### FR-ISU-001: CSV Import

**Description**: Accept CSV file uploads from Square Dashboard exports and stage the data.

| ID | Requirement | Priority |
|----|------------|----------|
| FR-ISU-001-001 | Accept CSV file upload via browser form | Must |
| FR-ISU-001-002 | Parse Square Summary CSV (transactions) using `ro_square_trans_csv_model` | Must |
| FR-ISU-001-003 | Parse Square Items CSV (line items) using `ro_square_items_csv_model` | Must |
| FR-ISU-001-004 | Validate CSV structure (expected columns present) | Must |
| FR-ISU-001-005 | Display parsed data for review before staging | Should |
| FR-ISU-001-006 | Insert validated records into staging tables on confirmation | Must |
| FR-ISU-001-007 | Support re-upload detection (transaction_id check) | Should |

**Source Files**: `import_statements.php`, `class.import_statements_gui.php`, `class.ro_square_trans_csv_model.php`, `class.ro_square_items_csv_model.php`

---

### FR-ISU-002: Square API Import

**Description**: Connect to the Square API and pull transaction data programmatically.

| ID | Requirement | Priority |
|----|------------|----------|
| FR-ISU-002-001 | Authenticate with Square API using configured credentials | Must |
| FR-ISU-002-002 | Pull Orders from Square API | Must |
| FR-ISU-002-003 | Pull Payments from Square API | Must |
| FR-ISU-002-004 | Pull Refunds from Square API | Must |
| FR-ISU-002-005 | Support date range filtering for API pulls | Must |
| FR-ISU-002-006 | Stage API data into same staging tables as CSV import | Must |
| FR-ISU-002-007 | Handle API rate limits and pagination | Should |

**Source Files**: `class.ksf_import_square.php` (controller tabs for API import)

---

### FR-ISU-003: Transaction Staging

**Description**: Insert imported transaction data into the staging table with status tracking.

| ID | Requirement | Priority |
|----|------------|----------|
| FR-ISU-003-001 | Store all Square transaction fields in `0_ksf_import_square_transactions` | Must |
| FR-ISU-003-002 | Track transaction status (unsettled/settled) | Must |
| FR-ISU-003-003 | Support date range queries for staged transactions | Must |
| FR-ISU-003-004 | Support filtering by settlement status | Must |
| FR-ISU-003-005 | Store payment-specific data (card, cash, fees, net totals) | Must |
| FR-ISU-003-006 | Store customer information from Square (name, reference_id, Customer_id) | Must |
| FR-ISU-003-007 | Store deposit information (deposit_id, deposit_date, deposit_details URL) | Must |
| FR-ISU-003-008 | Auto-set `last_updated` timestamp on insert/update | Must |
| FR-ISU-003-009 | Filter numeric fields (sanitize float values on set) | Must |

**Source Files**: `class.ksf_import_square_transactions.php`

---

### FR-ISU-004: Item Staging

**Description**: Stage line items linked to parent transactions.

| ID | Requirement | Priority |
|----|------------|----------|
| FR-ISU-004-001 | Store line item fields in `0_ksf_import_square_items` | Must |
| FR-ISU-004-002 | Link items to parent transaction via `transaction_id` | Must |
| FR-ISU-004-003 | Store item fields: Category, Item, Price_Point_Name, stock_id, quantity, modifiers | Must |
| FR-ISU-004-004 | Store item financial data: gross_sales, discounts, net_sales, tax | Must |
| FR-ISU-004-005 | Support retrieval of all items for a given transaction_id | Must |
| FR-ISU-004-006 | Support stock_id mapping for FA inventory items | Must |

**Source Files**: `class.ksf_import_square_items.php`

---

### FR-ISU-005: Process Staged Transactions

**Description**: Process staged transactions into FA ledger entries.

| ID | Requirement | Priority |
|----|------------|----------|
| FR-ISU-005-001 | Create FA Sales Invoices from staged Square transactions | Must |
| FR-ISU-005-002 | Create FA Customer Payments for card/cash received | Must |
| FR-ISU-005-003 | Create FA Bank Transfers from Square deposits to bank account | Must |
| FR-ISU-005-004 | Create FA Supplier Payments for vendor charges | Should |
| FR-ISU-005-005 | Allocate payments to matching invoices | Must |
| FR-ISU-005-006 | Record payment match in `0_ksf_import_square_payments` | Must |
| FR-ISU-005-007 | Record transaction-to-invoice match in `0_ksf_import_square_sales` | Must |
| FR-ISU-005-008 | Support process queue UI with status filtering (unsettled/settled/all) | Must |
| FR-ISU-005-009 | Support date range filtering on process queue | Must |
| FR-ISU-005-010 | Support disassociation of processed transactions (unset) | Should |

**Source Files**: `process_statements.php`, `class.ksf_import_square.php` (process_transactions_form, process_payments_form, process_deposits_form)

---

### FR-ISU-006: Customer/Counterparty Matching

**Description**: Match imported transactions to existing FA customers and vendors.

| ID | Requirement | Priority |
|----|------------|----------|
| FR-ISU-006-001 | Auto-match customers by name from `0_ksf_import_external_customers` | Must |
| FR-ISU-006-002 | Create new FA customers from Square customer data | Should |
| FR-ISU-006-003 | Match vendors by bank account short name | Must |
| FR-ISU-006-004 | Support manual customer/vendor selection on process screen | Must |
| FR-ISU-006-005 | Use PAN suffix as customer branch identifier when configured | Should |
| FR-ISU-006-006 | Link matched customer to debtor_no for invoice allocation | Must |

**Source Files**: `class.ksf_import_external_customers_model.php`, `class.ksf_import_square_customers_model.php`

---

### FR-ISU-007: UI Pages

**Description**: Provide user interface pages for import, review, and processing.

| ID | Priority | Description |
|----|----------|-------------|
| FR-ISU-007-001 | Must | Statement Import page (`import_statements.php`) — CSV upload form |
| FR-ISU-007-002 | Must | Process Staging page (`process_statements.php`) — queue with filters |
| FR-ISU-007-003 | Must | Main module page (`ksf_import_square.php`) — tabbed interface |
| FR-ISU-007-004 | Must | Transaction detail view — items, customer, matching invoices |
| FR-ISU-007-005 | Should | Deposit/Payment creation forms |
| FR-ISU-007-006 | Should | Match transaction to existing FA entry |
| FR-ISU-007-007 | Should | Edit item SKU mapping |

**Source Files**: `import_statements.php`, `process_statements.php`, `ksf_import_square.php`, `class.import_statements_gui.php`

---

### FR-ISU-008: Duplicate Detection

**Description**: Prevent re-import of already-imported transactions.

| ID | Requirement | Priority |
|----|------------|----------|
| FR-ISU-008-001 | Check `transaction_id` existence before insert into transactions table | Must |
| FR-ISU-008-002 | Check `payment_id` existence before insert into payments table | Must |
| FR-ISU-008-003 | Check `deposit_id` existence before insert into deposits | Must |
| FR-ISU-008-004 | Use counterparty model (`bi_counterparty_model`) for deduplication | Should |
| FR-ISU-008-005 | Use transaction title model (`bi_transationTitle_model`) for deduplication | Should |
| FR-ISU-008-006 | Skip duplicate records with user notification on re-import | Must |

**Source Files**: `class.bi_counterparty_model.php`, `class.bi_transactionTitle_model.php`, `class.import_statements_controller.php`

---

### FR-ISU-009: Generic Staging Support (Multi-Source)

**Description**: The module SHALL serve as a generic staging backend for
multiple importing modules (Square, WooCommerce, future sources) via the
hook_invoke interface.

| ID | Requirement | Priority |
|----|------------|----------|
| FR-ISU-009-001 | Accept staging requests via `hook_invoke('ksf_FA_ImportStagingProcessing_UI', 'respondToCapabilityRequest', ...)` | Must |
| FR-ISU-009-002 | Support `staging:stageOrder` request from external modules | Must |
| FR-ISU-009-003 | Support `staging:stageCustomer` request from external modules | Must |
| FR-ISU-009-004 | Accept source identifier (e.g., 'square', 'woocommerce') in staging requests | Must |
| FR-ISU-009-005 | Delegate to ISU staging tables regardless of source module | Must |
| FR-ISU-009-006 | Maintain backward compatibility with direct CSV import | Must |
| FR-ISU-009-007 | Provide fallback local staging when ISU hook unavailable | Should |

**Source Files**: `hooks.php` (hook_invoke_all), `class.ksf_import_square.php`

---

### FR-ISU-010: Config Delegation (Source-Specific Config)

**Description**: Importing modules provide source-specific configuration
values (GL accounts, bank accounts, payment methods) via the
`integration_module` delegation key. ISU does NOT own source-specific
config fields.

| ID | Requirement | Priority |
|----|------------|----------|
| FR-ISU-010-001 | ISU config table SHALL NOT contain source-specific fields (GL accounts, bank accounts, API tokens) | Must |
| FR-ISU-010-002 | Importing modules SHALL register as the `integration_module` for their config rows | Must |
| FR-ISU-010-003 | ISU processing methods SHALL accept a `$sourceConfig` array with GL/bank/payment values | Must |
| FR-ISU-010-004 | ISU SHALL fall back to generic defaults when source-specific config is not provided | Should |
| FR-ISU-010-005 | ISU config SHALL retain only source-agnostic fields (matching window, defaults, rounding) | Must |

**Source Files**: `class.ksf_import_square.php` (config_values), `hooks.php`

---

### FR-ISU-011: Source-Agnostic Transaction Processing

**Description**: Process staged transactions from any source (Square, Woo,
etc.) into FA ledger entries, using source-specific config provided by
the calling module.

| ID | Requirement | Priority |
|----|------------|----------|
| FR-ISU-011-001 | Create FA Sales Invoices from staged transactions regardless of source | Must |
| FR-ISU-011-002 | Use GL account from `$sourceConfig['gl_account']` instead of hardcoded config | Must |
| FR-ISU-011-003 | Use bank account from `$sourceConfig['bank_account']` for deposits | Must |
| FR-ISU-011-004 | Support configurable payment methods per source (`$sourceConfig['pay_type']`) | Should |
| FR-ISU-011-005 | Record source identifier on all FA transactions for traceability | Must |

**Source Files**: `class.ksf_import_square.php` (process_transactions_form, add_deposit_form, add_payment_form)

---

### FR-ISU-012: GL Mismatch Detection

**Description**: During the review/match phase, detect and flag GL account
mismatches between staged transactions and existing FA transactions.

| ID | Requirement | Priority |
|----|------------|----------|
| FR-ISU-012-001 | When a staged transaction is matched to an existing FA transaction, compare GL accounts | Must |
| FR-ISU-012-002 | If the staged GL (from source config) differs from the FA GL (from `gl_trans`), flag as mismatch | Must |
| FR-ISU-012-003 | Display a visual warning (icon/text) on mismatched match rows in the review UI | Must |
| FR-ISU-012-004 | Use configurable matching window (`days_before`/`days_after`) for candidate discovery | Must |
| FR-ISU-012-005 | Use configurable amount tolerance (`rounding_amount`) for candidate matching | Must |
| FR-ISU-012-006 | Support three-way comparison: source A staged + source B staged + native FA transaction | Should |
| FR-ISU-012-007 | Score match candidates by amount, date proximity, customer, and line items | Should |
| FR-ISU-012-008 | GL mismatch SHALL NOT block processing — advisory only | Must |

**Source Files**: `class.ksf_import_square.php` (findMatchingTransactions, displayMatchingPaymentsMatch)

---

### FR-ISU-013: Source Identification on Staged Transactions

**Description**: All staged transactions SHALL carry a source identifier
indicating which importing module created them.

| ID | Requirement | Priority |
|----|------------|----------|
| FR-ISU-013-001 | Staging request SHALL include `source` parameter (e.g., 'square', 'woocommerce') | Must |
| FR-ISU-013-002 | Source identifier stored in staging table for filtering and traceability | Must |
| FR-ISU-013-003 | ISU review UI SHALL support filtering by source | Should |

**Source Files**: `hooks.php`, `class.ksf_import_square_transactions.php`

---

## 3. Non-Functional Requirements

| ID | Requirement | Details |
|----|------------|---------|
| NFR-ISU-001 | PHP Version | PHP 7.3 compatible. No PHP 8+ features (named args, match, enums, etc.) |
| NFR-ISU-002 | FA Version | FrontAccounting 2.4.19 |
| NFR-ISU-003 | Idempotency | Re-importing the same CSV must not create duplicate staging records |
| NFR-ISU-004 | Error Logging | All import errors logged via `display_error()` and `display_notification()` |
| NFR-ISU-005 | MySQL Row Limit | Respect 4KB row limit; avoid excessive field lengths |
| NFR-ISU-006 | Security | All pages require `page_security` and `add_access_extensions()` |
| NFR-ISU-007 | Autoloader | Composer autoloader loaded lazily in `hook_invoke_all()` |
| NFR-ISU-008 | Performance | Use `set_time_limit(0)` for large imports; consider batch processing |

---

## 4. Tables Reference

> **Note**: These tables are source-agnostic. Staged transactions from
> Square, WooCommerce, and future sources share the same tables, distinguished
> by a source identifier column. Source-specific fields (GL accounts, bank
> accounts) are provided by the importing module at processing time, not
> stored in these tables.

### `0_ksf_import_square_transactions`

Primary staging table for Square transactions. Key columns:

| Column | Type | Description |
|--------|------|-------------|
| id | int(11) AI | Primary key |
| Date | date | Transaction date |
| Time | varchar(8) | Transaction time |
| transaction_id | varchar(32) | Square unique transaction ID |
| payment_id | varchar(32) | Square payment ID |
| deposit_id | varchar(32) | Square deposit ID |
| gross_sales | float | Gross sales amount |
| net_sales | float | Net sales amount |
| tax | float | Tax amount |
| total_collected | float | Total collected |
| card | float | Card payment amount |
| cash | float | Cash payment amount |
| fees | float | Square processing fees |
| net_total | float | Net total after fees |
| customer_name | varchar(64) | Customer name from Square |
| transaction_status | varchar(16) | Transaction status |
| last_updated | timestamp | Auto-updated timestamp |

### `0_ksf_import_square_items`

Line items per transaction. Key columns:

| Column | Type | Description |
|--------|------|-------------|
| id | int(11) AI | Primary key |
| transaction_id | varchar(32) | Links to parent transaction |
| Item | varchar(64) | Item name from Square |
| stock_id | varchar(32) | Mapped FA stock ID |
| quantity | int(11) | Quantity sold |
| gross_sales | float | Line item gross |
| net_sales | float | Line item net |
| tax | float | Line item tax |

### `0_ksf_import_square_payments`

Payment-to-FA match records.

| Column | Type | Description |
|--------|------|-------------|
| square_import_payments_id | int(11) AI | Primary key |
| square_payment_id | varchar(32) UNI | Square payment ID |
| total_collected | float | Amount collected |
| trans_type | int(11) | FA transaction type |
| trans_no | varchar(32) | FA transaction number |

---

## 5. Glossary

| Term | Definition |
|------|-----------|
| Staging | Inserting imported data into intermediate tables before processing into FA ledger |
| Source Module | An importing module (Square, WooCommerce, etc.) that provides data to ISU |
| Config Delegation | Pattern where source modules provide GL/bank/payment config to ISU at processing time |
| GL Mismatch | Advisory flag when staged transaction GL differs from matched FA transaction GL |
| Three-Way Match | Comparison of staged source A + staged source B + native FA transaction |
| Match Scoring | Weighted algorithm scoring candidates by amount, date, customer, line items |
| Square | Third-party point-of-sale and payment processing platform |
| WooCommerce | WordPress e-commerce plugin providing REST API for products/orders/customers |
| Settlement | The process of a transaction being transferred to the merchant's bank account |
| Counterparty | The other party in a transaction (customer or vendor) |
| PAN | Primary Account Number (card number suffix used for identification) |
| Integration Module | The source module registered to provide config values to ISU via delegation |
