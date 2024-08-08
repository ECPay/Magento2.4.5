<?php
namespace Ecpay\General\Controller\Process;

use Exception;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;
use Magento\Sales\Model\Order;
use Psr\Log\LoggerInterface ;

use Ecpay\General\Helper\Services\Common\EncryptionsService;
use Ecpay\General\Helper\Services\Common\OrderService;
use Ecpay\General\Helper\Services\Common\MailService;
use Ecpay\General\Helper\Services\Config\MainService;
use Ecpay\General\Helper\Services\Config\InvoiceService;
use Ecpay\General\Helper\Services\Config\LogisticService;
use Ecpay\General\Helper\Services\Config\PaymentService;
use Ecpay\General\Helper\Foundation\EncryptionsHelper;
use Ecpay\General\Helper\Foundation\GeneralHelper;
use Ecpay\General\Model\EcpayPaymentInfoFactory;

class PaymentInfoResponse extends Action implements CsrfAwareActionInterface
{

    protected $_loggerInterface;
    protected $_requestInterface;

    protected $_encryptionsService;
    protected $_orderService;

    protected $_mainService;
    protected $_invoiceService;
    protected $_logisticService;
    protected $_paymentService;

    protected $_encryptionsHelper;
    protected $_generalHelper;

    protected $_ecpayPaymentInfoFactory;

    public function __construct(
        LoggerInterface $loggerInterface,
        Context $context,
        RequestInterface $requestInterface,

        EncryptionsService $encryptionsService,
        OrderService $orderService,

        MainService $mainService,
        InvoiceService $invoiceService,
        LogisticService $logisticService,
        PaymentService $paymentService,

        EncryptionsHelper $encryptionsHelper,
        GeneralHelper $generalHelper,

        EcpayPaymentInfoFactory $ecpayPaymentInfoFactory
    )
    {
        $this->_loggerInterface = $loggerInterface;
        $this->_requestInterface = $requestInterface;

        $this->_encryptionsService = $encryptionsService;
        $this->_orderService = $orderService;

        $this->_mainService = $mainService;
        $this->_invoiceService = $invoiceService;
        $this->_logisticService = $logisticService;
        $this->_paymentService = $paymentService;

        $this->_encryptionsHelper = $encryptionsHelper;
        $this->_generalHelper = $generalHelper;

        $this->_ecpayPaymentInfoFactory = $ecpayPaymentInfoFactory;

        return parent::__construct($context);
    }

    public function execute()
    {
        // 接收金流資訊
        $paymentInfo = $this->_requestInterface->getPostValue();
        $this->_loggerInterface->debug('PaymentInfoResponse paymentInfo:'. print_r($paymentInfo,true));

        if (count($paymentInfo) < 1) {
            throw new Exception('Get ECPay feedback failed.');
        } else {

            // 取得原始訂單編號
            $orderInfo = $this->_orderService->getOrderIdByPaymentMerchantTradeNo($paymentInfo['MerchantTradeNo']);
            $this->_loggerInterface->debug('PaymentInfoResponse orderInfo:'. print_r($orderInfo,true));

            if (isset($orderInfo['entity_id']) && $orderInfo['entity_id'] !== '') {
                $orderId = intval($orderInfo['entity_id']);
            } else {
                $enctyOrderId = $this->getRequest()->getParam('id') ;
                $enctyOrderId = str_replace(' ', '+', $enctyOrderId) ;
                $orderId      = intval($this->_encryptionsService->decrypt($enctyOrderId));
            }
            $this->_loggerInterface->debug('PaymentInfoResponse orderId:'. print_r($orderId, true));

            // 取出 KEY IV MID
            $accountInfo = $this->_paymentService->getStageAccount();
            if ($paymentInfo['MerchantID'] != $accountInfo['MerchantId']) {
                $accountInfo = [
                    'HashKey' => $this->_mainService->getPaymentConfig('payment_hashkey'),
                    'HashIv'  => $this->_mainService->getPaymentConfig('payment_hashiv'),
                ] ;
            }
            $this->_loggerInterface->debug('PaymentToEcpay accountInfo:'. print_r($accountInfo,true));

            // 驗證參數(金額及checkMacValue)
            $checkMacValue = $this->_paymentService->checkMacValue($accountInfo, $paymentInfo);
            $orderCurrencyCode = $this->_orderService->getOrderCurrencyCode($orderId);
            $orderTotal = (!$orderCurrencyCode) ? $this->_orderService->getGrandTotal($orderId) : $this->_orderService->getBaseGrandTotal($orderId);
            // 符合AIO金額無條件進位到整數
            $orderTotal = (int)ceil($orderTotal);

            if (!$this->_paymentService->validAmount($paymentInfo['TradeAmt'], $orderTotal) || !$checkMacValue) {
                $this->_loggerInterface->debug('PaymentInfoResponse $paymentInfo.TradeAmt:'. print_r($paymentInfo['TradeAmt'], true));
                $this->_loggerInterface->debug('PaymentInfoResponse $orderTotal:'. print_r($orderTotal, true));
                throw new Exception('Order amount are not identical.');
            } else {

                // 訂單狀態判斷
                $createStatus = 'pending_payment';
                $orderStatus = $this->_orderService->getStatus($orderId);
                $this->_loggerInterface->debug('PaymentInfoResponse $orderStatus:'. print_r($orderStatus, true));

                if ($orderStatus === $createStatus) {
                    $responseResult = $this->_paymentService->getPaymentInfoResponse($orderId, $paymentInfo);
                    $this->_loggerInterface->debug('PaymentInfoResponse $responseResult:'. print_r($responseResult, true));

                    // 更新訂單備註
                    $status = false;
                    $isVisibleOnFront = false;
                    $this->_orderService->setOrderCommentForBack($orderId, $responseResult['comment'], $status, $isVisibleOnFront);

                    // 判斷取號結果
                    if ($responseResult['status'] === 1) {
                        // 相關資料庫回寫
                        $this->saveEcpayPaymentInfo($orderId, $paymentInfo);

                        // 更新訂單狀態
                        $this->_orderService->setOrderState($orderId, 'ecpay_pending_payment');
                        $this->_orderService->setOrderStatus($orderId, 'ecpay_pending_payment');

                        // 寄送付款資訊信件
                        // 組合信件內容參數
                        $paymentType = $this->_paymentService->getPaymentMethod($paymentInfo['PaymentType']);
                        if ($paymentType != 'BNPL') {
                            $templateValues = [
                                'real_order_id'        => $this->_orderService->getRealOrderId($orderId),
                                'created_at_formatted' => $this->_orderService->getCreatedAtFormatted($orderId, 2),
                                'payment_type'         => $paymentType,
                                'total_amount'         => intval($this->_orderService->getGrandTotal($orderId)),
                                'payment_info'         => $this->_paymentService->getPaymentInfoTemplateValues($paymentInfo),
                            ];
                            $this->_loggerInterface->debug('PaymentInfoResponse $templateValues:'. print_r($templateValues, true));
    
                            // 組合信件格式 包含寄件人、收件人、信件內容
                            $paymentMethod = strtolower($paymentType);
                            $mailData = [
                                'sender_name'     => MailService::DEFAULT_SEND_NAME,
                                'sender_email'    => MailService::DEFAULT_SEND_EMAIL,
                                'template_id'     => 'ecpay_payment_info_' . $paymentMethod . '_template',
                                'template_values' => $templateValues,
                                'receiver'        => $this->_orderService->getCustomerEmail($orderId),
                            ];
                            $this->_paymentService->sendMail($mailData);
                        }
                    } else {
                        // 更新訂單狀態
                        $this->_orderService->setOrderState($orderId, Order::STATE_CANCELED);
                        $this->_orderService->setOrderStatus($orderId, Order::STATE_CANCELED);
                    }
                }
            }
        }

        echo '1|OK' ;

        exit();
    }

