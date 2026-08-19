<?php

global $path_to_root;
$path_to_root = __DIR__ . "/../../../";

require_once( __DIR__ . "/class.csv_string_to_assoc_arr.php" );

//we need to interpret the file and generate a new statement for each day of transactions


/**//******************************************************************************************
* Parse the CSV
*
*       **      Mantis 3048 - Config to parse Items CSV         **
*	*202503 Square added 2 new fields.
*
**********************************************************************************************/
class ro_square_trans_csv_parser extends csv_string_to_assoc_arr 
{
	function __construct()
	{

		/**************************************************************************************************
		* We want to convert the labels in the CSV header into field names for importing into the class...
		*/
		$this->search_arr = array(
			"date",
			"time",
			"Time zone",
			"gross sales",
			"discounts",
			"service charges",
			"net sales",
			"gift card sales",
			"tax",
			"tip",
			"partial refunds",
			"total collected",
			"source",
			"card",
			"card entry methods",
			"cash",
			"square gift card",
			"other tender",
			"other tender type",
			"other tender note",
			"fees",
			"net total",
			"transaction id",
			"payment id",
			"card brand",
			"pan suffix",
			"device name",
			"staff name",
			"staff id",
			"details",
			"description",
			"event type",
			"location",
			"dining_option",
			"customer id",
			"customer name",
			"customer reference id",
			"device nickname",
			"third party fees",
			"deposit id",
			"deposit date",
			"deposit details",
			"fee percentage rate",
			"fee fixed rate",
			"refund reason",
			"discount name",
			"transaction status",
			"order reference id",
			"fulfillment note",
			"free processing applied",
			"Channel",
			"Unattributed Tips"
		);		

		$this->replace_arr = array(
			"Date",
			"Time",
			"Timezone",
			"gross_sales",
			"discounts",
			"service_charges",
			"net_sales",
			"gift_card_sales",
			"tax",
			"tip",
			"partial_refunds",
			"total_collected",
			"source",
			"card",
			"card_entry_methods",
			"cash",
			"square_gift_card",
			"other_tender",
			"other_tender_type",
			"other_tender_note",
			"fees",
			"net_total",
			"transaction_id",
			"payment_id",
			"card_brand",
			"PAN_suffix",
			"device_name",
			"staff_name",
			"staff_id",
			"details",
			"description",
			"event_type",
			"location",
			"Dining_option",
			"Customer_id",
			"customer_name",
			"customer_reference_id",
			"device_nickname",
			"third_party_fees",
			"deposit_id",
			"deposit_date",
			"deposit_details",
			"fee_percentage_rate",
			"fee_fixed_rate",
			"refund_reason",
			"discount_name",
			"transaction_status",
			"order_reference_id",
			"fulfillment_note",
			"free_processing_applied",
			"channel",
			"unattributed_tips"
		);		
		$this->sid_field = "transaction_id";
	}
		/*************************************************************
		* Square TRANS CSV Format (202408):
		*
		*	[date] => Date 
		*	[time] => Time
		*	[time_zone] => Time Zone 
		*	[gross_sales] => Gross Sales 
		*	[discounts] => Discounts 
		*	[service charges] => Service Charges 
		*	[net_sales] => Net Sales 
		*	[gift card sales] => Gift Card Sales 
		*	[tax] => Tax 
		*	[tip] => Tip 
		*	[partial refunds] => Partial Refunds 
		*	[total collected] => Total Collected 
		*	[source] => Source 
		*	[card] => Card 
		*	[card entry methods] => Card Entry Methods 
		*	[cash] => Cash 
		*	[square gift card] => Square Gift Card 
		*	[other tender] => Other Tender 
		*	[other tender type] => Other Tender Type 
		*	[other tender note] => Other Tender Note
		*	[fees] => Fees 
		*	[net total] => Net Total 
		*	[transaction_id] => Transaction ID 
		*	[payment_id] => Payment ID 
		*	[card brand] => Card Brand
		*	[pan suffix] => PAN Suffix
		*	[device_name] => Device Name 
		*	[staff name] => Staff Name 
		*	[staff id] => Staff ID 
		*	[details] => Details 
		*	[description] => Description 
		*	[event_type] => Event Type 
		*	[location] => Location 
		*	[dining_option] => Dining Option 
		*	[customer_id] => Customer ID 
		*	[customer_name] => Customer Name 
		*	[customer_reference_id] => Customer Reference ID 
		*	[device nickname] => Device Nickname 
		*	[third party fees] => Third Party Fees 
		*	[deposit id] => Deposit ID 
		*	[deposit date] => Deposit Date 
		*	[deposit details] => Deposit Details 
		*	[fee percentage rate] => Fee Percentage Rate 
		*	[fee fixed rate] => Fee Fixed Rate 
		*	[refund reason] => Refund Reason 
		*	[discount name] => Discount Name 
		*	[transaction status] => Transaction Status 
		*	[order reference id] => Order Reference ID 
		*	[fulfillment_note] => Fulfillment Note 
		*	[free processing applied] => Free Processing Applied
		************************************************************/
	/**//**************************************************
	*
        * Parse a CSV
        *
        * @param string contents from file_get_contents
        * @param array static_data (currently unused)
        * @param bool debugging turned on or off
        * @return array
	******************************************************/
	function parse( $content, $static = array(), $debug = false )
	{
		//display_notification( __FILE__ . "::" . __LINE__ . "::" );
		return parent::parse( $content, $static, $debug );
	}

}

