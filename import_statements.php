<?php
/**********************************************************************
***********************************************************************/
$page_security = 'SA_BANKACCOUNT';
$path_to_root = __DIR__ . "/../..";
include($path_to_root . "/includes/session.inc");

include_once($path_to_root . "/includes/date_functions.inc");
include_once($path_to_root . "/includes/ui.inc");

include_once( __DIR__ . "/includes/parsers.inc");

require_once( "class.ksf_import_square_transactions.php" );
require_once( "class.ksf_import_square_items.php" );
require_once( "class.ksf_import_square_sale.php" );

/*********
*	TODO
*	This process DOES NOT recognize re-uploading a file
*	Since this inserts the data on upload, we could end up with duplicate entries.
*	We can't put a unique index on the tables since we'd have to basically include ALL fields
*	  since you could have multiple lines with the same product, price in the same transaction.
*		Maybe square merges them all together if there is 1 price...but different prices
*		would certainly have separate lines.
*	This could also lead to issues once we edit the customer and then reload - defualt customer
*	would then look like a new transaction even if it is a duplicate.
*	
*	Can't use just timestamps either because we could have multiple tills running at once.
*	
*	Not sure what uniqueness square forces on device names, employee names, etc.  Can you
*	be logged in multiple times at the same time?
*
*	Transaction ID should be unique in the transactions table.  We could check that for the existance
*	before inserting into Items, and insert into transactions once we've inserted into items.  Use
*	a MYSQL transaction and rollback if both tables not touched...
*/

require_once( 'class.import_statements_gui.php' );
//require_once( 'class.import_statements_controller.php' );
require_once( '../ksf_modules_common/class.parse_uploaded_files.php' );


// select changed
if (get_post('_parser_update')) {
	$Ajax->activate('doc_tbl');
}

	$gui = new import_statements_gui();
	$gui->display();
start_form(true);

//var_dump( $_POST );
//var_dump( $_FILES );


if (empty($_POST['upload']) && empty($_POST['import'])) {
    	$gui->do_upload_form();
}


//if upload is hit, parse the files and store result in table
if (@$_POST['upload'] && ($_FILES['files']['error'][0] == 0)) {
 	$puf = new parse_uploaded_files( $gui );
	//We have transaction_id in both Square CSV file types.
	$puf->process_upload( "transaction_id" );
}

if( @$_POST['import'] )
{
	//var_dump( $_POST );
	//var_dump( $_SESSION );
	// ["import"]=> string(11) "Insert Data" ["parser"]=> string(19) "ro_square_trans_csv" ["_focus"]=> string(6) "parser" ["_modified"]=> string(1) "0" ["_token"]=> string(64) "663f105aa2fd21b21631107342033fe1bfa6b6c383649daa4f12bb2e1fde4596" 
	$classname = $_POST['parser'] . "_model";
	$filename = "class." . $classname . ".php";
	if( @require_once( $filename ) )
	{
		$imp = new $classname();
		$statements = unserialize( $_SESSION['statements'] );
		foreach( $statements as $statement )
		{
			foreach( $statement as $line )
			{
				try {
					//In this case, there should only be 1 line per statement.  Unlike the items CSV which will have 1+ line per SKU in the transaction.
					$imp->arr2obj( $line );
					$imp->insert_transaction();
				}
				catch( Exception $e )
				{
					display_error( __FILE__ . "::" . __LINE__ . ":: " . $e->getMessage() );
					display_notification( __FILE__ . "::" . __LINE__ . ":: " . print_r( $statement, true ) );
					display_notification( __FILE__ . "::" . __LINE__ . ":: " . print_r( $imp, true ) );
				}
			}
		}
	}
	else
	{
		display_error( $filename . " doesn't exist" );
	}
}



end_page();
?>
