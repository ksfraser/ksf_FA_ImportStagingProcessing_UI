ksf_import_square
===========

FA import square

The aim of this module is to be able import and process Square Transactions into FrontAccounting.

Using import_paypal and bank_import as starting points. 

As of 20250304 Square produces 3 CSVs for download regarding transactions:
- transactions-YYYMMDD.csv
- items-XX.csv
- items-sales-summary-XX.csv

Transactions contains multiple parts of data.  1 line per transaction
*payment or payments collected from a customer
**splits possible - Cash, card, gift cards.  Tax, tip, partial refunds.
**details about cards and the staff that took the payment.
**customer details if known.
**Transaction ID
*Bank Transfer from Square to business
**deposit date, details, fee percentage, fee fixed amount, fees deducted.
**Payment ID

Items contains details about the items sold.  This is 1 line per SKU.  Multiple lines for a given transaction
*Date/time/zone
*Category, item, qty, pricebooks, sku
*gross sale, discount, net sale, tax, transaction ID, Payment ID
*Customer details if known

Item Sale Summary.  One line per sku.  A summary.  Could be compared against Inventory Movement reports, IF Square is the only other source of info.
*Description, Sku, category
*total items sold for time period
*gross, refunds, discounts, net, tax



INSTALLATION
------------
1. Extract the archive or copy all files into /modules/ksf_import_square folder.
2. Install/activate module from FrontAccount as you do with other modules
3. after installation, 4 new menu links will appear into "Banking and General Ledger" section:
- Transaction/Process Bank Statements
- Inquiry/Bank Statemens Inquiry
- Maintenance/Import Bank Statements
- Maintenance/Manage Partners Bank Accounts

USAGE
-----
1. import a transactions file using the Import Square Transactions link
- select the correct format for your file
- check the output for any errors

2. process each transaction with Process Square link
- you will be presented a list of transactions with all the transaction details
- you have the option to process each transaction as 
- - a Customer Deposit, 
- - a Supplier payment, 
- - Manual settlement  or 
- - a Quick Entry (you will have to define Quick Entries as needed)
- after pressing "process", the transaction will be recorded into FA and the square transaction will be marked as "settled"
- if some human error occurs, by voiding the FA transaction, the corresponding bank transaction is "unsettled" as well and becomes "processable" again


FOR DEVELOPERS
--------------
The module has two parts:
- a square CSV parser and importer
- the required frontend screens for transaction processing



