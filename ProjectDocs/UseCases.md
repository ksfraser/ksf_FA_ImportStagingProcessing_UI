# Use Cases — ksf_FA_ImportStagingProcessing_UI

> **Module Code**: ISU
> **Version**: 1.0.0
> **Status**: Draft

---

## UC-ISU-001: Import Square CSV

**Primary Actor**: Accountant / Bookkeeper
**Goal**: Upload a Square Dashboard CSV export and stage the transactions for processing.

### Trigger
User navigates to Import Square Transactions menu item and selects a CSV file.

### Preconditions
- User is authenticated with SA_ksf_FA_ImportStagingProcessing_UI access
- Square CSV file has been exported from the Square Dashboard
- Staging tables exist in the database

### Postconditions
- Parsed transaction data is stored in session for review
- On confirmation, records are inserted into 0_ksf_import_square_transactions and 0_ksf_import_square_items
- Duplicate transactions are detected and skipped

### Basic Flow
1. User clicks Import Square Transactions in the GL/Orders menu
2. System displays the upload form (class.import_statements_gui::do_upload_form())
3. User selects a CSV file and clicks Upload
4. System calls parse_uploaded_files::process_upload("transaction_id") to parse the CSV
5. System identifies the parser model (ro_square_trans_csv_model or ro_square_items_csv_model)
6. System displays parsed data for review
7. User clicks Insert Data to confirm import
8. System iterates through parsed statements:
   a. For each transaction, calls arr2obj() and insert_transaction() on the parser model
   b. Checks for existing transaction_id - skips if duplicate
   c. Inserts into staging table
9. System displays import summary (new/updated/skipped counts)

### Alternative Flows
- **4a. CSV parsing fails**: System displays error message with file and line details
- **8a. Duplicate detected**: System logs notification and skips the record
- **8b. Insert fails**: System catches exception and displays error with transaction data

### Related Requirements
FR-ISU-001, FR-ISU-003, FR-ISU-004, FR-ISU-007

---

## UC-ISU-002: Import via Square API

**Primary Actor**: Accountant / Bookkeeper
**Goal**: Pull transactions from the Square API and stage them for processing.

### Trigger
User navigates to the main module page and selects an API import action.

### Preconditions
- Square API credentials are configured (OAuth token)
- ksf_import_square controller is installed and configured
- Network access to Square API endpoints

### Postconditions
- Transactions pulled from Square API are staged in 0_ksf_import_square_transactions
- Line items are staged in 0_ksf_import_square_items
- Payment and deposit data is available for processing

### Basic Flow
1. User clicks Staging Import in the GL/Orders menu
2. System loads ksf_import_square controller via ksf_import_square.php
3. System displays the tabbed interface with import options
4. User selects API import and specifies date range
5. System authenticates with Square API
6. System pulls Orders, Payments, and Refunds for the date range
7. System maps Square API data to staging table format
8. System inserts records into staging tables with duplicate detection
9. System displays import summary

### Alternative Flows
- **5a. Authentication fails**: System displays error with API credentials issue
- **6a. API rate limit hit**: System retries after delay or notifies user
- **6b. Network error**: System logs error and allows retry
- **8a. Duplicate transaction**: System skips and notifies

### Related Requirements
FR-ISU-002, FR-ISU-003, FR-ISU-004

---

## UC-ISU-003: Process Staged Transactions

**Primary Actor**: Accountant / Bookkeeper
**Goal**: Process staged Square transactions into FA ledger entries (invoices, payments, bank transfers).

### Trigger
User navigates to Process Staging or Process Transactions page and selects transactions to process.

### Preconditions
- Staged transactions exist in 0_ksf_import_square_transactions
- Staged items exist in 0_ksf_import_square_items
- FA bank accounts and GL accounts are configured
- Default customer is configured for unmatched transactions
- Date range and status filter are set

### Postconditions
- FA Sales Invoices are created from transaction + item data
- FA Customer Payments are recorded and allocated to invoices
- FA Bank Transfers are created for Square deposits
- Processed transactions are marked as settled
- Match records are stored in 0_ksf_import_square_payments and 0_ksf_import_square_sales

### Basic Flow
1. User navigates to Process Staging page
2. System displays date range filter and status filter (unsettled/settled/all)
3. User sets date range and clicks Search
4. System queries staged transactions via getTransactionsDateRange()
5. System displays each transaction with details and matching options
6. For each transaction, user selects partner type (Customer/Supplier/Bank Transfer/Quick Entry/Match)
7. System proposes matching based on bank account, amount, and date
8. User confirms processing
9. System creates appropriate FA transaction:
   a. Sales Invoice: Creates cart, adds line items from 0_ksf_import_square_items, writes invoice
   b. Customer Payment: Creates payment, allocates to selected invoice
   c. Bank Transfer: Creates transfer from Square bank to destination bank
10. System updates staged transaction status to settled
11. System stores match records in cross-reference tables
12. System displays link to view created FA transaction

### Alternative Flows
- **6a. No match found**: System allows manual partner selection or creation
- **9a. Invoice creation fails**: System rolls back transaction and displays error
- **9b. Customer not found**: System creates default customer or prompts creation
- **9c. Amount mismatch**: System displays warning but allows processing

### Related Requirements
FR-ISU-005, FR-ISU-006

---

## UC-ISU-004: Review and Correct Import

**Primary Actor**: Accountant / Bookkeeper
**Goal**: Review staged transactions, correct mapping issues, and approve or reject records before processing.

### Trigger
User navigates to transaction detail view from the process queue.

### Preconditions
- Staged transactions exist in staging tables
- User has VIEW or MANAGE access

### Postconditions
- Transaction details are displayed with items, customer info, and matching invoices
- SKU mappings can be corrected
- Customer/vendor associations can be updated
- Rejected transactions are not processed

### Basic Flow
1. User clicks View Transaction on a staged transaction
2. System loads transaction via ksf_import_square_transactions_model::getTransaction()
3. System displays customer details from Square (Customer_id, customer_name, customer_reference_id)
4. System displays financial details (gross_sales, net_sales, tax, total_collected, card, cash)
5. System loads line items via ksf_import_square_items_model::getTransaction()
6. System searches for matching invoices via fa_debtor_trans_model::findMatchingInvoices()
7. System displays potential matching invoices with scores
8. User reviews SKU mappings:
   a. Clicks Edit SKU link for items with missing or incorrect stock_id
   b. Updates stock_id to match FA inventory item
9. User reviews customer match:
   a. Confirms auto-matched customer
   b. Or selects different customer from dropdown
10. User clicks Match Transaction to Invoice to link to specific FA invoice

### Alternative Flows
- **3a. No customer data**: System displays Create Customer option
- **7a. No matching invoices found**: System offers to create direct invoice
- **8a. Custom Amount item**: System allows SKU edit for custom amount items

### Related Requirements
FR-ISU-007, FR-ISU-008
