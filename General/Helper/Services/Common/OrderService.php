<?php

namespace Ecpay\General\Helper\Services\Common;

use Psr\Log\LoggerInterface;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\Service\InvoiceService;
use Magento\Framework\DB\Transaction;

use Magento\Sales\Model\Convert\Order;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory;
use Magento\Sales\Model\OrderFactory;
use Magento\Shipping\Model\ShipmentNotifier;

use Ecpay\General\Model\EcpayLogisticFactory;
use Ecpay\General\Model\EcpayPaymentInfoFactory;

class OrderService extends AbstractHelper
{
    protected $_loggerInterface;
    protected $_orderInterface;
    protected $_ecpayLogisticFactory;
    protected $_ecpayPaymentInfoFactory;
    protected $_invoiceService;
    protected $_transaction;

    protected $_orderCollectionFactory;
    protected $_convertOrder;
    protected $_orderFactory;
    protected $_shipmentNotifier;

    public function __construct(
        InvoiceService $invoiceService,
        Transaction $transaction,
        LoggerInterface $loggerInterface,
        OrderInterface $orderInterface,
        EcpayLogisticFactory $ecpayLogisticFactory,
        EcpayPaymentInfoFactory $ecpayPaymentInfoFactory,
        CollectionFactory $orderCollectionFactory,
        Order $convertOrder,
        OrderFactory $orderFactory,
        ShipmentNotifier $shipmentNotifier
    )
    {
        $this->_loggerInterface = $loggerInterface;
        $this->_orderInterface = $orderInterface;
        $this->_ecpayLogisticFactory = $ecpayLogisticFactory;
        $this->_ecpayPaymentInfoFactory = $ecpayPaymentInfoFactory;
        $this->_invoiceService = $invoiceService;
        $this->_transaction = $transaction;

        $this->_orderCollectionFactory = $orderCollectionFactory;
        $this->_convertOrder = $convertOrder;
        $this->_orderFactory = $orderFactory;
        $this->_shipmentNotifier = $shipmentNotifier;
    }

    /**
     * 訂單編號組合
     * @param  string  $orderId
     * @param  string  $prefix
     * @return string
     */
    public function getMerchantTradeNo($orderId, $prefix = '')
    {
        $merchantTradeNo = $prefix . substr(str_pad($orderId, 8, '0', STR_PAD_LEFT), 0, 8) . 'SN' . substr(hash('sha256', (string) time()), -5) ;
        return substr($merchantTradeNo, 0, 20);
    }

    /**
     * 利用Payment MerchantTradeNo取得訂單資訊
     * @param  string  $merchantTradeNo
     * @return array
     */
    public function getOrderIdByPaymentMerchantTradeNo($merchantTradeNo)
    {
        $info = [];

        $orderModel = $this->_orderFactory->create();

        $collection =  $orderModel
                     ->getCollection()
                     ->addFieldToFilter('ecpay_payment_merchant_trade_no', ['eq' => $merchantTradeNo])
                     ->setOrder('entity_id','DESC')
                     ->setCurPage(1)
                     ->setPageSize(1);

        foreach($collection as $item){
            $info = $item->getData() ;
        }

        return $info;
    }

    /**
     * 取得可執行自動開立發票程序的訂單編號
     * @return array $ids
     */
    public function getOrderForInvoiceAutoProcedure()
    {
        $ids = [];
        $collection = $this->_orderCollectionFactory->create()
            ->addAttributeToSelect('entity_id')
            ->addFieldToFilter('ecpay_invoice_auto_tag',  ['eq' => 1])
            ->addFieldToFilter('ecpay_invoice_tag', ['eq' => 0]);

        foreach($collection as $item){
            $ids[] = $item->getData('entity_id') ;
        }

        return $ids;
    }

    /**
     * 取得可執行自動開立物流訂單程序的訂單編號
     * @return array $ids
     */
    public function getOrderForLogisticAutoProcedure()
    {
        $ids = [];
        $collection = $this->_orderCollectionFactory->create()
            ->addAttributeToSelect('entity_id')
            ->addFieldToFilter('ecpay_logistic_auto_tag',  ['eq' => 1])
            ->addFieldToFilter('ecpay_shipping_tag', ['eq' => 0]);

        foreach($collection as $item){
            $ids[] = $item->getData('entity_id') ;
        }

        return $ids;
    }

