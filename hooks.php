<?php
declare(strict_types=1);

define('MENU_IMPORT', 'menu_import');
define('SS_ksf_FA_ImportStagingProcessing_UI', 110 << 8);

class hooks_ksf_FA_ImportStagingProcessing_UI extends hooks
{
    var $module_name = 'ksf_FA_ImportStagingProcessing_UI';
    var $version = '2.4.3-0';

    function install_options($app)
    {
        global $path_to_root;

        switch ($app->id) {
            case 'GL':
            case 'orders':
                $app->add_rapp_function(
                    3,
                    _('ISU Configuration'),
                    $path_to_root . '/modules/' . $this->module_name . '/pages/config.php',
                    'SA_' . $this->module_name,
                    MENU_CONFIG
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

    /**
     * @since 2.0.0
     */
    public function getModuleConstants(&$data, $opts = null)
    {
        $constants = [
            'KSF_IMPORT_STAGING_MODULE' => $this->module_name,
            'KSF_IMPORT_STAGING_VERSION' => $this->version,
        ];
        $data['constants'] = $constants;
        return $constants;
    }

    /**
     * @since 2.0.0
     */
    public function getModuleCapabilities(&$data, $opts = null)
    {
        $capabilities = [
            'staging' => [
                'description' => 'Generic staging infrastructure for importing financial transactions',
                'methods' => ['stageTransaction', 'stageCustomer', 'processTransactions'],
            ],
            'config_delegation' => [
                'description' => 'Accept source-specific config from importing modules via integration_module key',
                'methods' => ['getSourceConfig', 'setSourceConfig'],
            ],
        ];
        $data['capabilities'] = $capabilities;
        return $capabilities;
    }

    /**
     * @since 2.0.0
     */
    public function hasCapability(&$data, $opts = null)
    {
        $capability = $opts['capability'] ?? $data['capability'] ?? null;
        if ($capability === null) {
            $data['has_capability'] = false;
            $data['error'] = 'No capability specified';
            return false;
        }

        $capabilities = ['staging', 'config_delegation'];
        $hasCapability = in_array($capability, $capabilities);
        $data['has_capability'] = $hasCapability;
        $data['capability_checked'] = $capability;
        return $hasCapability;
    }

    /**
     * @since 2.0.0
     */
    public function respondToCapabilityRequest(&$data, $opts = null)
    {
        $request = $opts['request'] ?? $data['request'] ?? 'capabilities';
        $data['request'] = $request;
        $data['module'] = $this->module_name;

        switch ($request) {
            case 'capabilities':
                return $this->getModuleCapabilities($data, $opts);
            case 'constants':
                return $this->getModuleConstants($data, $opts);
            case (strpos($request, 'has:') === 0):
                $capability = substr($request, 4);
                return $this->hasCapability($data, ['capability' => $capability]);
            case 'staging:stageTransaction':
                return $this->handleStageTransaction($data, $opts);
            case 'staging:stageItem':
                return $this->handleStageItem($data, $opts);
            case 'staging:stagePayment':
                return $this->handleStagePayment($data, $opts);
            case 'staging:updateStatus':
                return $this->handleUpdateStatus($data, $opts);
            case 'staging:transactionExists':
                return $this->handleTransactionExists($data, $opts);
            default:
                $data['error'] = 'Unknown request type: ' . $request;
                return null;
        }
    }

    /**
     * Handle a stageTransaction request from an importing module.
     *
     * Builds a StagingTransactionDTO from the payload, stages via DTO method,
     * returns StagingResult as array in $data['result'].
     *
     * @since 2.0.0
     * @since 2.1.0 Uses DTO-based staging
     */
    private function handleStageTransaction(array &$data, array $opts): ?array
    {
        $transaction = $opts['transaction'] ?? $data['transaction'] ?? [];
        if (empty($transaction)) {
            $data['success'] = false;
            $data['error'] = 'No transaction data provided';
            return null;
        }

        $dto = \Ksfraser\ImportStaging\DTO\StagingTransactionDTO::fromArray($transaction);
        $service = $this->getStagingService();
        $result = $service->stageTransactionDTO($dto);

        $data['success'] = $result->isOk();
        $data['result'] = $result->toArray();
        $data['error'] = $result->getError() ? $result->getError()->getMessage() : '';

        return $data['result'];
    }

    /**
     * Handle a stageItem request from an importing module.
     *
     * @since 2.0.0
     * @since 2.1.0 Uses DTO-based staging
     */
    private function handleStageItem(array &$data, array $opts): ?array
    {
        $item = $opts['item'] ?? $data['item'] ?? [];
        if (empty($item)) {
            $data['success'] = false;
            $data['error'] = 'No item data provided';
            return null;
        }

        $dto = \Ksfraser\ImportStaging\DTO\StagingItemDTO::fromArray($item);
        $service = $this->getStagingService();
        $result = $service->stageItemDTO($dto);

        $data['success'] = $result->isOk();
        $data['result'] = $result->toArray();
        $data['error'] = $result->getError() ? $result->getError()->getMessage() : '';

        return $data['result'];
    }

    /**
     * Handle a stagePayment request from an importing module.
     *
     * @since 2.0.0
     * @since 2.1.0 Uses DTO-based staging
     */
    private function handleStagePayment(array &$data, array $opts): ?array
    {
        $payment = $opts['payment'] ?? $data['payment'] ?? [];
        if (empty($payment)) {
            $data['success'] = false;
            $data['error'] = 'No payment data provided';
            return null;
        }

        $dto = \Ksfraser\ImportStaging\DTO\StagingPaymentDTO::fromArray($payment);
        $service = $this->getStagingService();
        $result = $service->stagePaymentDTO($dto);

        $data['success'] = $result->isOk();
        $data['result'] = $result->toArray();
        $data['error'] = $result->getError() ? $result->getError()->getMessage() : '';

        return $data['result'];
    }

    /**
     * Handle an updateStatus request from an importing module.
     *
     * @since 2.0.0
     */
    private function handleUpdateStatus(array &$data, array $opts): ?array
    {
        $id = (int)($opts['id'] ?? $data['id'] ?? 0);
        $status = $opts['status'] ?? $data['status'] ?? '';
        $extra = $opts['extra'] ?? $data['extra'] ?? [];

        if ($id <= 0 || $status === '') {
            $data['success'] = false;
            $data['error'] = 'Missing id or status';
            return null;
        }

        $service = $this->getStagingService();
        $result = $service->updateStatus($id, $status, $extra);

        $data['success'] = $result['success'];
        $data['result'] = $result;
        $data['error'] = $result['error'] ?? '';

        return $result;
    }

    /**
     * Handle a transactionExists request from an importing module.
     *
     * @since 2.0.0
     */
    private function handleTransactionExists(array &$data, array $opts): ?array
    {
        $transactionId = $opts['transaction_id'] ?? $data['transaction_id'] ?? '';
        if ($transactionId === '') {
            $data['success'] = false;
            $data['error'] = 'No transaction_id provided';
            return null;
        }

        $service = $this->getStagingService();
        $exists = $service->transactionExists($transactionId);

        $data['success'] = true;
        $data['result'] = ['exists' => $exists];

        return $data['result'];
    }

    /**
     * Get or create a StagingService instance.
     *
     * @since 2.0.0
     */
    private function getStagingService(): \Ksfraser\ImportStaging\StagingService
    {
        static $service = null;
        if ($service === null) {
            $service = new \Ksfraser\ImportStaging\StagingService();
        }
        return $service;
    }
}
