# User Acceptance Test Cases — ksf_FA_ImportStagingProcessing_UI

> **Module Code**: ISU
> **Version**: 1.0.0
> **Status**: Draft

---

## Test Environment Requirements

- FA 2.4.19 installed with sample data
- ksf_FA_ImportStagingProcessing_UI module activated
- ksf_modules_common module installed
- MySQL/MariaDB with staging tables created
- PHP 7.3 on the server
- Square CSV test files (Summary and Items formats)

---

## UAT-ISU-001: Upload CSV and Verify Staging Records Created

**FR Reference**: FR-ISU-001, FR-ISU-003, FR-ISU-004

### Preconditions
- User has SA_ksf_FA_ImportStagingProcessing_UI access
- Staging tables 0_ksf_import_square_transactions and 0_ksf_import_square_items exist
- A valid Square Summary CSV file is available for upload

### Test Steps

| Step | Action | Expected Result |
|------|--------|----------------|
| 1 | Navigate to GL/Orders > Import Square Transactions | Upload form is displayed with file input and parser selector |
| 2 | Select a valid Square Summary CSV file | File is selected in the form |
| 3 | Click Upload button | System parses CSV and displays parsed data for review |
| 4 | Verify parsed data shows correct transaction fields (Date, transaction_id, gross_sales, etc.) | All expected fields are populated with correct values from CSV |
| 5 | Select the ro_square_trans_csv parser if not auto-selected | Parser is selected |
| 6 | Click Insert Data button | System inserts records into 0_ksf_import_square_transactions |
| 7 | Verify import summary shows new records created | Summary displays count of inserted records |
| 8 | Query 0_ksf_import_square_transactions table directly | Records exist with correct data matching the CSV |
| 9 | Upload a Square Items CSV file | System parses items and displays for review |
| 10 | Click Insert Data for items | Records are inserted into 0_ksf_import_square_items |
| 11 | Verify items are linked to parent transactions via transaction_id | Items have valid transaction_id values matching transactions table |

### Pass Criteria
- All CSV records are staged in the correct tables
- Data types are correct (dates, floats, strings)
- Transaction IDs are preserved
- No duplicate records on single upload

---

## UAT-ISU-002: Import via API and Verify Transactions Staged

**FR Reference**: FR-ISU-002, FR-ISU-003

### Preconditions
- Square API credentials are configured in module settings
- Network access to Square API is available
- Staging tables exist

### Test Steps

| Step | Action | Expected Result |
|------|--------|----------------|
| 1 | Navigate to GL/Orders > Staging Import | Module tabbed interface loads |
| 2 | Verify Configuration tab shows Square API settings | API credential fields are present |
| 3 | Configure API credentials if not already set | Settings are saved |
| 4 | Navigate to Import Files tab | Import options are displayed |
| 5 | Select API import and specify a date range (e.g., last 7 days) | Date range is set |
| 6 | Initiate API pull | System connects to Square API |
| 7 | Verify API returns transaction data | Transactions are received |
| 8 | Verify staged records in 0_ksf_import_square_transactions | Records exist with correct Square transaction data |
| 9 | Verify item records in 0_ksf_import_square_items | Line items are staged with correct transaction_id links |
| 10 | Verify payment_id and deposit_id fields are populated | Payment and deposit IDs from Square are stored |

### Pass Criteria
- API connection succeeds
- Transactions are staged with all relevant fields
- No duplicate records on repeated API pull for same date range

---

## UAT-ISU-003: Process Staged Transactions into FA Invoices

**FR Reference**: FR-ISU-005, FR-ISU-006

### Preconditions
- Staged transactions exist in 0_ksf_import_square_transactions (unsettled)
- Staged items exist in 0_ksf_import_square_items
- FA bank accounts and GL accounts are configured
- Default customer is configured

### Test Steps

| Step | Action | Expected Result |
|------|--------|----------------|
| 1 | Navigate to GL/Orders > Process Staging | Process queue page loads |
| 2 | Set date range to include staged transactions | Date range filter is set |
| 3 | Set status filter to Unsettled | Filter shows unsettled transactions |
| 4 | Click Search | Staged transactions are displayed in the queue |
| 5 | Verify each transaction shows Date, amounts, customer_name, transaction_id | All key fields are visible |
| 6 | Select partner type for a transaction (e.g., CU for customer) | Partner type dropdown changes |
| 7 | Select or confirm customer from dropdown | Customer is selected |
| 8 | Click Process button | System creates FA Sales Invoice from transaction + items |
| 9 | Verify Sales Invoice is created in FA | Invoice exists in 0_sales_orders or equivalent with correct line items |
| 10 | Verify invoice line items match staged items | stock_id, quantity, price match |
| 11 | Verify transaction status updated to settled | Status shows settled in the queue |
| 12 | Click View Entry link | FA transaction view page opens showing the created invoice |
| 13 | Process a payment transaction | Customer Payment is created and allocated |
| 14 | Verify payment is recorded in 0_ksf_import_square_payments | Match record exists with correct square_payment_id and trans_no |

### Pass Criteria
- FA invoices are created with correct line items
- Customer payments are recorded and allocated
- Status is updated to settled
- Cross-reference records are stored
- Links to FA transactions work correctly

---