    /**
     * 取得訂單
     * @param  string  $orderId
     * @param  int     $type
     * @return Order
     */
    public function getOrder($orderId, $type = 0)
    {
        if ($type === 0) {
            // Get order by entity id
            $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
            $order = $objectManager->create('\Magento\Sales\Model\OrderRepository')->get($orderId);
        } else {
            // Get order by increment id
            $order = $this->_orderInterface->loadByIncrementId($orderId);
        }

        return $order;
    }

    /**
     * 取得實際的訂單編號
     * @param  string  $orderId
     * @return string
     */
    public function getRealOrderId($orderId)
    {
        return $this->getOrder($orderId)->getRealOrderId();
    }

    /**
     * 取得訂單建立時間
     * @param  string  $orderId
     * @return string
     */
    public function getCreatedAt($orderId)
    {
        return $this->getOrder($orderId)->getCreatedAt();
    }

    /**
     * 取得訂單狀態
     * @param  string  $orderId
     * @return string
     */
    public function getStatus($orderId)
    {
        return $this->getOrder($orderId)->getStatus();
    }

    /**
     * 取得訂單的 protect code 欄位
     * @param  string  $orderId
     * @return string
     */
    public function getProtectCode($orderId)
    {
        return $this->getOrder($orderId)->getProtectCode();
    }

    /**
     * 取得付款方式
     * @param  string  $orderId
     * @return string
     */
    public function getPaymentMethod($orderId)
    {
        $payment = $this->getOrder($orderId)->getPayment();
        $method = $payment->getMethod();

        return $method;
    }

    /**
     * 取得額外資訊
     * @param  string  $orderId
     * @return string
     */
    public function getAdditionalInformation($orderId)
    {
        $payment = $this->getOrder($orderId)->getPayment();
        $info = $payment->getAdditionalInformation();

        return $info;
    }

    /**
     * 取得幣別
     * @param  string  $orderId
     * @return string
     */
    public function getOrderCurrencyCode($orderId)
    {
        return $this->getOrder($orderId)->getOrderCurrencyCode();
    }

    /**
     * 取得訂單折扣金額
     * @param  string  $orderId
     * @return string
     */
    public function getBaseDiscountAmount($orderId)
    {
        return $this->getOrder($orderId)->getBaseDiscountAmount();
    }

    /**
     * 取得訂單物流金額
     * @param  string  $orderId
     * @return string
     */
    public function getBaseShippingAmount($orderId)
    {
        return $this->getOrder($orderId)->getBaseShippingAmount();
    }

    /**
     * 取得訂單小計金額(未稅)
     * @param  string  $orderId
     * @return string
     */
    public function getBaseSubtotal($orderId)
    {
        return $this->getOrder($orderId)->getBaseSubtotal();
    }

    /**
     * 取得訂單總金額 - store base currency grand total
     * @param  string  $orderId
     * @return string
     */
    public function getBaseGrandTotal($orderId)
    {
        return $this->getOrder($orderId)->getBaseGrandTotal();
    }

    /**
     * 取得訂單總金額(GrandTotal)
     * @param  string  $orderId
     * @return string
     */
    public function getGrandTotal($orderId)
    {
        return $this->getOrder($orderId)->getGrandTotal();
    }

    /**
     * 取得訂單總金額(BaseTotalPaid)
     * @param  string  $orderId
     * @return string
     */
    public function getBaseTotalPaid($orderId)
    {
        return $this->getOrder($orderId)->getBaseTotalPaid();
    }

    /**
     * 取得訂單總金額(TotalPaid)
     * @param  string  $orderId
     * @return string
     */
    public function getTotalPaid($orderId)
    {
        return $this->getOrder($orderId)->getTotalPaid();
    }

    /**
     * 取得訂單重量
     * @param  string  $orderId
     * @return string
     */
    public function getWeight($orderId)
    {
        return $this->getOrder($orderId)->getWeight();
    }

    /**
     * 取得訂購人姓名
     * @param  string  $orderId
     * @return string
     */
    public function getCustomerFirstname($orderId)
    {
        return $this->getOrder($orderId)->getCustomerFirstname();
    }

    /**
     * 取得訂購人姓名
     * @param  string  $orderId
     * @return string
     */
    public function getCustomerLastname($orderId)
    {
        return $this->getOrder($orderId)->getCustomerLastname();
    }

    /**
     * 取得訂購人Email
     * @param  string  $orderId
     * @return string
     */
    public function getCustomerEmail($orderId)
    {
        return $this->getOrder($orderId)->getCustomerEmail();
    }

