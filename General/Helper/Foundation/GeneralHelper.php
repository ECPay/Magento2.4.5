<?php

namespace Ecpay\General\Helper\Foundation;

use Magento\Framework\App\DeploymentConfig;
use Magento\Framework\App\Helper\AbstractHelper;

class GeneralHelper extends AbstractHelper
{
    public const CONFIG_PATH_ECPAY_GENERAL  = 'ecpay/general';
    public const CONFIG_PATH_ECPAY_PAYMENT  = 'ecpay/payment';
    public const CONFIG_PATH_ECPAY_LOGISTIC = 'ecpay/logistic';
    public const CONFIG_PATH_ECPAY_INVOICE  = 'ecpay/invoice';

    /**
     * Deployment configuration
     *
     * @var DeploymentConfig
     */
    protected $_deploymentConfig ;

    /**
     * @param DeploymentConfig $deploymentConfig
     */
	public function __construct(DeploymentConfig $deploymentConfig) {
		$this->_deploymentConfig = $deploymentConfig;
	}

    /**
     * 取 env.php ecpay/general 資料
     *
     * @param  string $path
     * @return mixed
     */
    public function getEcpayGeneralConfigData(string $path)
    {
        return $this->_deploymentConfig->get(self::CONFIG_PATH_ECPAY_GENERAL . $path);
    }

    /**
     * 取 env.php ecpay/payment 資料
     *
     * @param  string $path
     * @return mixed
     */
    public function getEcpayPaymentConfigData(string $path)
    {
        return $this->_deploymentConfig->get(self::CONFIG_PATH_ECPAY_PAYMENT . $path);
    }

    /**
     * 取 env.php ecpay/logistic 資料
     *
     * @param  string $path
     * @return mixed
     */
    public function getEcpayLogisticConfigData(string $path)
    {
        return $this->_deploymentConfig->get(self::CONFIG_PATH_ECPAY_LOGISTIC . $path);
    }

    /**
     * 取 env.php ecpay/invoice 資料
     *
     * @param  string $path
     * @return mixed
     */
    public function getEcpayInvoiceConfigData(string $path)
    {
        return $this->_deploymentConfig->get(self::CONFIG_PATH_ECPAY_INVOICE . $path);
    }

    /**
     * 從 env.php 中取出 KEY、IV
     *
     * @return array
     */
    public function getEncryKeyIV()
    {
        $info = [
            'key' => $this->getEcpayGeneralConfigData('/hash_key'),
            'iv'  => $this->getEcpayGeneralConfigData('/hash_iv'),
        ] ;

        return $info;
    }

    /**
     * Generate a more truly "random" alpha-numeric string.
     *
     * @param  int    $length
     * @return string
     */
    public function random($length = 16)
    {
        $string = '';

        while (($len = strlen($string)) < $length) {
            $size = $length - $len;

            $bytes = random_bytes($size);

            $string .= substr(str_replace(['/', '+', '='], '', base64_encode($bytes)), 0, $size);
        }

        return $string;
    }
}