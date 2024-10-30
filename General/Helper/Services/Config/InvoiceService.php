<?php

namespace Ecpay\General\Helper\Services\Config;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Store\Model\ScopeInterface;
use Magento\Framework\UrlInterface;

use Ecpay\General\Model\EcpayInvoice;

use Ecpay\General\Helper\Services\Config\MainService;
use Ecpay\General\Helper\Services\Common\OrderService;
use Ecpay\General\Helper\Services\Config\LogisticService;
use Ecpay\Sdk\Factories\Factory;

class InvoiceService extends AbstractHelper
{
    protected $_urlInterface;

    protected $_mainService;
    protected $_orderService;
    protected $_logisticService;

    public function __construct(
        Context $context,
        UrlInterface $urlInterface,

        MainService $mainService,
        OrderService $orderService,
        LogisticService $logisticService
    )
    {
        $this->_urlInterface = $urlInterface;

        $this->_mainService = $mainService;
        $this->_orderService = $orderService;
        $this->_logisticService = $logisticService;

        parent::__construct($context);
    }

    /**
     * 取出測試帳號KEY IV
     *
     * @return array
     */
    public function getStageAccount()
    {

        $info = [
            'MerchantId'    => '2000132',
            'HashKey'       => 'ejCk326UnaZWKisg',
            'HashIv'        => 'q9jcZX8Ib9LM8wYk',
        ];

        return $info;
    }

    /**
     * 取出API介接網址
     * @param  string  $action
     * @param  string  $stage
     * @return string  $url
     */
    public function getApiUrl($action = 'issue', $stage = 1)
    {

        if($stage == 1){

            switch ($action) {

                case 'check_love_code':
                    $url = 'https://einvoice-stage.ecpay.com.tw/B2CInvoice/CheckLoveCode';
                break;

                case 'check_barcode':
                    $url = 'https://einvoice-stage.ecpay.com.tw/B2CInvoice/CheckBarcode';
                break;

                case 'issue':
                    $url = 'https://einvoice-stage.ecpay.com.tw/B2CInvoice/Issue';
                break;

                case 'delay_issue':
                    $url = 'https://einvoice-stage.ecpay.com.tw/B2CInvoice/DelayIssue';
                break;

                case 'invalid':
                    $url = 'https://einvoice-stage.ecpay.com.tw/B2CInvoice/Invalid';
                break;

                case 'cancel_delay_issue':
                    $url = 'https://einvoice-stage.ecpay.com.tw/B2CInvoice/CancelDelayIssue';
                break;

                default:
                    $url = '';
                break;
            }

        } else {

            switch ($action) {

                case 'check_love_code':
                    $url = 'https://einvoice.ecpay.com.tw/B2CInvoice/CheckLoveCode';
                break;

                case 'check_barcode':
                    $url = 'https://einvoice.ecpay.com.tw/B2CInvoice/CheckBarcode';
                break;

                case 'issue':
                    $url = 'https://einvoice.ecpay.com.tw/B2CInvoice/Issue';
                break;

                case 'delay_issue':
                    $url = 'https://einvoice.ecpay.com.tw/B2CInvoice/DelayIssue';
                break;

                case 'invalid':
                    $url = 'https://einvoice.ecpay.com.tw/B2CInvoice/Invalid';
                break;

                case 'cancel_delay_issue':
                    $url = 'https://einvoice.ecpay.com.tw/B2CInvoice/CancelDelayIssue';
                break;

                default:
                    $url = '';
                break;
            }
        }

        return $url;
    }

    /**
     * 取得發票開立類別名稱對應表
     *
     * @return array
     */
    public function getInvoiceTypeTable()
    {
        return [
            EcpayInvoice::ECPAY_INVOICE_TYPE_P => '個人',
            EcpayInvoice::ECPAY_INVOICE_TYPE_C => '公司',
            EcpayInvoice::ECPAY_INVOICE_TYPE_D => '捐贈',
        ];
    }

