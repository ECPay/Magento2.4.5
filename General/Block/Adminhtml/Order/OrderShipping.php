<?php

namespace Ecpay\General\Block\Adminhtml\Order;

use Ecpay\General\Helper\Services\Common\EncryptionsService;
use Ecpay\General\Helper\Services\Common\OrderService;
use Ecpay\General\Helper\Services\Config\MainService;
use Ecpay\General\Helper\Services\Config\LogisticService;
use Magento\Backend\Block\Template\Context;
use Magento\Framework\Registry;
use Magento\Sales\Block\Adminhtml\Order\AbstractOrder;
use Magento\Sales\Helper\Admin;
use Psr\Log\LoggerInterface ;

class OrderShipping extends AbstractOrder
{
    protected $_loggerInterface;

    protected $_encryptionsService;
    protected $_orderService;
    protected $_mainService;
    protected $_logisticService;

    protected $orderId;

    public function __construct(
        LoggerInterface $loggerInterface,

        Context $context,
        Registry $registry,
        Admin $adminHelper,

        EncryptionsService $encryptionsService,
        OrderService $orderService,
        MainService $mainService,
        LogisticService $logisticService,

        array $data = []
    ){
        parent::__construct($context, $registry, $adminHelper, $data);

        $this->_loggerInterface = $loggerInterface;

        $this->_encryptionsService = $encryptionsService;
        $this->_orderService = $orderService;
        $this->_mainService = $mainService;
        $this->_logisticService = $logisticService;

        $this->orderId = (int) $this->getOrder()->getId();
        $this->_loggerInterface->debug('OrderShipping orderId:'. print_r($this->orderId, true));
    }

    /**
     * 物流模組是否啟動
     *
     * @return bool
     */
    public function getLogisticModuleEnable()
    {
        return $this->_mainService->getMainConfig('ecpay_enabled_logistic');
    }

    public function getTitle()
    {
        return 'Shipping Method';
    }

    /**
     * 取得加密資料
     *
     * @return string $encryptData
     */
    public function getEncryptData()
    {
        $encryptData = [
            'order_id'     => $this->_encryptionsService->encrypt($this->orderId),
            'protect_code' => $this->_orderService->getProtectCode($this->orderId),
        ];

        return json_encode($encryptData);
    }

    /**
     * 取得物流資料
     *
     * @return string
     */
    public function getShippingData()
    {
	    // 物流方式
        $shippingMethod = $this->_orderService->getShippingMethod($this->orderId);
        $this->_loggerInterface->debug('OrderShipping $shippingMethod:'. print_r($shippingMethod, true));

        $logisticData = [];
        if ($this->_logisticService->isEcpayLogistics($shippingMethod)) {

            // 是否已開立物流訂單
            $ecpayShippingTag = $this->_orderService->getEcpayShippingTag($this->orderId);
            $this->_loggerInterface->debug('OrderShipping $ecpayShippingTag:'. print_r($ecpayShippingTag, true));

            // 已開立物流訂單
            if ($ecpayShippingTag === '1') {
                // 取得綠界物流單資訊
                $ecpayLogisticInfo = $this->_orderService->getEcpayLogisticInfo($this->orderId);
                $this->_loggerInterface->debug('OrderShipping $ecpayLogisticInfo:'. print_r($ecpayLogisticInfo, true));

                // 撈取物流資料
                $logisticData = [
                    'merchant_trade_no'    => $ecpayLogisticInfo['merchant_trade_no'],
                    'all_pay_logistics_id' => $ecpayLogisticInfo['all_pay_logistics_id'],
                    'logistics_type'       => $ecpayLogisticInfo['logistics_type'],
                ];

                // 判斷物流種類
                $this->_loggerInterface->debug('OrderShipping $isEcpayCvsLogistics:'. print_r($this->_logisticService->isEcpayCvsLogistics($shippingMethod), true));

                if ($this->_logisticService->isEcpayCvsLogistics($shippingMethod)) {
                    // 超商
                    $extension = [
                        'cvs_store_id'        => $this->_orderService->getEcpayLogisticCvsStoreId($this->orderId),
                        'cvs_store_name'      => $this->_orderService->getEcpayLogisticCvsStoreName($this->orderId),
                        'cvs_payment_no'      => $ecpayLogisticInfo['cvs_payment_no']
                    ];
                } elseif ($this->_logisticService->isEcpayHomeLogistics($shippingMethod)) {
                    // 宅配
                    $extension = [
                        'booking_note' => $ecpayLogisticInfo['booking_note']
                    ];
                }
            } else {
                if ($this->_logisticService->isEcpayCvsLogistics($shippingMethod)) {
                    // 超商
                    $extension = [
                        'cvs_store_id'        => $this->_orderService->getEcpayLogisticCvsStoreId($this->orderId),
                        'cvs_store_name'      => $this->_orderService->getEcpayLogisticCvsStoreName($this->orderId)
                    ];
                } elseif ($this->_logisticService->isEcpayHomeLogistics($shippingMethod)) {
                    // 宅配
                    $extension = [];
                }
            }
            $this->_loggerInterface->debug('OrderShipping $extension:'. print_r($extension, true));
            $logisticData = array_merge($logisticData, $extension);
        }

        return json_encode($logisticData);
    }
}




