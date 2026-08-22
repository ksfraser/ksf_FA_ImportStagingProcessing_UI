# Design: ISU Staging Hook Architecture

> **Module:** ksf_FA_ImportStagingProcessing_UI
> **Status:** Draft
> **Date:** 2026-08-21

---

## 1. Problem Statement

ISU currently contains Square-specific legacy code (models, parsers, entry points) that should not live in a generic staging module. Importing modules (Square, Woo) bypass ISU's staging logic by inserting directly into their own tables or using incomplete hooks.

**Goals:**
1. ISU becomes a **pure generic staging backend** -- no source-specific code
2. Importing modules call ISU via `hook_invoke` with **typed DTOs**
3. ISU performs **validation, deduplication, and sanity checking** on every insert
4. ISU returns **structured result DTOs** so callers know exactly what happened
5. ISU retains its Process Transactions/Payments/Deposits review screens

---

## 2. Architecture

```
Square Module (CSV/API)
    |
    |  hook_invoke('ksf_FA_ImportStagingProcessing_UI', 'staging:stageTransaction', $data)
    |  hook_invoke('ksf_FA_ImportStagingProcessing_UI', 'staging:stageItem', $data)
    |  hook_invoke('ksf_FA_ImportStagingProcessing_UI', 'staging:stagePayment', $data)
    v
+------------------------------------------+
|  ISU StagingService                      |
|  1. Build DTO from payload               |
|  2. Validate (required fields, formats)  |
|  3. Deduplicate (transaction_id check)   |
|  4. Compare existing (if found)          |
|  5. INSERT or UPDATE                     |
|  6. Return StagingResult                 |
+------------------------------------------+
    |
    |  StagingResult::inserted(id)
    |  StagingResult::updated(id)
    |  StagingResult::duplicate(id)
    |  StagingResult::error(StagingError)
    v
Calling Module handles result
```

---

## 3. DTOs

All DTOs in `src/DTO/` under `Ksfraser\ImportStaging\DTO`.

### 3.1 StagingTransactionDTO

Maps 1:1 to `0_ksf_import_square_transactions` columns. Source modules populate from their API/CSV data.

**Required field:** `sourceTransactionId` (maps to `transaction_id` in the table).

**Fields:** source, date, time, timezone, grossSales, discounts, serviceCharges, giftCardSales, netSales, tax, tip, partialRefunds, totalCollected, source (tender), card, cardEntryMethods, cash, squareGiftCard, otherTender, otherTenderType, otherTenderNote, fees, netTotal, transactionId, paymentId, cardBrand, panSuffix, deviceName, staffName, staffId, description, details, eventType, location, diningOption, customerId, customerName, customerReferenceId, deviceNickname, thirdPartyFees, depositId, depositDate, depositDetails, feePercentageRate, feeFixedRate, refundReason, discountName, transactionStatus, orderReferenceId, fulfillmentNote, freeProcessingApplied, rawJson.

**API:**
- `static fromArray(array $data): self`
- `toArray(): array`
- Individual getters (camelCase)
- Fluent `with*()` setters for each field

### 3.2 StagingItemDTO

Maps 1:1 to `0_ksf_import_square_items` columns.

**Required field:** `transactionId` (links to parent transaction).

**Fields:** date, time, timezone, category, item, pricePointName, stockId, modifiersApplied, quantity, grossSales, discounts, netSales, tax, paymentId, deviceName, notes, details, eventType, location, diningOption, customerId, customerName, customerReferenceId, unit, count, itemizationType, fulfillmentNote, rawJson.

### 3.3 StagingPaymentDTO

Maps to `0_ksf_import_square_payments` columns.

**Required field:** `paymentId` (maps to `square_payment_id`).

**Fields:** totalCollected, transType, transNo.

### 3.4 StagingResult

Returned by every staging operation.

```php
class StagingResult
{
    const STATUS_INSERTED  = 'inserted';
    const STATUS_UPDATED   = 'updated';
    const STATUS_DUPLICATE = 'duplicate';
    const STATUS_ERROR     = 'error';
}
```

