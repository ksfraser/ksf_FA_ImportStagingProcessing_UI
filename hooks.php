<?php
declare(strict_types=1);

define('MENU_IMPORT', 'menu_import');
define('SS_ksf_FA_ImportStagingProcessing_UI', 110 << 8);

class hooks_ksf_FA_ImportStagingProcessing_UI extends hooks
{
    var $module_name = 'ksf_FA_ImportStagingProcessing_UI';

    function install_options($app)
    {
        global $path_to_root;

        switch ($app->id) {
            case 'GL':
            case 'orders':
                $app->add_lapp_function(
                    3,
                    _('Staging Import'),
                    $path_to_root . '/modules/' . $this->module_name . '/import_square.php',
                    'SA_' . $this->module_name
                );
                $app->add_rapp_function(
                    3,
                    _('Import Square Transactions'),
                    $path_to_root . '/modules/' . $this->module_name . '/import_statements.php',
                    'SA_' . $this->module_name,
                    MENU_MAINTENANCE
                );
                $app->add_rapp_function(
                    3,
                    _('Process Staging'),
                    $path_to_root . '/modules/' . $this->module_name . '/process_statements.php',
                    'SA_' . $this->module_name,
                    MENU_IMPORT
                );
                break;
        }
    }

    function activate_extension($company, $check_only = true)
    {
        return true;
    }

    function install_access()
    {
        $SS = SS_ksf_FA_ImportStagingProcessing_UI;
        $security_sections[$SS] = _('Import Staging Processing UI');
        $security_areas['SA_' . $this->module_name] = [
            $SS | 1,
            _('Import Staging Processing UI'),
        ];
        $security_areas['SA_' . $this->module_name . 'VIEW'] = [
            $SS | 2,
            _('View Import Staging'),
        ];
        $security_areas['SA_' . $this->module_name . 'MANAGE'] = [
            $SS | 4,
            _('Manage Import Staging'),
        ];
        return [$security_areas, $security_sections];
    }

    function hook_invoke_all($hook, &$data)
    {
        $autoload = __DIR__ . '/vendor/autoload.php';
        if (file_exists($autoload)) {
            require_once $autoload;
        }
        return parent::hook_invoke_all($hook, $data);
    }
}
