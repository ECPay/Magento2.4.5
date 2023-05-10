<?php

namespace Ecpay\General\Helper\Services\Config;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Store\Model\ScopeInterface;

class MainService extends AbstractHelper
{
    public function getConfigValue($field, $storeId = null)
    {
        return $this->scopeConfig->getValue(
            $field, ScopeInterface::SCOPE_STORE, $storeId
        );
    }

    public function getMainConfig($code, $storeId = null)
    {
        $configPath = 'main_config/main/' ;
        return $this->getConfigValue($configPath . $code, $storeId);
    }

    /*
        Ex:$code=payment_order_prefix  see:/etc/system.xml
    */
    public function getPaymentConfig($code, $storeId = null)
    {
        $configPath = 'payment_config/payment/' ;
        return $this->getConfigValue($configPath . $code, $storeId);
    }

    public function getLogisticConfig($code, $storeId = null)
    {
        $configPath = 'logistic_config/logistic/' ;
        return $this->getConfigValue($configPath . $code, $storeId);
    }

    public function getInvoiceConfig($code, $storeId = null)
    {
        $configPath = 'invoice_config/invoice/' ;
        return $this->getConfigValue($configPath . $code, $storeId);
    }

    public function getPaymentModuleConfig($configPath, $code, $storeId = null)
    {
        return $this->getConfigValue($configPath .'/'. $code, $storeId);
    }

    /**
     * 金流模組是否啟動
     *
     * @return bool
     */
    public function isPaymentModuleEnable()
    {
        return $this->getMainConfig('ecpay_enabled_payment');
    }

    /**
     * 物流模組是否啟動
     *
     * @return bool
     */
    public function isLogisticModuleEnable()
    {
        return $this->getMainConfig('ecpay_enabled_logistic');
    }

    /**
     * 發票模組是否啟動
     *
     * @return bool
     */
    public function isInvoiceModuleEnable()
    {
        return $this->getMainConfig('ecpay_enabled_invoice');
    }
}