**Factory methods:**
- `StagingResult::inserted(int $id): self`
- `StagingResult::updated(int $id): self`
- `StagingResult::duplicate(int $id): self`
- `StagingResult::error(StagingError $error): self`

### 3.5 StagingError

Field-level errors with error code, message, field name, and context.

**Error codes:**
- `STAGE_MISSING_REQUIRED` -- required field empty/null
- `STAGE_INVALID_FORMAT` -- value doesn't match expected format
- `STAGE_DUPLICATE` -- identical data already staged
- `STAGE_DIFF_DUPLICATE` -- exists but differs (results in update)
- `STAGE_DB_ERROR` -- database insert/update failed
- `STAGE_VALIDATION_FAILED` -- business rule violation

---

## 4. Hook Protocol

| Hook Request | Payload | Returns |
|---|---|---|
| `staging:stageTransaction` | `['transaction' => [...fields]]` | `StagingResult` array |
| `staging:stageItem` | `['item' => [...fields]]` | `StagingResult` array |
| `staging:stagePayment` | `['payment' => [...fields]]` | `StagingResult` array |
| `staging:updateStatus` | `['id' => int, 'status' => string]` | `['success' => bool]` |
| `staging:transactionExists` | `['transaction_id' => string]` | `['exists' => bool]` |

### Calling Convention

```php
$data = ['transaction' => $dto->toArray()];
hook_invoke('ksf_FA_ImportStagingProcessing_UI', 'staging:stageTransaction', $data);

// $data['result'] contains StagingResult::toArray()
$status = $data['result']['status']; // 'inserted'|'updated'|'duplicate'|'error'
$id     = $data['result']['id'];     // staging row ID
$error  = $data['result']['error'];  // StagingError array or null
```

---

## 5. Processing Pipeline

### 5.1 Stage Transaction

1. Receive hook call with DTO payload
2. Build StagingTransactionDTO from payload
3. **Validate:** sourceTransactionId non-empty; date format valid
4. **Deduplicate:** SELECT by transaction_id
   - Exists + identical fields -> return `duplicate`
   - Exists + fields differ -> UPDATE, return `updated`
   - Not found -> INSERT, return `inserted`
5. On exception -> return `error` with StagingError

### 5.2 Stage Item

- Links to parent via `transaction_id`
- Does NOT deduplicate (multiple items per transaction)
- Always inserts (items are additive)

### 5.3 Stage Payment

- Deduplicates on `square_payment_id` (UNIQUE index)
- Exists -> return `duplicate`
- Not found -> INSERT

---

## 6. Menu Changes (ISU hooks.php)

**Remove:**
- "Staging Import" -> `ksf_import_square.php` (the legacy Import Files entry point)

**Retain:**
- "Import Transactions" -> `import_statements.php`
- "Process Staging" -> `process_statements.php`

**Add:**
- "Process Transactions" -> `process_statements.php` (staged transactions from `0_ksf_import_square_transactions`)
- "Process Payments" -> new entry point wrapping `process_statements_form()` logic
- "Process Deposits" -> new entry point wrapping `process_deposits_form()` logic

---

## 7. Migration Path

### Phase 1 (this iteration): DTOs + Hook Protocol
1. Create `src/DTO/StagingTransactionDTO.php`
2. Create `src/DTO/StagingItemDTO.php`
3. Create `src/DTO/StagingPaymentDTO.php`
4. Create `src/DTO/StagingResult.php`
5. Create `src/DTO/StagingError.php`
6. Update `src/StagingService.php` to accept DTOs and return StagingResult
7. Update `hooks.php` to route through StagingService with DTOs
8. Remove "Staging Import" menu
9. Write unit tests

### Phase 2: Square Module Integration
1. Build DTOs from Square API response in ImportService
2. Call ISU hooks with DTOs instead of direct SQL
3. Handle StagingResult responses

### Phase 3: Woo Module Integration
1. Build DTOs from Woo REST payload in OrderStaging
2. Call ISU hooks with DTOs instead of direct SQL
3. Handle StagingResult responses
4. Remove `woo_order_staging` fallback table

### Phase 4: Legacy Cleanup
1. Remove Square-specific files from ISU module
2. Move entry points to ksf_FA_Square