    /**
     * 取得帳單地址
     * @param  string  $orderId
     * @return class
     */
    public function getBillingAddress($orderId)
    {
        return $this->getOrder($orderId)->getBillingAddress();
    }

    /**
     * 取得帳單城市
     * @param  string  $orderId
     * @return string
     */
    public function getBillingCity($orderId)
    {
        return $this->getOrder($orderId)->getBillingAddress()->getCity();
    }

    /**
     * 取得帳單區域
     * @param  string  $orderId
     * @return string
     */
    public function getBillingRegion($orderId)
    {
        return $this->getOrder($orderId)->getBillingAddress()->getRegion();
    }

    /**
     * 取得帳單郵遞區號
     * @param  string  $orderId
     * @return string
     */
    public function getBillingPostcode($orderId)
    {
        return $this->getOrder($orderId)->getBillingAddress()->getPostcode();
    }

    /**
     * 取得帳單街道
     * @param  string  $orderId
     * @return string
     */
    public function getBillingStreet($orderId)
    {
        $street = $this->getOrder($orderId)->getBillingAddress()->getStreet();

        return $street[0];
    }

    /**
     * 取得帳單收件人
     * @param  string  $orderId
     * @return string
     */
    public function getBillingName($orderId)
    {
        $firstName  =  $this->getOrder($orderId)->getBillingAddress()->getFirstname();
        $lastName   =  $this->getOrder($orderId)->getBillingAddress()->getlastname();

        return $lastName.$firstName ;
    }

    /**
     * 取得帳單連絡電話
     * @param  string  $orderId
     * @return string
     */
    public function getBillingTelephone($orderId)
    {
        return $this->getOrder($orderId)->getBillingAddress()->getTelephone();
    }

    /**
     * 取得帳單電子郵件
     * @param  string  $orderId
     * @return class
     */
    public function getBillingEmail($orderId)
    {
        return $this->getOrder($orderId)->getBillingAddress()->getEmail();
    }

    /**
     * 取得帳單公司名稱
     * @param  string  $orderId
     * @return class
     */
    public function getBillingCompany($orderId)
    {
        return $this->getOrder($orderId)->getBillingAddress()->getCompany();
    }

    /**
     * 取得收件人
     * @param  string  $orderId
     * @return string
     */
    public function getShippingName($orderId)
    {
        $firstName  =  $this->getOrder($orderId)->getShippingAddress()->getFirstname();
        $lastName   =  $this->getOrder($orderId)->getShippingAddress()->getLastname();

        return $lastName.$firstName ;
    }

    /**
     * 取得收件人電話
     * @param  string  $orderId
     * @return string
     */
    public function getShippingTelephone($orderId)
    {
        return $this->getOrder($orderId)->getShippingAddress()->getTelephone();
    }

    /**
     * 取得收件人郵遞區號
     * @param  string  $orderId
     * @return string
     */
    public function getShippingPostcode($orderId)
    {
        return $this->getOrder($orderId)->getShippingAddress()->getPostcode();
    }

    /**
     * 取得收件人街道
     * @param  string  $orderId
     * @return string
     */
    public function getShippingStreet($orderId)
    {
        $street = $this->getOrder($orderId)->getShippingAddress()->getStreet();

        return $street[0];
    }

    /**
     * Get formatted order created date in store timezone
     * @param  string   $orderId
     * @param  string   $format
     * @return string
     */
    public function getCreatedAtFormatted($orderId, $format)
    {
        return $this->getOrder($orderId)->getCreatedAtFormatted($format);
    }

    /**
     * 寫入備註
     * @param  string  $orderId
     * @param  boolean  $comment
     * @param  boolean  $status
     * @param  boolean  $isVisibleOnFront
     */
    public function setOrderCommentForBack($orderId, $comment = '', $status = false, $isVisibleOnFront = false)
    {
        $order = $this->getOrder($orderId);
        $order->addCommentToStatusHistory($comment, $status, $isVisibleOnFront);
        $order->save() ;
    }

    /**
     * 更新訂單狀態
     * @param  string  $orderId
     * @param  string  $state
     */
    public function setOrderState($orderId, $state)
    {
        $order = $this->getOrder($orderId);
        $order->setState($state);
        $order->save();
    }

    /**
     * 取得訂單狀態
     * @param  string  $orderId
     */
    public function getOrderState($orderId)
    {
        $order = $this->getOrder($orderId);
        return $order->getState();
    }

    /**
     * 更新訂單狀態
     * @param  string  $orderId
     * @param  string  $status
     */
    public function setOrderStatus($orderId, $status)
    {
        $order = $this->getOrder($orderId);
        $order->setStatus($status);
        $order->save();
    }

