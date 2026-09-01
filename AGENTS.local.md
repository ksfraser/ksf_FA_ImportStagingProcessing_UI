<!-- Repo-specific appendix to the shared AGENTS.md. Generic conventions live in AGENTS_ARCH.md (hardlinked). -->

# AGENTS.md — ksf_FA_ImportStagingProcessing_UI
> **Project-specific development guidelines.**
> Inherits all rules from `~/AGENTS.md` (master). This file adds module-specific details.
---
## Module Overview
**ksf_FA_ImportStagingProcessing_UI** is the FrontAccounting UI layer for importing Square transactions. It provides CSV upload, Square API import, transaction staging, customer/counterparty matching, and processing of staged transactions into FA sales invoices, payments, and bank transfers.
This module depends on the **ksf_FA_ImportStagingProcessing** backend library (models and processing logic). It was refactored from the legacy `FA_ImportSquareUp` module.
---
## Naming Conventions
### FA Module
- **Directory**: `ksf_FA_ImportStagingProcessing_UI`
- **Hooks class**: `hooks_ksf_FA_ImportStagingProcessing_UI`
- **Security section**: `SS_ksf_FA_ImportStagingProcessing_UI` (defined as `110 << 8`)
- **Security areas**:
  - `SA_ksf_FA_ImportStagingProcessing_UI` — Full access
  - `SA_ksf_FA_ImportStagingProcessing_UIVIEW` — View only
  - `SA_ksf_FA_ImportStagingProcessing_UIMANAGE` — Manage import staging
### SQL Tables
- `0_ksf_import_square_transactions` — Staged Square transactions
- `0_ksf_import_square_items` — Staged line items per transaction
- `0_ksf_import_square_payments` — Payment-to-FA match records
- `0_ksf_import_square_sales` — Transaction-to-FA invoice match records
All tables use the `0_` company prefix. Do NOT use `@TB_PREF@` or `{TB_PREF}`.
### Documentation
- **Module code**: `ISU` (ImportStagingUI)
- **FR naming**: `FR-ISU-<SEQ>-<SUBSEQ>`
- **UC naming**: `UC-ISU-<SEQ>`
- **UAT naming**: `UAT-ISU-<SEQ>`
- **BR naming**: `BR-ISU-<SEQ>`
---
## Architecture
### MVC Pattern
The module follows a loose MVC pattern:
| Layer | Files | Description |
|-------|-------|-------------|
| **Entry Points** | `ksf_import_square.php`, `import_statements.php`, `process_statements.php` | Page entry points with session/security setup |
| **Controller** | `class.ksf_import_square.php`, `class.import_statements_controller.php` | Orchestrate import, process, and matching flows |
| **Models** | `class.ksf_import_square_transactions.php`, `class.ksf_import_square_items.php`, `class.ksf_import_square_payments.php`, `class.bi_counterparty_model.php`, `class.bi_transactionTitle_model.php` | Data access and staging logic |
| **GUI/View** | `class.import_statements_gui.php`, `class.import_statements.php` | UI rendering (forms, tables) |
| **Parsers** | `class.ro_square_trans_csv_model.php`, `class.ro_square_items_csv_model.php` | CSV parsing models for Square export files |
| **Legacy** | `ksf_import_square.php`, `ksf_import_square.inc.php` | Legacy entry point (redirects to new UI) |
### Key Class Hierarchy
All models extend `generic_fa_interface_model` (from `ksf_modules_common`). The main controller `ksf_import_square` extends `generic_fa_interface`.
```
generic_fa_interface_model
  ├── ksf_import_square_transactions_model
  ├── ksf_import_square_items_model
  ├── ksf_import_square_payments_model
  ├── bi_counterparty_model
  └── bi_transationTitle_model
generic_fa_interface
  └── ksf_import_square
```
---
## Dependencies
### Required Modules
- **ksf_modules_common** — Shared library (`class.generic_fa_interface.php`, `defines.inc.php`, `class.parse_uploaded_files.php`, `class.fa_bank_transfer.php`, `class.fa_customer_payment.php`, `class.fa_debtor_trans.php`, `class.fa_gl.php`)
- **ksf_FA_ImportStagingProcessing** — Backend processing library
### FA Core Dependencies
- `includes/session.inc` — Session and authentication
- `includes/date_functions.inc` — Date utilities
- `includes/ui.inc` — UI helpers
- Sales includes: `cart_class.inc` for invoice creation
- Purchasing includes: `purchasing_db.inc` for supplier payments
---
## Menus
Registered under GL and Orders application tabs (app id `GL` / `orders`):
| Menu Item | File | Type |
|-----------|------|------|
| Staging Import | `import_square.php` | Left app (persistent) |
| Import Square Transactions | `import_statements.php` | Right app (maintenance) |
| Process Staging | `process_statements.php` | Right app (import menu) |
---
## Configuration
Module configuration is managed via `ksf_import_square.php` with these prefs:
- `default_customer` — FA customer for unmatched transactions
- `square_gl` / `square_bank` — Square clearing GL/bank accounts
- `xfer_to_gl` / `xfer_to_bank` — Transfer destination accounts
- `cash_gl` / `cash_bank` — Cash accounts
- `useCardAsBranch` — Use PAN suffix as customer branch
- `days_before` / `days_after` — Invoice matching window
- `rounding_amount` — Tolerance for amount matching
- `default_location` / `default_dimension1` / `default_dimension2` — FA defaults
- `allowSkuChange` — Allow SKU editing on staged items
---
## Testing
### TDD Workflow
1. **RED**: Write failing test
2. **GREEN**: Write minimal code to pass
3. **REFACTOR**: Improve while keeping tests green
### Framework
- **PHPUnit** (loaded via Composer autoloader in `hook_invoke_all`)
- **Target**: 100% code coverage
- Skipped tests = failed tests
### Test Structure
```php
namespace Tests\Unit;
class ImportSquareTransactionsTest extends \PHPUnit\Framework\TestCase
{
    /** @BABOK Related: UT-ISU-001-001-001 */
    public function testInsertTransaction(): void
    {
        // Arrange
        $model = new ksf_import_square_transactions_model();
        // Act
        $model->set('transaction_id', 'TEST123');
        $result = $model->insert_transaction();
        // Assert
        $this->assertNotFalse($result);
    }
}
```
### Traceability in Code
Each class/method should reference its FR via PHPDoc:
```php
/** @BABOK Related: FR-ISU-003-001 */
class ksf_import_square_transactions_model extends generic_fa_interface_model
```
---