    /**
     * 開立發票
     * @param string $orderId
     */
    public function invoiceIssue($orderId)
    {
        // 判斷發票模組是否啟動
        $ecpayEnableInvoice = $this->_mainService->isInvoiceModuleEnable();
        if ($ecpayEnableInvoice == 0) {
            return [
                'code'  => '1003',
                'msg'   => __('code_1003'),
                'data'  => '',
            ];;
        }

        // 判斷是否已經開立過發票
        $ecpayInvoiceTag = $this->_orderService->getEcpayInvoiceTag($orderId);
        if ($ecpayInvoiceTag == 0) {
        } else {
            return [
                'code'  => '1002',
                'msg'   => __('code_1002'),
                'data'  => '',
            ];;
        }

        $status = false;

        // 取出是否為測試模式
        $invoiceStage = $this->_mainService->getInvoiceConfig('enabled_invoice_stage');
        $this->_logger->debug('InvoiceService invoiceStage:'. print_r($invoiceStage, true));

        if ($invoiceStage == 1) {
            // 取出 KEY IV MID (測試模式)
            $accountInfo = $this->getStageAccount();
            $this->_logger->debug('InvoiceService accountInfo:'. print_r($accountInfo, true));
        }
        else {
            // 取出 KEY IV MID (正式模式)
            $invoiceMerchantId = $this->_mainService->getInvoiceConfig('invoice_mid');
            $invoiceHashKey    = $this->_mainService->getInvoiceConfig('invoice_hashkey');
            $invoiceHashIv     = $this->_mainService->getInvoiceConfig('invoice_hashiv');

            $this->_logger->debug('InvoiceService invoiceMerchantId:'. print_r($invoiceMerchantId, true));
            $this->_logger->debug('InvoiceService invoiceHashKey:'. print_r($invoiceHashKey, true));
            $this->_logger->debug('InvoiceService invoiceHashIv:'. print_r($invoiceHashIv, true));

            $accountInfo = [
                'MerchantId' => $invoiceMerchantId,
                'HashKey'    => $invoiceHashKey,
                'HashIv'     => $invoiceHashIv,
            ];
        }

        // 判斷是否為延遲開立
        $invoiceDelayDate = $this->_mainService->getInvoiceConfig('invoice_dalay_date');
        $this->_logger->debug('InvoiceService invoiceDelayDate:'. print_r($invoiceDelayDate, true));

        // 取出訂單前綴
        $invoiceOrderPreFix = $this->_mainService->getInvoiceConfig('invoice_order_prefix');
        $this->_logger->debug('InvoiceService invoiceOrderPreFix:'. print_r($invoiceOrderPreFix, true));

        // 組合廠商訂單編號
        $merchantTradeNo = $this->_orderService->getMerchantTradeNo($orderId, $invoiceOrderPreFix);
        $this->_logger->debug('InvoiceService merchantTradeNo:'. print_r($merchantTradeNo, true));

        // 取出訂單金額資訊
        $baseDiscountAmount = $this->_orderService->getBaseDiscountAmount($orderId);
        $baseShippingAmount = $this->_orderService->getBaseShippingAmount($orderId);
        $baseSubtotal       = $this->_orderService->getBaseSubtotal($orderId);
        $baseGrandTotal     = $this->_orderService->getBaseGrandTotal($orderId);

        $itemPrice = $baseSubtotal + $baseShippingAmount + $baseDiscountAmount;
        $itemAmount = $baseGrandTotal;
        $saleAmount = intval(round($itemAmount, 0));

        $this->_logger->debug('InvoiceService baseDiscountAmount:'. print_r($baseDiscountAmount, true));
        $this->_logger->debug('InvoiceService baseShippingAmount:'. print_r($baseShippingAmount, true));
        $this->_logger->debug('InvoiceService baseSubtotal:'. print_r($baseSubtotal, true));
        $this->_logger->debug('InvoiceService saleAmount:'. print_r($saleAmount, true));

        // 發票欄位資訊
        $ecpayInvoiceCarruerType        = $this->_orderService->getEcpayInvoiceCarruerType($orderId);
        $ecpayInvoiceType               = $this->_orderService->getecpayInvoiceType($orderId);
        $ecpayInvoiceCarruerNum         = $this->_orderService->getEcpayInvoiceCarruerNum($orderId);
        $ecpayInvoiceLoveCode           = $this->_orderService->getEcpayInvoiceLoveCode($orderId);
        $ecpayInvoiceCustomerIdentifier = $this->_orderService->getEcpayInvoiceCustomerIdentifier($orderId);
        $ecpayInvoiceCustomerCompany    = $this->_orderService->getEcpayInvoiceCustomerCompany($orderId);

        $this->_logger->debug('InvoiceService ecpayInvoiceCarruerType:'. print_r($ecpayInvoiceCarruerType, true));
        $this->_logger->debug('InvoiceService ecpayInvoiceType:'. print_r($ecpayInvoiceType, true));
        $this->_logger->debug('InvoiceService ecpayInvoiceCarruerNum:'. print_r($ecpayInvoiceCarruerNum, true));
        $this->_logger->debug('InvoiceService ecpayInvoiceLoveCode:'. print_r($ecpayInvoiceLoveCode, true));
        $this->_logger->debug('InvoiceService ecpayInvoiceCustomerIdentifier:'. print_r($ecpayInvoiceCustomerIdentifier, true));
        $this->_logger->debug('InvoiceService ecpayInvoiceCustomerCompany:'. print_r($ecpayInvoiceCustomerCompany, true));

        // 取得帳單收件人資訊
        $billingName        = $this->_orderService->getBillingName($orderId);
        $billingTelephone   = $this->_orderService->getBillingTelephone($orderId);
        $billingEmail       = $this->_orderService->getBillingEmail($orderId);

        // 取得帳單地址資訊
        $billingCity        = $this->_orderService->getBillingCity($orderId);
        $billingRegion      = $this->_orderService->getBillingRegion($orderId);
        $billingPostcode    = $this->_orderService->getBillingPostcode($orderId);
        $billingStreet      = $this->_orderService->getBillingStreet($orderId);

        $this->_logger->debug('InvoiceService billingName:'. print_r($billingName, true));
        $this->_logger->debug('InvoiceService billingTelephone:'. print_r($billingTelephone, true));
        $this->_logger->debug('InvoiceService billingEmail:'. print_r($billingEmail, true));
        $this->_logger->debug('InvoiceService billingCity:'. print_r($billingCity, true));
        $this->_logger->debug('InvoiceService billingRegion:'. print_r($billingRegion, true));
        $this->_logger->debug('InvoiceService billingPostcode:'. print_r($billingPostcode, true));
        $this->_logger->debug('InvoiceService billingStreet:'. print_r($billingStreet, true));

        // 判斷是否為自動開立模式，寫入備註
        $commentMessage = $this->_orderService->getEcpayInvoiceAutoTag($orderId) ? '(自動開立)' : '';

        if (true) {
        
            $factory = new Factory([
                'hashKey'   => $accountInfo['HashKey'],
                'hashIv'    => $accountInfo['HashIv'],
            ]);

            $postService = $factory->create('PostWithAesJsonResponseService');

            // 訂單資訊組合
            $Items[] = [
                'ItemName'      => '網路商品一批',
                'ItemCount'     => 1,
                'ItemWord'      => '批',
                'ItemPrice'     => $itemPrice,
                'ItemTaxType'   => '1',
                'ItemAmount'    => $itemAmount,
            ];

            // 地址郵遞區號組合
            $getPostalName = $this->_logisticService->getPostalName($billingPostcode);

            $data = [
                'MerchantID'    => $accountInfo['MerchantId'],
                'RelateNumber'  => $merchantTradeNo,
                'CustomerID'    => '',
                'CustomerName'  => $billingName,
                'CustomerAddr'  => $getPostalName.$billingStreet,
                'CustomerPhone' => $billingTelephone,
                'CustomerEmail' => $billingEmail,
                'Print'         => '0',
                'Donation'      => '0',
                'LoveCode'      => '',
                'CarrierType'   => '',
                'CarrierNum'    => '',
                'TaxType'       => '1',
                'SalesAmount'   => $saleAmount,
                'Items'         => $Items,
                'InvType'       => '07'
            ];

            switch ($ecpayInvoiceType) {
                case EcpayInvoice::ECPAY_INVOICE_TYPE_P:
                    $this->_logger->debug('InvoiceService ecpayInvoiceType:個人');

                    switch ($ecpayInvoiceCarruerType) {
                        case '1':
                            $data['CarrierType'] = '1';
                        break;

                        case '2':
                            $data['CarrierType'] = '2';
                            $data['CarrierNum'] = $ecpayInvoiceCarruerNum;
                        break;

                        case '3':
                            $data['CarrierType'] = '3';
                            $data['CarrierNum'] = $ecpayInvoiceCarruerNum;
                        break;

                        default:
                            $data['Print'] = '1';
                        break;
                    }

                break;

                case EcpayInvoice::ECPAY_INVOICE_TYPE_C:
                    $this->_logger->debug('InvoiceService ecpayInvoiceType:公司');

                    switch ($ecpayInvoiceCarruerType) {
                        case '1':
                            $data['CarrierType'] = '1';
                        break;

                        case '2':
                            $data['CarrierType'] = '2';
                            $data['CarrierNum'] = $ecpayInvoiceCarruerNum;
                        break;

                        case '3':
                            $data['CarrierType'] = '3';
                            $data['CarrierNum'] = $ecpayInvoiceCarruerNum;
                        break;

                        default:
                            $data['Print'] = '1';
                    }
                    
                    $data['CustomerIdentifier'] = $ecpayInvoiceCustomerIdentifier;
                    $data['CustomerName'] = $ecpayInvoiceCustomerCompany;

                break;

                case EcpayInvoice::ECPAY_INVOICE_TYPE_D:
                    $this->_logger->debug('InvoiceService ecpayInvoiceType:捐贈');
                    $data['Donation'] = '1';
                    $data['LoveCode'] = $ecpayInvoiceLoveCode;

                break;
            }
        }

        // 組合送綠界格式
        if ($invoiceDelayDate == '' || $invoiceDelayDate == 0) {
            // 一般開立

            // 取出 URL
            $apiUrl = $this->getApiUrl('issue', $invoiceStage);
            $this->_logger->debug('InvoiceService apiUrl:'. print_r($apiUrl, true));

            $input = [
                'MerchantID' => $accountInfo['MerchantId'],
                'RqHeader' => [
                    'Timestamp' => time(),
                    'Revision' => '3.0.0',
                ],

                'Data' => $data,
            ];

            try {
                $response = $postService->post($input, $apiUrl);

                $this->_logger->debug('InvoiceService input:'. print_r($input, true));
                $this->_logger->debug('InvoiceService response:'. print_r($response, true));

                if (isset($response['Data']['RtnCode']) && $response['Data']['RtnCode'] == 1) {

                    // 更新訂單發票欄位
                    $this->_orderService->setOrderData($orderId, 'ecpay_invoice_number', $response['Data']['InvoiceNo']);
                    $this->_orderService->setOrderData($orderId, 'ecpay_invoice_date', $response['Data']['InvoiceDate']);
                    $this->_orderService->setOrderData($orderId, 'ecpay_invoice_random_number', $response['Data']['RandomNumber']);

                    $this->_orderService->setOrderData($orderId, 'ecpay_invoice_tag', 1);
                    $this->_orderService->setOrderData($orderId, 'ecpay_invoice_issue_type', 1);
                    $this->_orderService->setOrderData($orderId, 'ecpay_invoice_od_sob', $merchantTradeNo);

                    // 回傳資料寫入備註
                    $comment = $response['Data']['RtnMsg'] . '，發票號碼：' . $response['Data']['InvoiceNo'] . '，隨機碼：' . $response['Data']['RandomNumber'] . '交易單號：' . $merchantTradeNo . $commentMessage;

                    $isVisibleOnFront = false;

                    $this->_orderService->setOrderCommentForBack($orderId, $comment, $status, $isVisibleOnFront);

                    // 執行Magento自己的發票程序
                    $this->_orderService->setOrderInvoice($orderId);

                    // 組合回傳前端的dataResponse
                    $dataResponse = [
                        'ecpay_invoice_number'              => $response['Data']['InvoiceNo'],
                        'ecpay_invoice_date'                => $response['Data']['InvoiceDate'],
                        'ecpay_invoice_random_number'       => $response['Data']['RandomNumber'],
                        'ecpay_invoice_tag'                 => 1,
                        'ecpay_invoice_issue_type'          => 1,
                        'ecpay_invoice_od_sob'              => $merchantTradeNo,
                        'ecpay_invoice_type'                => $this->getInvoiceTypeTable()[$ecpayInvoiceType],
                    ];

                    switch ($ecpayInvoiceType) {
                        case EcpayInvoice::ECPAY_INVOICE_TYPE_P:
                            $dataResponse['ecpay_invoice_carruer_num'] = $ecpayInvoiceCarruerNum ;
                        break;
                        case EcpayInvoice::ECPAY_INVOICE_TYPE_C:
                            $dataResponse['ecpay_invoice_customer_company'] = $ecpayInvoiceCustomerCompany ;
                            $dataResponse['ecpay_invoice_customer_identifier'] = $ecpayInvoiceCustomerIdentifier ;
                        break;
                        case EcpayInvoice::ECPAY_INVOICE_TYPE_D:
                            $dataResponse['ecpay_invoice_love_code'] = $ecpayInvoiceLoveCode ;
                        break;
                    }

                    return [
                        'code' => '0999',
                        'msg'   => __('code_0999'),
                        'data' => $dataResponse
                    ];

                }
                else {

                    $comment = '開立失敗' . print_r($response) . $commentMessage;
                    $isVisibleOnFront = false;
                    $this->_orderService->setOrderCommentForBack($orderId, $comment, $status, $isVisibleOnFront);

                    return [
                        'code' => '1004',
                        'msg'   => __('code_1004'),
                        'data' => ''
                    ];
                }
            } catch (\Exception $e) {
                // 回傳資料寫入備註
                $comment = '一般開立發票失敗，' . $e->getMessage(); 
                $status = false;
                $isVisibleOnFront = false;

                $this->_orderService->setOrderCommentForBack($orderId, $comment, $status, $isVisibleOnFront);
            }
        }
        else {
            // 延遲開立

            // 取出 URL
            $apiUrl = $this->getApiUrl('delay_issue', $invoiceStage);
            $this->_logger->debug('InvoiceService apiUrl:'. print_r($apiUrl, true));

            $NotifyURL = $this->_urlInterface->getUrl("ecpaygeneral/Process/InvoiceDelayNotifyResponse");
            $this->_logger->debug('InvoiceService NotifyURL:'. print_r($NotifyURL, true));

            // 補上延遲開立的參數
            $data['DelayFlag']  =  '1';
            $data['DelayDay']   =  $invoiceDelayDate;
            $data['Tsr']        =  $merchantTradeNo;
            $data['PayType']    =  '2';
            $data['PayAct']     =  'ECPAY';
            $data['NotifyURL']  =  $NotifyURL;

            $input = [
                'MerchantID' => $accountInfo['MerchantId'],
                'RqHeader' => [
                    'Timestamp' => time(),
                    'Revision' => '3.0.0',
                ],

                'Data' => $data,
            ];

            try {
                $response = $postService->post($input, $apiUrl);

                $this->_logger->debug('InvoiceService input:'. print_r($input, true));
                $this->_logger->debug('InvoiceService response:'. print_r($response, true));

                if (isset($response['Data']['RtnCode']) && $response['Data']['RtnCode'] == 1) {
                    // 更新訂單發票欄位
                    $this->_orderService->setOrderData($orderId, 'ecpay_invoice_tag', 1);
                    $this->_orderService->setOrderData($orderId, 'ecpay_invoice_issue_type', 2);
                    $this->_orderService->setOrderData($orderId, 'ecpay_invoice_od_sob', $merchantTradeNo);

                    // 回傳資料寫入備註
                    $comment = $response['Data']['RtnMsg'] . '(延遲開立)，交易單號：' . $response['Data']['OrderNumber'] . $commentMessage;
                    $isVisibleOnFront = false;

                    $this->_orderService->setOrderCommentForBack($orderId, $comment, $status, $isVisibleOnFront);

                    // 執行Magento自己的發票程序
                    $this->_orderService->setOrderInvoice($orderId);

                    // 組合回傳前端的dataResponse
                    $dataResponse = [
                        'ecpay_invoice_number'          => '',
                        'ecpay_invoice_date'            => '',
                        'ecpay_invoice_random_number'   => '',
                        'ecpay_invoice_tag'             => 1,
                        'ecpay_invoice_issue_type'      => 2,
                        'ecpay_invoice_od_sob'          => $merchantTradeNo,
                    ];

                    return [
                        'code' => '0999',
                        'msg'   => __('code_0999'),
                        'data' => $dataResponse
                    ];
                }
                else {
                    $comment = '開立失敗' . print_r($response) . $commentMessage;
                    $isVisibleOnFront = false;
                    $this->_orderService->setOrderCommentForBack($orderId, $comment, $status, $isVisibleOnFront);

                    return [
                        'code' => '1004',
                        'msg'   => __('code_1004'),
                        'data' => ''
                    ];
                }
            } catch (\Exception $e) {
                // 回傳資料寫入備註
                $comment = '延遲開立發票失敗，' . $e->getMessage(); 
                $status = false;
                $isVisibleOnFront = false;

                $this->_orderService->setOrderCommentForBack($orderId, $comment, $status, $isVisibleOnFront);
            }
        }
    }
}