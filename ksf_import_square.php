<?php
$page_security = 'SA_ksf_FA_ImportStagingProcessing_UI';
$path_to_root = "../..";

include($path_to_root . "/includes/session.inc");
add_access_extensions();
set_ext_domain('modules/ksf_FA_ImportStagingProcessing_UI');

include_once($path_to_root . "/includes/ui.inc");
include_once($path_to_root . "/includes/data_checks.inc");

error_reporting(E_ALL);
ini_set("display_errors", "on");

global $db;
$debug_sql = 0;

include_once("class.ksf_import_square.php");
require_once('ksf_import_square.inc.php');

$coastc = new ksf_import_square(KSF_IMPORT_SQUARE_PREFS);
$found = $coastc->is_installed();
$coastc->set_var('found', $found);
$coastc->set_var('help_context', "Import Staging");
$coastc->set_var('redirect_to', "ksf_import_square.php");
$coastc->run();
