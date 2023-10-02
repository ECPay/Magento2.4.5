<?php

namespace Ecpay\General\Helper\Services\Config;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Store\Model\ScopeInterface;

class MainService extends AbstractHelper
{
    /**
     * 購物車名稱
     */
    public const CART_NAME = 'magento';

    /**
     * 購物車開發版本
     */
    public const CART_VERSION = 'v2.4.3-p3';

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

    /**
     * Get the module description
     *
     * @return string
     */
    public function getModuleDescription()
    {
        return 'ecpay_module_' . strtolower(self::CART_NAME . '_' . self::CART_VERSION);
    }

    /**
     *   Ex:$code=payment_order_prefix  see:/etc/system.xml
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