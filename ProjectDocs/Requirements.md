# Requirements Specification — ksf_FA_ImportStagingProcessing_UI

> **Module Code**: ISU
> **Version**: 1.0.0
> **Status**: Draft

---

## 1. Business Context

FrontAccounting (FA) needs to import Square point-of-sale transactions for accounting purposes. Currently, Square transaction data exists in two forms:

1. **CSV exports** from the Square Dashboard (Summary and Items reports)
2. **Square API** providing programmatic access to Orders, Payments, and Refunds

The module stages imported data into intermediate tables before processing into FA's ledger, ensuring idempotent imports, duplicate detection, and proper customer/vendor matching.

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
| Square | Third-party point-of-sale and payment processing platform |
| Settlement | The process of a Square transaction being transferred to the merchant's bank account |
| Counterparty | The other party in a transaction (customer or vendor) |
| PAN | Primary Account Number (card number suffix used for identification) |
