<?php

global $path_to_root;
$path_to_root = __DIR__ . "/../../../";

require_once( __DIR__ . "/class.csv_string_to_assoc_arr.php" );

//we need to interpret the file and generate a new statement for each day of items

/**//******************************************************************************************
* Parse the CSV
*
*	** 	Mantis 3053 - Config to parse Items CSV		**
*
**********************************************************************************************/
class ro_square_items_csv_parser extends csv_string_to_assoc_arr 
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
			"category",		
			"item",		
			"qty",		
			"price point name",		
			"sku",		
			"modifiers applied",		
			"gross sales",		
			"discounts",		
			"net sales",		
			"tax",		
			"transaction id",		
			"payment id",		
			"device name",		
			"notes",		
			"details",		
			"event type",		
			"location",		
			"dining option",		
			"customer id",		
			"customer name",		
			"customer reference id",		
			"unit",		
			"count",		
			"itemization type",		
			"fulfillment note",		
			"token" 
		);		

		$this->replace_arr = array(
			"Date",		
			"Time",		
			"Timezone",		
			"Category",		
			"Item",		
			"quantity",		
			"Price_Point_Name",		
			"stock_id",		
			"modifiers_applied",		
			"gross_sales",		
			"discounts",		
			"net_sales",		
			"tax",		
			"transaction_id",		
			"payment_id",		
			"device_name",		
			"notes",		
			"details",		
			"event_type",		
			"location",		
			"dining_option",		
			"Customer_id",		
			"customer_name",		
			"customer_reference_id",		
			"unit",		
			"count",		
			"itemization_type",		
			"fulfillment_note",		
			"token"
		);		
		$this->sid_field = "transaction_id";
	}
		/*************************************************************
		* Square ITEMS CSV Format (202408):
		*
		*	[date] => Date 
		*	[time] => Time 
		*	[time_zone] => Time Zone 
		*	[category] => Category 
		*	[item] => Item 
		*	[qty] => Qty 
		*	[price_point_name] => Price Point Name 
		*	[sku] => SKU 
		*	[modifiers_applied] => Modifiers Applied 
		*	[gross_sales] => Gross Sales 
		*	[discounts] => Discounts 
		*	[net_sales] => Net Sales 
		*	[tax] => Tax 
		*	[transaction_id] => Transaction ID 
		*	[payment_id] => Payment ID 
		*	[device_name] => Device Name 
		*	[notes] => Notes 
		*	[details] => Details 
		*	[event_type] => Event Type 
		*	[location] => Location 
		*	[dining_option] => Dining Option 
		*	[customer_id] => Customer ID 
		*	[customer_name] => Customer Name 
		*	[customer_reference_id] => Customer Reference ID 
		*	[unit] => Unit 
		*	[count] => Count 
		*	[itemization_type] => Itemization Type 
		*	[fulfillment_note] => Fulfillment Note 
		*	[token] => Token 
		************************************************************/
	/**//**************************************************
	*
        * Import a CSV
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
