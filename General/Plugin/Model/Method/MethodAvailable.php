<?php

namespace Ecpay\General\Plugin\Model\Method;

use Psr\Log\LoggerInterface ;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Payment\Model\MethodList;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;

use Ecpay\General\Helper\Services\Config\MainService;
use Ecpay\General\Helper\Services\Config\LogisticService;
use Ecpay\General\Helper\Services\Config\PaymentService;

class MethodAvailable
{
    protected $_loggerInterface;

    protected $_storeManager;

    protected $_mainService;
    protected $_logisticService;
    protected $_paymentService;

    public function __construct(
        LoggerInterface $loggerInterface,
        StoreManagerInterface $storeManager,
        MainService $mainService,
        LogisticService $logisticService,
        PaymentService $paymentService
    )
    {
        $this->_loggerInterface = $loggerInterface;

        $this->_storeManager = $storeManager;

        $this->_mainService = $mainService;
        $this->_logisticService = $logisticService;
        $this->_paymentService = $paymentService;
    }

    /**
     * @param Magento\Payment\Model\MethodList $subject
     * @param $result
     * @return array
     */
    public function afterGetAvailableMethods(MethodList $subject, $result)
    {
        $objectManager = ObjectManager::getInstance();
        $cart = $objectManager->get('\Magento\Checkout\Model\Cart');
        $quote = $cart->getQuote();

        // 如果使用綠界物流，判斷貨到付款付款方式
        $cashOnDeliveryTag = 1;
        $shippingMethod = $quote->getShippingAddress()->getShippingMethod();
        $this->_loggerInterface->debug('MethodAvailable shippingMethod:'. print_r($shippingMethod,true));

        if ($this->_logisticService->isEcpayLogistics($shippingMethod)) {

            switch ($shippingMethod) {

                case 'ecpaylogisticcsvunimart_ecpaylogisticcsvunimart':
                    $group = 'ecpaylogisticcsvunimart' ;
                    break;
                case 'ecpaylogisticcsvfamily_ecpaylogisticcsvfamily':
                    $group = 'ecpaylogisticcsvfamily' ;
                    break;

                case 'ecpaylogisticcsvhilife_ecpaylogisticcsvhilife':
                    $group = 'ecpaylogisticcsvhilife' ;
                    break;

                case 'ecpaylogisticcsvokmart_ecpaylogisticcsvokmart':
                    $group = 'ecpaylogisticcsvokmart' ;
                    break;

                default:
                    $group = '' ;
                    break;
            }

            if(!empty($group)){
                $cashOnDeliveryTag = ObjectManager::getInstance()
                        ->get(ScopeConfigInterface::class)
                        ->getValue(
                            'carriers/' . $group . '/cash_on_delivery',
                            ScopeInterface::SCOPE_STORE,
                        );
            }
        }

        // 黑貓 中華郵政不允許貨到付款
        if(
            $shippingMethod == 'ecpaylogistichometcat_ecpaylogistichometcat' || 
            $shippingMethod == 'ecpaylogistichomepost_ecpaylogistichomepost' 
        ){
            $cashOnDeliveryTag = 0;
        }                   

        // 過濾顯示金流
        foreach ($result as $key => $_result) {

            // 綠界金流顯示檢查
            if ($this->_paymentService->isEcpayPayment($_result->getCode())) {
                // 金流沒有啟用時或幣別不是台幣，關閉所有綠界金流
                if (!$this->_mainService->isPaymentModuleEnable() || !$this->checkCurrencyCode()) {
                    unset($result[$key]);
                }
            }

            // 關閉貨到付款選項
            if ($cashOnDeliveryTag == 0 && $_result->getCode() == 'cashondelivery') {
                unset($result[$key]);
            }
        }

        // 使用綠界物流只能用綠界金流
        if ($this->_logisticService->isEcpayLogistics($shippingMethod)) {

            foreach ($result as $key => $_result) {

                if (
                    $this->_paymentService->isEcpayPayment($_result->getCode()) ||
                    $_result->getCode() == 'cashondelivery'
                ) {

                } else {
                    unset($result[$key]);
                }
            }
        }

        return $result;
    }

    /**
     * 幣別檢查，僅限台幣
     *
     * @return bool
     */
    private function checkCurrencyCode()
    {
        $baseCurrencyCode = $this->_storeManager->getStore()->getBaseCurrencyCode();
        $currentCurrencyCode = $this->_storeManager->getStore()->getCurrentCurrencyCode();

        if ($baseCurrencyCode !== 'TWD') {
            return false;
        }

        if ($currentCurrencyCode !== 'TWD') {
            return false;
        }

        return true;
    }
}