## UAT-ISU-004: Duplicate Detection Prevents Re-import

**FR Reference**: FR-ISU-008

### Preconditions
- A Square CSV has already been imported (transactions exist in staging table)

### Test Steps

| Step | Action | Expected Result |
|------|--------|----------------|
| 1 | Navigate to Import Square Transactions | Upload form is displayed |
| 2 | Upload the same CSV file that was previously imported | System parses CSV |
| 3 | Click Insert Data | System processes each record |
| 4 | Verify notification shows duplicate records were skipped | Display_notification shows skip messages |
| 5 | Query 0_ksf_import_square_transactions table | No duplicate transaction_id records exist |
| 6 | Verify record count is unchanged from first import | Same number of records as after first import |

### Pass Criteria
- Duplicate transaction_ids are detected and skipped
- No duplicate records in staging table
- User is notified of skipped duplicates
- Original records are unchanged

---

## UAT-ISU-005: Counterparty Matching Links to Existing Customers

**FR Reference**: FR-ISU-006

### Preconditions
- Existing FA customers with names matching Square customer data
- Staged transactions with customer_name populated

### Test Steps

| Step | Action | Expected Result |
|------|--------|----------------|
| 1 | Navigate to Process Staging page | Transaction queue loads |
| 2 | Find a transaction with a customer_name that matches an existing FA customer | Transaction is displayed |
| 3 | Select CU (Customer) as partner type | Customer dropdown appears |
| 4 | Verify system proposes matching customer | Dropdown pre-selects matching customer |
| 5 | Verify customer_branches_list shows branches if customer has multiple | Branch dropdown appears |
| 6 | Process the transaction | Invoice is created for matched customer |
| 7 | Verify FA invoice debtor_no matches the matched customer | Invoice is associated with correct customer |

### Pass Criteria
- Auto-matching proposes correct customer
- Manual override works
- Invoice is created under correct customer account

---

## UAT-ISU-006: Error Handling for Malformed CSV Data

**FR Reference**: FR-ISU-001, FR-ISU-007

### Preconditions
- A malformed or incomplete CSV file is available

### Test Steps

| Step | Action | Expected Result |
|------|--------|----------------|
| 1 | Navigate to Import Square Transactions | Upload form is displayed |
| 2 | Upload a CSV file missing required columns (e.g., no transaction_id) | System attempts parse |
| 3 | Verify error message is displayed | display_error() shows meaningful error |
| 4 | Verify no partial records are inserted | Staging table is unchanged |
| 5 | Upload a CSV file with invalid data types (e.g., text in amount field) | System handles gracefully |
| 6 | Verify invalid rows are skipped with notification | User sees which rows failed |
| 7 | Upload a CSV with valid rows mixed with invalid rows | System processes valid rows, skips invalid |
| 8 | Verify valid records are staged | Valid rows exist in staging table |

### Pass Criteria
- Malformed CSV produces clear error messages
- No partial/corrupt records in staging
- Valid rows in mixed files are still imported
- System remains stable after error

---

## UAT-ISU-007: Process Queue Displays Correct Status

**FR Reference**: FR-ISU-005, FR-ISU-007

### Preconditions
- Mix of settled and unsettled staged transactions exist

### Test Steps

| Step | Action | Expected Result |
|------|--------|----------------|
| 1 | Navigate to Process Staging page | Queue loads with default filter |
| 2 | Set status filter to Unsettled | Only unsettled transactions shown |
| 3 | Verify unsettled transactions show Process button | Each row has actionable controls |
| 4 | Set status filter to Settled | Only settled transactions shown |
| 5 | Verify settled transactions show settled status and View links | Status is clearly indicated; links to FA entries work |
| 6 | Set status filter to All | All transactions shown regardless of status |
| 7 | Change date range to exclude some transactions | Queue updates to show only matching date range |
| 8 | Verify record counts match expectations | Correct number of transactions displayed |

### Pass Criteria
- Status filter works correctly for all three options
- Date range filter correctly limits displayed records
- Settled transactions show appropriate status indicators
- Process buttons only appear for unsettled transactions
- View links work for settled transactions

---

## Appendix: Test Data Requirements

### Sample Square Summary CSV
```
Date,Time,Timezone,Gross Sales,Discounts,Net Sales,Tax,Total Collected,Card,Cash,Transaction ID,Payment ID,Deposit ID,Customer Name
2025-01-15,10:30:00,EST,25.00,0.00,25.00,2.00,27.00,27.00,0.00,SQ_TXN_001,SQ_PAY_001,SQ_DEP_001,John Smith
2025-01-15,11:45:00,EST,15.50,0.00,15.50,1.24,16.74,0.00,16.74,SQ_TXN_002,SQ_PAY_002,,Jane Doe
```

### Sample Square Items CSV
```
Date,Time,Category,Item,Quantity,Gross Sales,Discounts,Net Sales,Tax,Transaction ID
2025-01-15,10:30:00,Apparel,T-Shirt M,1,25.00,0.00,25.00,2.00,SQ_TXN_001
2025-01-15,11:45:00,Food,Coffee Large,2,8.00,0.00,8.00,0.64,SQ_TXN_002
2025-01-15,11:45:00,Food,Muffin,1,7.50,0.00,7.50,0.60,SQ_TXN_002
```
