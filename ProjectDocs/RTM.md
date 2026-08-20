# Requirements Traceability Matrix — ksf_FA_ImportStagingProcessing_UI

> **Module Code**: ISU
> **Version**: 1.0.0
> **Status**: Draft

---

## 1. Functional Requirement to Use Case Mapping

| FR ID | FR Name | UC-ISU-001 | UC-ISU-002 | UC-ISU-003 | UC-ISU-004 |
|-------|---------|:----------:|:----------:|:----------:|:----------:|
| FR-ISU-001 | CSV Import | X | | | |
| FR-ISU-002 | Square API Import | | X | | |
| FR-ISU-003 | Transaction Staging | X | X | X | X |
| FR-ISU-004 | Item Staging | X | X | X | X |
| FR-ISU-005 | Process Staged Transactions | | | X | |
| FR-ISU-006 | Customer/Counterparty Matching | | | X | X |
| FR-ISU-007 | UI Pages | X | X | X | X |
| FR-ISU-008 | Duplicate Detection | X | X | | X |

---

## 2. FR to Code Mapping

| FR ID | Code File(s) | Class/Function |
|-------|-------------|----------------|
| FR-ISU-001-001 | import_statements.php | upload form rendering |
| FR-ISU-001-002 | class.ro_square_trans_csv_model.php | ro_square_trans_csv_model::arr2obj() |
| FR-ISU-001-003 | class.ro_square_items_csv_model.php | ro_square_items_csv_model::arr2obj() |
| FR-ISU-001-004 | class.import_statements_gui.php | do_upload_form() |
| FR-ISU-001-005 | class.import_statements_gui.php | display() |
| FR-ISU-001-006 | import_statements.php | insert loop (lines 68-101) |
| FR-ISU-001-007 | class.import_statements_controller.php | statement_exists() check |
| FR-ISU-002-001 | class.ksf_import_square.php | __construct() - API config |
| FR-ISU-002-002 | class.ksf_import_square.php | import_files_form() |
| FR-ISU-002-003 | class.ksf_import_square.php | upload_file_form() |
| FR-ISU-002-004 | class.ksf_import_square.php | process_payments_form() |
| FR-ISU-002-005 | class.ksf_import_square.php | formFromTo() date range |
| FR-ISU-002-006 | class.ksf_import_square_transactions.php | insert_transaction() |
| FR-ISU-003-001 | class.ksf_import_square_transactions.php | define_table() - full schema |
| FR-ISU-003-002 | class.ksf_import_square_transactions.php | transaction_status field |
| FR-ISU-003-003 | class.ksf_import_square_transactions.php | getTransactionsDateRange() |
| FR-ISU-003-004 | class.ksf_import_square_transactions.php | statusFilter in queries |
| FR-ISU-003-005 | class.ksf_import_square_transactions.php | card, cash, fees, net_total fields |
| FR-ISU-003-006 | class.ksf_import_square_transactions.php | customer_name, Customer_id fields |
| FR-ISU-003-007 | class.ksf_import_square_transactions.php | deposit_id, deposit_date fields |
| FR-ISU-003-008 | class.ksf_import_square_transactions.php | last_updated timestamp default |
| FR-ISU-003-009 | class.ksf_import_square_transactions.php | set() - filter_var for floats |
| FR-ISU-004-001 | class.ksf_import_square_items.php | define_table() - full schema |
| FR-ISU-004-002 | class.ksf_import_square_items.php | transaction_id foreign key |
| FR-ISU-004-003 | class.ksf_import_square_items.php | Category, Item, stock_id fields |
| FR-ISU-004-004 | class.ksf_import_square_items.php | gross_sales, discounts, net_sales, tax |
| FR-ISU-004-005 | class.ksf_import_square_items.php | getTransaction($transaction_id) |
| FR-ISU-004-006 | class.ksf_import_square_items.php | stock_id mapping field |
| FR-ISU-005-001 | class.ksf_import_square.php | viewCreateDirectInvoice() |
| FR-ISU-005-002 | process_statements.php | ST_CUSTPAYMENT case (line 324) |
| FR-ISU-005-003 | class.ksf_import_square.php | add_deposit_form() - fa_bank_transfer |
| FR-ISU-005-004 | process_statements.php | ST_SUPPAYMENT case (line 200) |
| FR-ISU-005-005 | process_statements.php | fa_customer_payment::write_allocation() |
| FR-ISU-005-006 | class.ksf_import_square_payments.php | insertPaymentMatch() |
| FR-ISU-005-007 | class.ksf_import_square_sales_model.php | insert (sales match) |
| FR-ISU-005-008 | process_statements.php | statusFilter array_selector (line 660) |
| FR-ISU-005-009 | process_statements.php | date_cells From/To (lines 658-659) |
| FR-ISU-005-010 | process_statements.php | UnsetTrans handler (line 63) |
| FR-ISU-006-001 | class.ksf_import_external_customers_model.php | searchCustomersByName() |
| FR-ISU-006-002 | process_statements.php | AddCustomer handler (line 79) |
| FR-ISU-006-003 | process_statements.php | search_partner_by_bank_account() |
| FR-ISU-006-004 | process_statements.php | partnerType array_selector |
| FR-ISU-006-005 | class.ksf_import_square.php | useCardAsBranch config |
| FR-ISU-006-006 | process_statements.php | customer_list(), customer_branches_list() |
| FR-ISU-007-001 | import_statements.php | main entry page |
| FR-ISU-007-002 | process_statements.php | process queue with filters |
| FR-ISU-007-003 | ksf_import_square.php | main tabbed interface |
| FR-ISU-007-004 | class.ksf_import_square.php | transaction_items_form() |
| FR-ISU-007-005 | class.ksf_import_square.php | add_deposit_form(), add_payment_form() |
| FR-ISU-007-006 | class.ksf_import_square.php | match_deposit_form(), match_payment_form() |
| FR-ISU-007-007 | class.ksf_import_square.php | edit_item_sku action tab |
| FR-ISU-008-001 | class.ksf_import_square_transactions.php | transaction_id existence check |
| FR-ISU-008-002 | class.ksf_import_square_payments.php | square_payment_id unique index |
| FR-ISU-008-003 | class.ksf_import_square.php | deposit_id checks |
| FR-ISU-008-004 | class.bi_counterparty_model.php | counterparty deduplication |
| FR-ISU-008-005 | class.bi_transactionTitle_model.php | title-based deduplication |
| FR-ISU-008-006 | class.import_statements_controller.php | statement_exists(), trans_exists() |

