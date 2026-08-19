<?php

/*****************************************************
*
*       **      MANTIS 3043 - CSV Parser        **
*
*****************************************************/

/**
*	TODO
*		Mantis 3058 - Incorporate my "modules" loading stuff to build the array returned by getParsers by scanning a directory for parsers.
*/

include_once( __DIR__ . "/parser.php");
include_once( __DIR__  . "/ro_square_trans_csv.php");
include_once( __DIR__  . "/ro_square_items_csv.php");

function getParsers() {
    return array(
	'ro_square_trans_csv' => array('name' => 'Square Transactions', 'class' => 'ksf_import_square_transactions_model', 'select' => array('customer' => 'Select default customer')),
	'ro_square_items_csv' => array('name' => 'Square Items', 'class' => 'ksf_import_square_items_model', 'select' => array('customer' => 'Select default customer')),
	);
}

?>