    /**
     * 儲存 paymentinfo 回傳資料
     *
     * @param  string $orderId
     * @param  array  $response
     * @return void
     */
    public function saveEcpayPaymentInfo(string $orderId, array $response)
    {
        $ecpayPaymentInfoModel = $this->_ecpayPaymentInfoFactory->create();
        $paymentInfoData = [
            'order_id'          => $orderId,
            'merchant_id'       => $response['MerchantID'],
            'merchant_trade_no' => $response['MerchantTradeNo'],
            'store_id'          => $response['StoreID'],
            'rtn_code'          => $response['RtnCode'],
            'rtn_msg'           => $response['RtnMsg'],
            'trade_no'          => $response['TradeNo'],
            'trade_amt'         => $response['TradeAmt'],
            'payment_type'      => $response['PaymentType'],
            'trade_date'        => $response['TradeDate'],
            'custom_field1'     => $response['CustomField1'],
            'custom_field2'     => $response['CustomField2'],
            'custom_field3'     => $response['CustomField3'],
            'custom_field4'     => $response['CustomField4'],
        ];

        $paymentMethod = $this->_paymentService->getPaymentMethod($response['PaymentType']);
        switch($paymentMethod) {
            case 'ATM':
                $extension = [
                    'expire_date' => $response['ExpireDate'],
                    'bank_code'   => $response['BankCode'],
                    'vaccount'    => $response['vAccount'],
                ];
                break;
            case 'CVS':
            case 'BARCODE':
                $extension = [
                    'expire_date' => $response['ExpireDate'],
                    'payment_no'  => $response['PaymentNo'],
                    'barcode1'    => $response['Barcode1'],
                    'barcode2'    => $response['Barcode2'],
                    'barcode3'    => $response['Barcode3'],
                ];
                break;
            case 'BNPL':
                $extension = [
                    'bnpl_trade_no' => $response['BNPLTradeNo'],
                    'bnpl_installment'   => $response['BNPLInstallment'],
                ];
                break;
        }
        $paymentInfoData = array_merge($paymentInfoData, $extension);

        $ecpayPaymentInfoModel->addData($paymentInfoData);
        $ecpayPaymentInfoModel->save();
    }

    /**
     * @inheritDoc
     */
    public function createCsrfValidationException(
        RequestInterface $requestInterface
    ): ?InvalidRequestException {

        return null;
    }

    /**
     * @inheritDoc
     */
    public function validateForCsrf(RequestInterface $requestInterface): ?bool
    {
        return true;
    }
}