---

## 3. FR to Business Requirement Mapping

| FR ID | BR-ISU-001 | BR-ISU-002 | BR-ISU-003 | BR-ISU-004 |
|-------|:----------:|:----------:|:----------:|:----------:|
| FR-ISU-001 | X | X | | |
| FR-ISU-002 | X | X | | |
| FR-ISU-003 | X | X | | |
| FR-ISU-004 | X | | | |
| FR-ISU-005 | | | X | X |
| FR-ISU-006 | | | | X |
| FR-ISU-007 | X | | X | X |
| FR-ISU-008 | | X | | |

---

## 4. UAT to FR Mapping

| UAT ID | UAT Name | FR IDs |
|--------|----------|--------|
| UAT-ISU-001 | Upload CSV and verify staging records | FR-ISU-001, FR-ISU-003, FR-ISU-004 |
| UAT-ISU-002 | Import via API and verify transactions staged | FR-ISU-002, FR-ISU-003 |
| UAT-ISU-003 | Process staged transactions into FA invoices | FR-ISU-005, FR-ISU-006 |
| UAT-ISU-004 | Duplicate detection prevents re-import | FR-ISU-008 |
| UAT-ISU-005 | Counterparty matching links to existing customers | FR-ISU-006 |
| UAT-ISU-006 | Error handling for malformed CSV data | FR-ISU-001, FR-ISU-007 |
| UAT-ISU-007 | Process queue displays correct status | FR-ISU-005, FR-ISU-007 |

---

## 5. Status Key

- **Covered**: FR has corresponding code, test, and UC
- **Partial**: FR has code but incomplete test coverage
- **Planned**: FR is defined but not yet implemented
- **N/A**: FR is not applicable to current release
