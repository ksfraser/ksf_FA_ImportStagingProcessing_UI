# BABOK Business Analysis — ksf_FA_ImportStagingProcessing_UI

> **Module Code**: ISU
> **Version**: 1.0.0
> **Status**: Draft

---

## 1. Business Analysis Overview

### 1.1 Purpose

This document captures the business analysis for the ksf_FA_ImportStagingProcessing_UI module, aligned with BABOK (Business Analysis Body of Knowledge) practices. It maps business needs to requirements and solution design.

### 1.2 Scope

The module automates the import of Square POS transactions into FrontAccounting, replacing manual CSV data entry and providing a pipeline from raw Square data to posted ledger entries.

---

## 2. Stakeholders

| Stakeholder | Role | Interest |
|-------------|------|----------|
| FA Administrators | Module installers and configurators | Setup, configuration, module activation |
| Accountants / Bookkeepers | Daily users | Import transactions, match to invoices, process payments |
| Business Owners | Consumers of financial reports | Accurate, timely recording of Square sales |
| IT Support | Technical maintainers | Module updates, troubleshooting, API configuration |
| Square API Administrators | API credential holders | Maintain OAuth tokens and API access |

---

## 3. Business Needs

### 3.1 Current State (As-Is)

- Square transactions are exported manually as CSV from the Square Dashboard
- CSV data is reviewed visually or imported via ad-hoc scripts
- Manual data entry into FA for sales invoices and payments
- No automated duplicate detection — risk of double-entry
- No systematic link between Square transaction IDs and FA ledger entries
- Time-consuming reconciliation between Square deposits and bank statements

### 3.2 Desired State (To-Be)

- Automated CSV import with validation and staging
- Optional Square API integration for real-time or scheduled pulls
- Automatic duplicate detection using transaction IDs and counterparty models
- One-click processing of staged transactions into FA invoices, payments, and transfers
- Full traceability from Square transaction_id to FA trans_no
- Reduced manual data entry and reconciliation effort

### 3.3 Gap Analysis

| Gap | Current | Target | Solution |
|-----|---------|--------|----------|
| Data Entry | Manual CSV typing | Automated CSV parse + stage | CSV upload with parser models |
| Duplicate Detection | None | Transaction ID + counterparty matching | Staging tables with existence checks |
| API Access | Not available | Square API pull | API import controller |
| Invoice Matching | Manual | Automated amount/date matching | `fa_debtor_trans_model::findMatchingInvoices()` |
| Deposit Reconciliation | Manual bank statement review | Auto-matched deposits | Bank transfer creation from Square deposits |

---

## 4. Business Requirements

### BR-ISU-001: Automated Transaction Import

**Statement**: The system SHALL accept Square transaction data via CSV upload or Square API and stage it for processing into FrontAccounting.

**Rationale**: Manual data entry is error-prone and time-consuming. Automated import reduces errors and frees staff time.

**Acceptance Criteria**:
- CSV files from Square Dashboard can be uploaded and parsed
- Parsed data is displayed for review before staging
- Staged records are stored in `0_ksf_import_square_transactions` and `0_ksf_import_square_items`
- Duplicate transactions are detected and skipped with notification

**Related FRs**: FR-ISU-001, FR-ISU-002, FR-ISU-003, FR-ISU-004

---

### BR-ISU-002: Duplicate Prevention

**Statement**: The system SHALL prevent duplicate entry of the same Square transaction into the FA ledger.

**Rationale**: Re-importing the same CSV or API data must not create duplicate invoices or payments.

**Acceptance Criteria**:
- `transaction_id` uniqueness is checked before insert into staging
- `payment_id` is checked before creating payment matches
- User is notified when a duplicate is skipped
- Counterparty and transaction title models support additional deduplication

**Related FRs**: FR-ISU-008

---

### BR-ISU-003: Automated Ledger Processing

**Statement**: The system SHALL process staged transactions into appropriate FA ledger entries (sales invoices, customer payments, bank transfers) with minimal manual intervention.

**Rationale**: Processing staged data should be a single-step operation after review.

**Acceptance Criteria**:
- Sales invoices are created from Square transaction + item data
- Customer payments are recorded and allocated to invoices
- Bank transfers are created for Square deposits to the bank account
- Each processed transaction is marked as settled with links to FA entries
- Matched transactions are recorded in cross-reference tables

**Related FRs**: FR-ISU-005

---

### BR-ISU-004: Customer and Vendor Matching

**Statement**: The system SHALL match Square transactions to existing FA customers and vendors, and support creation of new entities when no match is found.

**Rationale**: Accurate customer/vendor association is required for proper accounting and reporting.

**Acceptance Criteria**:
- Customers are matched by name from the external customers table
- Vendors are matched by bank account short name
- New customers can be created from Square customer data
- PAN suffix can be used as a branch identifier
- Manual override is available on the process screen

**Related FRs**: FR-ISU-006

---

## 5. Business Rules

| Rule ID | Description |
|---------|------------|
| BR-ISU-R001 | A Square transaction with an existing `transaction_id` in the staging table SHALL NOT be re-inserted |
| BR-ISU-R002 | A Square payment with an existing `square_payment_id` in the payments table SHALL NOT be re-inserted |
| BR-ISU-R003 | Transactions SHALL only be processed into FA if they are in "unsettled" status |
| BR-ISU-R004 | Customer payments SHALL be allocated to a single invoice at a time |
| BR-ISU-R005 | Bank transfers for Square deposits SHALL be from the configured Square bank account to the configured transfer destination bank account |
| BR-ISU-R006 | All monetary values SHALL be sanitized to 2 decimal places before storage |
| BR-ISU-R007 | The module SHALL use the `0_` company prefix for all tables |

---

## 6. Solution Approach

### 6.1 Architecture

The solution is a FrontAccounting 2.4 module following MVC patterns:

- **Entry points** handle session setup, security, and page rendering
- **Controller classes** orchestrate business logic (import, process, match)
- **Model classes** handle data access and validation
- **GUI classes** handle UI rendering
- **Parser classes** handle CSV format-specific parsing

### 6.2 Data Flow

```
Square CSV/API → Parser → Controller → Staging Tables → Process UI → FA Ledger
                                                        ↓
                                                   Match Tables
                                            (payments, sales, bank_transfer)
```

### 6.3 External Dependencies

- **ksf_modules_common**: Shared FA interface classes, parsers, bank transfer, customer payment, debtor transaction models
- **ksf_FA_ImportStagingProcessing**: Backend processing library
- **FA Core**: Sales, purchasing, banking, and GL modules

---

## 7. Assumptions and Constraints

### Assumptions
- Square CSV export format is consistent and stable
- FA 2.4.19 is the target platform with standard modules enabled
- MySQL/MariaDB is the database backend
- PHP 7.3 is available on the server

### Constraints
- PHP 7.3 compatibility (no PHP 8+ features)
- MySQL 4KB row limit must be respected
- FA's `db_query()` abstraction must be used for all database operations
- Security sections must be registered via `install_access()`
- Composer autoloader must be loaded lazily

---

## 8. Risks

| Risk | Impact | Mitigation |
|------|--------|-----------|
| Square CSV format changes | Parser breaks | Document expected columns; validate on parse |
| API rate limits | Import delays | Implement retry logic and pagination |
| Large import volumes | Timeout | Use `set_time_limit(0)`; consider batch processing |
| Duplicate data on re-import | Double-entry | Transaction ID checks; idempotent inserts |
| MySQL row size limit | Insert failures | Monitor field sizes; split large data |
