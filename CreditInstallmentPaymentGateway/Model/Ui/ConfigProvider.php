<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Ecpay\CreditInstallmentPaymentGateway\Model\Ui;

use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Framework\App\ObjectManager;

use Ecpay\General\Helper\Services\Config\PaymentService;

/**
 * Class ConfigProvider
 */
final class ConfigProvider implements ConfigProviderInterface
{
    const CODE = 'ecpay_credit_installment_gateway';

    protected $_paymentService;

    /**
     * @param PaymentService $paymentService
     */
    public function __construct(PaymentService $paymentService)
    {
        $this->_paymentService = $paymentService;
    }

    /**
     * Retrieve assoc array of checkout configuration
     *
     * @return array
     */
    public function getConfig()
    {
        $result = [];
        $validCreditInstallments = $this->_paymentService->getValidCreditInstallments();
        foreach ($validCreditInstallments as $key) {
            $result[$key] = $this->_paymentService->getCreditInstallmentName($key);

            // 圓夢分期檢查
            if ($key == 'credit_30N' && !$this->checkCreditInstallments()) {
                unset($result[$key]);
            }
        }

        return [
            'payment' => [
                self::CODE => [
                    'ecpayCreditInstallments' => $result
                ]
            ]
        ];
    }

    /**
     * 檢查期數限制
     *
     * @return bool
     */
    private function checkCreditInstallments()
    {
        // 圓夢分期有金額的限制，訂單金額需 > 2w
        $objectManager = ObjectManager::getInstance();
        $cart = $objectManager->get('\Magento\Checkout\Model\Cart');
        $grandTotal  = $cart->getQuote()->getGrandTotal();

        return ($grandTotal > 20000);

    }
}
