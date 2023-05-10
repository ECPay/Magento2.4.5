<?php

namespace Ecpay\CreditInstallmentPaymentGateway\Model\Config\Source;

use Ecpay\General\Helper\Services\Config\PaymentService;

class CreditInstallment
{
    protected $_paymentService;

    /**
     * @param PaymentService $paymentService
     */
    public function __construct(PaymentService $paymentService)
    {
        $this->_paymentService = $paymentService;
    }

    public function toOptionArray()
    {
        $creditInstallments = $this->_paymentService->getCreditInstallments();

        // 後台多選格式
        $result = [];
        foreach ($creditInstallments as $key) {
            array_push($result, [
                'value' => $key,
                'label' => $this->_paymentService->getCreditInstallmentName($key)
            ]);
        }

        return $result;
    }
}