    /**
     * 更新訂單欄位
     * @param  string  $orderId
     * @param  string  $key
     * @param  string  $value
     */
    public function setOrderData($orderId, $key = '', $value = '')
    {
        $order = $this->getOrder($orderId);
        $order->setData($key, $value);
        $order->save() ;
    }

    /**
     * 利用訂單編號取出訂購商品
     * @param  string   $orderId
     * @return array    $results
     */
    public function getSalesOrderItemByOrderId($orderId)
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $resource = $objectManager->get('Magento\Framework\App\ResourceConnection');
        $connection = $resource->getConnection();

        $select = $connection->select()
            ->from(['soi' => 'sales_order_item'],['name'])
            ->where("soi.order_id = :order_id");

        $bind = ['order_id' => $orderId];
        $results = $connection->fetchAll($select, $bind);

        $connection->closeConnection();

        return $results;
    }

    // invoice

    /**
     * 取得發票資訊
     * @param  string  $orderId
     * @return string
     */
    public function getEcpayInvoiceCarruerType($orderId)
    {
        return $this->getOrder($orderId)->getEcpayInvoiceCarruerType();
    }

    /**
     * 取得發票資訊
     * @param  string  $orderId
     * @return string
     */
    public function getecpayInvoiceType($orderId)
    {
        return $this->getOrder($orderId)->getecpayInvoiceType();
    }

    /**
     * 取得發票資訊
     * @param  string  $orderId
     * @return string
     */
    public function getEcpayInvoiceCarruerNum($orderId)
    {
        return $this->getOrder($orderId)->getEcpayInvoiceCarruerNum();
    }

    /**
     * 取得發票資訊
     * @param  string  $orderId
     * @return string
     */
    public function getEcpayInvoiceLoveCode($orderId)
    {
        return $this->getOrder($orderId)->getEcpayInvoiceLoveCode();
    }

    /**
     * 取得發票資訊
     * @param  string  $orderId
     * @return string
     */
    public function getEcpayInvoiceCustomerIdentifier($orderId)
    {
        return $this->getOrder($orderId)->getEcpayInvoiceCustomerIdentifier();
    }

    /**
     * 取得發票資訊(公司抬頭)
     * @param  string  $orderId
     * @return string
     */
    public function getEcpayInvoiceCustomerCompany($orderId)
    {
        return $this->getOrder($orderId)->getEcpayInvoiceCustomerCompany();
    }

    /**
     * 取得發票開立旗標 0.未開立 1.已開立
     * @param  string  $orderId
     * @return string
     */
    public function getEcpayInvoiceTag($orderId)
    {
        return $this->getOrder($orderId)->getEcpayInvoiceTag();
    }

    /**
     * 取得發票開立旗標 1.一般開立 2.延遲開立
     * @param  string  $orderId
     * @return string
     */
    public function getEcpayInvoiceIssueType($orderId)
    {
        return $this->getOrder($orderId)->getEcpayInvoiceIssueType();
    }

    /**
     * 取得發票交易單號
     * @param  string  $orderId
     * @return string
     */
    public function getEcpayInvoiceOdSob($orderId)
    {
        return $this->getOrder($orderId)->getEcpayInvoiceOdSob();
    }

    /**
     * 取得發票號碼
     * @param  string  $orderId
     * @return string
     */
    public function getEcpayInvoiceNumber($orderId)
    {
        return $this->getOrder($orderId)->getEcpayInvoiceNumber();
    }

    /**
     * 取得發票開立時間
     * @param  string  $orderId
     * @return string
     */
    public function getEcpayInvoiceDate($orderId)
    {
        return $this->getOrder($orderId)->getEcpayInvoiceDate();
    }

    /**
     * 取得發票隨機碼
     * @param  string  $orderId
     * @return string
     */
    public function getEcpayInvoiceRandomNumber($orderId)
    {
        return $this->getOrder($orderId)->getEcpayInvoiceRandomNumber();
    }

    /**
     * 取得發票自動開立程序
     * @param  string  $orderId
     * @return string
     */
    public function getEcpayInvoiceAutoTag($orderId)
    {
        return $this->getOrder($orderId)->getEcpayInvoiceAutoTag();
    }

    /**
     * 利用商家自訂訂單編號找出訂單
     * @param  string   $invoiceOdSob
     * @return array    $results
     */
    public function getOrderByEcpayInvoiceOdSob($invoiceOdSob)
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $resource = $objectManager->get('Magento\Framework\App\ResourceConnection');
        $connection = $resource->getConnection();

        $select = $connection->select()
            ->from(['so' => 'sales_order'],['*'])
            ->where("so.ecpay_invoice_od_sob = :ecpay_invoice_od_sob");

        $bind = ['ecpay_invoice_od_sob' => $invoiceOdSob];
        $results = $connection->fetchAll($select, $bind);

        $connection->closeConnection();

        return $results;
    }

    /**
     * 執行Magento本身的開立發票程序
     * @param  string   $orderId
     * @return array    $results
     */
    public function setOrderInvoice($orderId)
    {
        $order = $this->getOrder($orderId);
        $this->_loggerInterface->debug('setOrderInvoice 執行Magento本身的發票程序');
        $order = $this->getOrder($orderId);

        if($order->canInvoice()) {

            $baseGrandTotal = $this->getBaseGrandTotal($orderId);
            $grandTotal     = $this->getGrandTotal($orderId);

            $baseTotalPaid  = $this->getBaseTotalPaid($orderId);
            $totalPaid      = $this->getTotalPaid($orderId);

            // $baseGrandTotal = $baseGrandTotal + $baseTotalPaid ;
            // $grandTotal = $grandTotal + $totalPaid ;

            $this->setOrderData($orderId, 'base_total_paid', $baseGrandTotal);
            $this->setOrderData($orderId, 'total_paid', $grandTotal);

            $this->_loggerInterface->debug('setOrderInvoice base_total_paid:' . print_r($baseGrandTotal, true));
            $this->_loggerInterface->debug('setOrderInvoice total_paid:' . print_r($grandTotal, true));

            $invoice = $this->_invoiceService->prepareInvoice($order);
            $invoice->register();
            $invoice->save();
            $invoice->getOrder()->setIsInProcess(true);
            $transactionSave = $this->_transaction->addObject($invoice)->addObject($invoice->getOrder())->save();

        }
    }


    // logistic

    /**
     * 取得物流方式
     * @param  string  $orderId
     * @return string
     */
    public function getShippingMethod($orderId)
    {
        return $this->getOrder($orderId)->getShippingMethod();
    }

    /**
     * 取得物流方式組合名稱
     * @param  string  $orderId
     * @return string
     */
    public function getShippingDescription($orderId)
    {
        return $this->getOrder($orderId)->getShippingDescription();
    }

    /**
     * 取得超商店舖編號
     * @param  string  $orderId
     * @return string
     */
    public function getEcpayLogisticCvsStoreId($orderId)
    {
        return $this->getOrder($orderId)->getEcpayLogisticCvsStoreId();
    }

    /**
     * 取得超商店舖名稱
     * @param  string  $orderId
     * @return string
     */
    public function getEcpayLogisticCvsStoreName($orderId)
    {
        return $this->getOrder($orderId)->getEcpayLogisticCvsStoreName();
    }

    /**
     * 取得超商店舖地址
     * @param  string  $orderId
     * @return string
     */
    public function getEcpayLogisticCvsStoreAddress($orderId)
    {
        return $this->getOrder($orderId)->getEcpayLogisticCvsStoreAddress();
    }

    /**
     * 取得超商店舖電話
     * @param  string  $orderId
     * @return string
     */
    public function getEcpayLogisticCvsStoreTelephone($orderId)
    {
        return $this->getOrder($orderId)->getEcpayLogisticCvsStoreTelephone();
    }

    /**
     * 取得物流單自動開立程序
     * @param  string  $orderId
     * @return string
     */
    public function getEcpayLogisticAutoTag($orderId)
    {
        return $this->getOrder($orderId)->getEcpayLogisticAutoTag();
    }

    /**
     * 取得綠界物流單建立旗標 0.未建立 1.已建立
     * @param  string  $orderId
     * @return string
     */
    public function getEcpayShippingTag($orderId)
    {
        return $this->getOrder($orderId)->getEcpayShippingTag();
    }

    /**
     * 取得綠界物流單資訊
     * @param  string  $orderId
     * @return string
     */
    public function getEcpayLogisticInfo($orderId)
    {
        $info = [] ;

        $ecpayLogisticModel = $this->_ecpayLogisticFactory->create();

        $collection =  $ecpayLogisticModel
                     ->getCollection()
                     ->addFieldToFilter('order_id', ['eq' => $orderId])
                     ->setOrder('entity_id','DESC')
                     ->setCurPage(1)
                     ->setPageSize(1);

        foreach($collection as $item){
            $info = $item->getData() ;
        }

        return $info;
    }

    /**
     * 利用MerchantTradeNo取得綠界物流單資訊
     * @param  string  $MerchantTradeNo
     * @return string
     */
    public function getEcpayLogisticInfoByMerchantTradeNo($MerchantTradeNo)
    {
        $info = [] ;

        $ecpayLogisticModel = $this->_ecpayLogisticFactory->create();

        $collection =  $ecpayLogisticModel
                     ->getCollection()
                     ->addFieldToFilter('merchant_trade_no', ['eq' => $MerchantTradeNo])
                     ->setOrder('entity_id','DESC')
                     ->setCurPage(1)
                     ->setPageSize(1);

        foreach($collection as $item){
            $info = $item->getData() ;
        }

        return $info;
    }

    /**
     * 執行Magento本身的物流程序
     * @param  string   $orderId
     * @return array    $results
     */
    public function setOrderShip($orderId)
    {

        $this->_loggerInterface->debug('setOrderShip 執行Magento本身的物流程序');

        $order = $this->getOrder($orderId);

        if (!$order->canShip()) {
            throw new \Magento\Framework\Exception\LocalizedException(
                __("You can't create the Shipment of this order.")
            );
        }

        $orderShipment = $this->_convertOrder->toShipment($order);

        foreach ($order->getAllItems() as $orderItem) {

            // Check virtual item and item Quantity
            if (!$orderItem->getQtyToShip() || $orderItem->getIsVirtual()) {
                continue;
            }

            $qty = $orderItem->getQtyToShip();
            $shipmentItem = $this->_convertOrder->itemToShipmentItem($orderItem)->setQty($qty);
            $orderShipment->addItem($shipmentItem);
        }

        $orderShipment->register();
        $orderShipment->getOrder()->setIsInProcess(true);

        try {

            // Save created Order Shipment
            $orderShipment->save();
            $orderShipment->getOrder()->save();

            // Send Shipment Email
            $this->_shipmentNotifier->notify($orderShipment);
            $orderShipment->save();

        } catch (\Exception $e) {
            throw new \Magento\Framework\Exception\LocalizedException(
                __($e->getMessage())
            );
        }
    }

    /**
     * 取得Ship ID
     * @param  string  $orderId
     * @param  string  $key
     * @param  string  $value
     */
    public function getShipmentId($orderId)
    {
        $shipmentId = null ;

        $order = $this->getOrder($orderId);

        $shipmentCollection = $order->getShipmentsCollection();

        foreach($shipmentCollection as $shipment){
            $shipmentId = $shipment->getIncrementId();
        }

        $this->_loggerInterface->debug('OrderService getShipmentId shipmentId:'. print_r($shipmentId,true));
        return $shipmentId ;
    }

    // payment

    /**
     * 取得綠界金流資訊
     * @param  string  $orderId
     * @return string
     */
    public function getEcpayPaymentInfo($orderId)
    {
        $info = [] ;

        $ecpayPaymentInfoModel = $this->_ecpayPaymentInfoFactory->create();

        $collection =  $ecpayPaymentInfoModel
                     ->getCollection()
                     ->addFieldToFilter('order_id', ['eq' => $orderId])
                     ->setOrder('entity_id','DESC')
                     ->setCurPage(1)
                     ->setPageSize(1);

        foreach($collection as $item){
            $info = $item->getData() ;
        }

        return $info;
    }

    /**
     * 利用MerchantTradeNo取得綠界金流資訊
     * @param  string  $MerchantTradeNo
     * @return string
     */
    public function getEcpayPaymentInfoByMerchantTradeNo($MerchantTradeNo)
    {
        $info = [] ;

        $ecpayPaymentInfoModel = $this->_ecpayPaymentInfoFactory->create();

        $collection =  $ecpayPaymentInfoModel
                     ->getCollection()
                     ->addFieldToFilter('merchant_trade_no', ['eq' => $MerchantTradeNo])
                     ->setOrder('entity_id','DESC')
                     ->setCurPage(1)
                     ->setPageSize(1);

        foreach($collection as $item){
            $info = $item->getData() ;
        }

        return $info;
    }

    /**
     * 取得付款完成狀態
     * @param  string  $orderId
     * @return string
     */
    public function getEcpayPaymentCompleteTag($orderId)
    {
        return $this->getOrder($orderId)->getEcpayPaymentCompleteTag();
    }
}