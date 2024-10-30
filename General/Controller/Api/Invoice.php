<?php 
namespace Ecpay\General\Controller\Api;

use Psr\Log\LoggerInterface ;
use Magento\Framework\UrlInterface;
use Magento\Framework\Webapi\Exception;

use Ecpay\General\Model\EcpayInvoice;
use Ecpay\General\Helper\Services\Common\EncryptionsService;
use Ecpay\General\Helper\Services\Common\OrderService;
use Ecpay\General\Helper\Services\Config\MainService;
use Ecpay\General\Helper\Services\Config\InvoiceService;
use Ecpay\General\Helper\Services\Config\LogisticService;
use Ecpay\General\Helper\Services\Config\PaymentService;
use Ecpay\General\Helper\Foundation\GeneralHelper;

use Ecpay\Sdk\Factories\Factory;

class Invoice {

    protected $_loggerInterface;
    protected $_urlInterface;

    protected $_encryptionsService;
    protected $_orderService;
    protected $_mainService;
    protected $_invoiceService;
    protected $_logisticService;
    protected $_paymentService;

    protected $_encryptionsHelper;
    protected $_generalHelper;

    public function __construct(
        LoggerInterface $loggerInterface,
        UrlInterface $urlInterface,

        EncryptionsService $encryptionsService,
        OrderService $orderService,
        MainService $mainService,
        InvoiceService $invoiceService,
        LogisticService $logisticService,
        PaymentService $paymentService,
        GeneralHelper $generalHelper
    )
    {
        $this->_loggerInterface = $loggerInterface;
        $this->_urlInterface = $urlInterface;

        $this->_encryptionsService = $encryptionsService;
        $this->_orderService = $orderService;
        $this->_mainService = $mainService;
        $this->_invoiceService = $invoiceService;
        $this->_logisticService = $logisticService;
        $this->_paymentService = $paymentService;
        
        $this->_generalHelper = $generalHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function checkBarcode($barcode)
    {
        // 手機條碼格式驗證
        if (!preg_match('/^\/{1}[0-9a-zA-Z+-.]{7}$/', $barcode)) {
            throw new Exception(__('code_1008'), '1008');
        }

        // 取出是否為測試模式
        $invoiceStage = $this->_mainService->getInvoiceConfig('enabled_invoice_stage');
        $this->_loggerInterface->debug('getInvoiceAccountInfo invoiceStage:' . print_r($invoiceStage, true));

        // 取得發票會員資訊
        $accountInfo = $this->getInvoiceAccountInfo($invoiceStage);

        $factory = new Factory([
            'hashKey'   => $accountInfo['HashKey'],
            'hashIv'    => $accountInfo['HashIv'],
        ]);

        $postService = $factory->create('PostWithAesJsonResponseService');

        // 取出 URL
        $apiUrl = $this->_invoiceService->getApiUrl('check_barcode', $invoiceStage);
        $this->_loggerInterface->debug('checkBarcode apiUrl:'. print_r($apiUrl, true));

        // 組合送綠界格式
        $data = [
            'MerchantID' 	=> $accountInfo['MerchantId'],
            'BarCode' 		=> $barcode,
        ];

        $input = [
            'MerchantID' => $accountInfo['MerchantId'],
            'RqHeader' => [
                'Timestamp' => time(),
                'Revision' => '3.0.0',
            ],
            'Data' => $data,
        ];

        $response = $postService->post($input, $apiUrl);

        $this->_loggerInterface->debug('checkBarcode input:' . print_r($input, true));
        $this->_loggerInterface->debug('checkBarcode response:' . print_r($response, true));

        // 呼叫財政部API失敗
        if (isset($response['Data']['RtnCode']) && $response['Data']['RtnCode'] == 9000001) {
            $this->_loggerInterface->debug(__('code_1901'));
            throw new Exception(__('code_1901'), '1901');
        }

        // 手機條碼驗證失敗
        if (!isset($response['Data']['RtnCode']) || $response['Data']['RtnCode'] != 1 || $response['Data']['IsExist'] == 'N') {
            throw new Exception(__('code_1009'), '1009');
        }

        // 轉為JSON格式
        $responseArray = [
            'code'  => '0999',
            'msg'   => __('code_0999'),
            'data'  => '',
        ];

        return json_encode($responseArray);
    }
   
    /**
     * {@inheritdoc}
     */
    public function checkLoveCode($loveCode)
    {
        // 捐贈碼格式驗證
        if (!preg_match('/^([xX]{1}[0-9]{2,6}|[0-9]{3,7})$/', $loveCode)) {
            throw new Exception(__('code_1010'), '1010');
        }

        // 取出是否為測試模式
        $invoiceStage = $this->_mainService->getInvoiceConfig('enabled_invoice_stage');
        $this->_loggerInterface->debug('invalidInvoice invoiceStage:' . print_r($invoiceStage, true));

        // 取得發票會員資訊
        $accountInfo = $this->getInvoiceAccountInfo($invoiceStage);

        $factory = new Factory([
            'hashKey'   => $accountInfo['HashKey'],
            'hashIv'    => $accountInfo['HashIv'],
        ]);

        $postService = $factory->create('PostWithAesJsonResponseService');

        // 取出 URL
        $apiUrl = $this->_invoiceService->getApiUrl('check_love_code', $invoiceStage);
        $this->_loggerInterface->debug('checkLoveCode apiUrl:'. print_r($apiUrl, true));

        // 組合送綠界格式
        $data = [
            'MerchantID' 	=> $accountInfo['MerchantId'],
            'LoveCode' 		=> $loveCode,
        ];

        $input = [
            'MerchantID' => $accountInfo['MerchantId'],
            'RqHeader' => [
                'Timestamp' => time(),
                'Revision' => '3.0.0',
            ],
            'Data' => $data,
        ];

        $response = $postService->post($input, $apiUrl);

        $this->_loggerInterface->debug('checkLoveCode input:' . print_r($input, true));
        $this->_loggerInterface->debug('checkLoveCode response:' . print_r($response, true));

        // 呼叫財政部API失敗
        if (isset($response['Data']['RtnCode']) && $response['Data']['RtnCode'] == 9000001) {
            $this->_loggerInterface->debug(__('code_1901'));
            throw new Exception(__('code_1901'), '1901');
        }

        // 捐贈碼驗證失敗
        if (!isset($response['Data']['RtnCode']) || $response['Data']['RtnCode'] != 1 || $response['Data']['IsExist'] == 'N') {
            throw new Exception(__('code_1011'), '1011');
        }

        $responseArray = [
            'code'  => '0999',
            'msg'   => __('code_0999'),
            'data'  => '',
        ];

        return json_encode($responseArray);
    }

    /**
     * {@inheritdoc}
     */
    public function checkCitizenDigitalCertificate($carrierNumber)
    {
        // 自然人憑證格式驗證
        if (!preg_match('/^[a-zA-Z]{2}\d{14}$/', $carrierNumber)) {
            throw new Exception(__('code_1012'), '1012');
        }

        $responseArray = [
            'code'  => '0999',
            'msg'   => __('code_0999'),
            'data'  => '',
        ];

        return json_encode($responseArray);
    }

    /**
     * {@inheritdoc}
     */
    public function checkBusinessNumber($businessNumber)
    {
        // 統一編號格式驗證
        if (!preg_match('/^[0-9]{8}$/', $businessNumber)) {
            throw new Exception(__('code_1013'), '1013');
        }

        $responseArray = [
            'code'  => '0999',
            'msg'   => __('code_0999'),
            'data'  => '',
        ];

        return json_encode($responseArray);
    }

    /**
     * {@inheritdoc}
     */
    public function createInvoice($orderId, $protectCode)
    {
        $responseArray = [
            'code'  => '0000',
            'msg'   => '這是開立發票API',
            'data'  => '',
        ];
        
        $enctyOrderId = str_replace(' ', '+', $orderId) ;
        $orderId      = $this->_encryptionsService->decrypt($enctyOrderId);
        $orderId      = (int) $orderId ;

        // 取出訂單protect_code資訊
        $protectCodeFromOrder = $this->_orderService->getProtectCode($orderId);
        $this->_loggerInterface->debug('createInvoice protectCodeFromOrder:'. print_r($protectCodeFromOrder,true));

        // 驗證訂單欄位protect_code 是否正確
        if ($protectCodeFromOrder != $protectCode) {
            $this->_loggerInterface->debug('createInvoice protect_code驗證錯誤:'. print_r($protectCode,true));

            // 轉為JSON格式
            $responseArray = [
                'code'  => '0001',
                'msg'   => __('code_0001'),
                'data'  => '',
            ];
        }
        else {
            $responseArray = $this->_invoiceService->invoiceIssue($orderId);
        }
        $this->_loggerInterface->debug('createInvoice responseArray:'. print_r($responseArray,true));
        
        return json_encode($responseArray);
    }

    /**
     * {@inheritdoc}
     */
    public function invalidInvoice($orderId, $protectCode)
    {
        $responseArray = [
            'code'  => '0000',
            'msg'   => '這是作廢發票API',
            'data'  => '',
        ];

        // 解密訂單編號
        $enctyOrderId = str_replace(' ', '+', $orderId) ;
        $orderId      = $this->_encryptionsService->decrypt($enctyOrderId);
        $orderId      = (int) $orderId ;

        $this->_loggerInterface->debug('invalidInvoice enctyOrderId:' . print_r($enctyOrderId,true));
        $this->_loggerInterface->debug('invalidInvoice orderId:' . print_r($orderId,true));

        // 取出訂單protect_code資訊
        $protectCodeFromOrder = $this->_orderService->getProtectCode($orderId);
        $this->_loggerInterface->debug('invalidInvoice protectCodeFromOrder:' . print_r($protectCodeFromOrder,true));

        // 驗證訂單欄位protect_code 是否正確
        if ($protectCodeFromOrder != $protectCode) {

            $this->_loggerInterface->debug('invalidInvoice protect_code驗證錯誤:' . print_r($protectCode,true));

            // 轉為JSON格式
            $responseArray = [
                'code'  => '0001',
                'msg'   => __('code_0001'),
                'data'  => '',
            ];

            return json_encode($responseArray);
        }

        // 判斷是否有發票要作廢
        $ecpayInvoiceTag = $this->_orderService->getEcpayInvoiceTag($orderId);
        $this->_loggerInterface->debug('invalidInvoice ecpayInvoiceTag:' . print_r($ecpayInvoiceTag, true));

        if ($ecpayInvoiceTag == 1) {

            // 取出是否為測試模式
            $invoiceStage = $this->_mainService->getInvoiceConfig('enabled_invoice_stage');
            $this->_loggerInterface->debug('invalidInvoice invoiceStage:' . print_r($invoiceStage, true));

            if ($invoiceStage == 1) {

                // 取出 KEY IV MID (測試模式)
                $accountInfo = $this->_invoiceService->getStageAccount();
                $this->_loggerInterface->debug('invalidInvoice accountInfo:' . print_r($accountInfo, true));

            } else {

                // 取出 KEY IV MID (正式模式)
                $invoiceMerchantId = $this->_mainService->getInvoiceConfig('invoice_mid');
                $invoiceHashKey    = $this->_mainService->getInvoiceConfig('invoice_hashkey');
                $invoiceHashIv     = $this->_mainService->getInvoiceConfig('invoice_hashiv');

                $this->_loggerInterface->debug('invalidInvoice invoiceMerchantId:' . print_r($invoiceMerchantId, true));
                $this->_loggerInterface->debug('invalidInvoice invoiceHashKey:' . print_r($invoiceHashKey, true));
                $this->_loggerInterface->debug('invalidInvoice invoiceHashIv:' . print_r($invoiceHashIv, true));

                $accountInfo = [
                    'MerchantId' => $invoiceMerchantId,
                    'HashKey'    => $invoiceHashKey,
                    'HashIv'     => $invoiceHashIv,
                ] ;
            }

            // 發票欄位資訊
            $ecpayInvoiceNumber = $this->_orderService->getEcpayInvoiceNumber($orderId);
            $ecpayInvoiceDate = $this->_orderService->getEcpayInvoiceDate($orderId);
            $ecpayInvoiceIssueType = $this->_orderService->getEcpayInvoiceIssueType($orderId);  // 取出開立方式 1.一般開立 2.延遲開立

            $this->_loggerInterface->debug('invalidInvoice ecpayInvoiceNumber:'. print_r($ecpayInvoiceNumber, true));
            $this->_loggerInterface->debug('invalidInvoice ecpayInvoiceDate:'. print_r($ecpayInvoiceDate, true));
            $this->_loggerInterface->debug('invalidInvoice ecpayInvoiceIssueType:'. print_r($ecpayInvoiceIssueType, true));

            if ( !empty($ecpayInvoiceNumber)) {
                
                // 存在發票號碼

                $factory = new Factory([
                    'hashKey'   => $accountInfo['HashKey'],
                    'hashIv'    => $accountInfo['HashIv'],
                ]);

                $postService = $factory->create('PostWithAesJsonResponseService');

                // 取出 URL
                $apiUrl = $this->_invoiceService->getApiUrl('invalid', $invoiceStage);
                $this->_loggerInterface->debug('invalidInvoice apiUrl:'. print_r($apiUrl, true));

                // 組合送綠界格式
                $data = [
                    'MerchantID' => $accountInfo['MerchantId'],
                    'InvoiceNo' => $ecpayInvoiceNumber,
                    'InvoiceDate' => $ecpayInvoiceDate,
                    'Reason' => '作廢發票',
                ];

                $input = [
                    'MerchantID' => $accountInfo['MerchantId'],
                    'RqHeader' => [
                        'Timestamp' => time(),
                        'Revision' => '3.0.0',
                    ],
    
                    'Data' => $data,
                ];
    
                $response = $postService->post($input, $apiUrl);
    
                $this->_loggerInterface->debug('invalidInvoice input:' . print_r($input,true));
                $this->_loggerInterface->debug('invalidInvoice response:' . print_r($response,true));

                if (isset($response['Data']['RtnCode']) && $response['Data']['RtnCode'] == 1) {

                    // 回傳資料寫入備註
                    $comment = $response['Data']['RtnMsg'] . '，發票號碼：' . $response['Data']['InvoiceNo']; 
                    $status = false;
                    $isVisibleOnFront = false;

                    $this->_orderService->setOrderCommentForBack($orderId, $comment, $status, $isVisibleOnFront);

                    // 清除原先發票欄位
                    $this->_orderService->setOrderData($orderId, 'ecpay_invoice_tag', 0);
                    $this->_orderService->setOrderData($orderId, 'ecpay_invoice_issue_type', 0);
                    $this->_orderService->setOrderData($orderId, 'ecpay_invoice_od_sob', '');

                    $this->_orderService->setOrderData($orderId, 'ecpay_invoice_number', '');
                    $this->_orderService->setOrderData($orderId, 'ecpay_invoice_date', '');
                    $this->_orderService->setOrderData($orderId, 'ecpay_invoice_random_number', '');

                    // 回傳成功狀態到前端
                    // 轉為JSON格式
                    $responseArray = [
                        'code'  => '0999',
                        'msg'   => __('code_0999'),
                        'data'  => '',
                    ];

                    return json_encode($responseArray);
                }
                else {

                    // 回傳資料寫入備註
                    $comment = '(' . $response['Data']['RtnCode'] .')'. $response['Data']['RtnMsg'] ; 
                    $status = false;
                    $isVisibleOnFront = false;

                    $this->_orderService->setOrderCommentForBack($orderId, $comment, $status, $isVisibleOnFront);

                    // 轉為JSON格式
                    $responseArray = [
                        'code'  => '1006',
                        'msg'   => __('code_1006'),
                        'data'  => '',
                    ];

                    return json_encode($responseArray);
                }

            } else if($ecpayInvoiceIssueType == 2 && empty($ecpayInvoiceNumber)){

                // 延遲開立並尚未開立發票
                $ecpayInvoiceOdSob = $this->_orderService->getEcpayInvoiceOdSob($orderId) ;
                $this->_loggerInterface->debug('CancelDelayIssue ecpayInvoiceOdSob:'. print_r($ecpayInvoiceOdSob, true));

                $factory = new Factory([
                    'hashKey'   => $accountInfo['HashKey'],
                    'hashIv'    => $accountInfo['HashIv'],
                ]);

                $postService = $factory->create('PostWithAesJsonResponseService');

                // 取出 URL
                $apiUrl = $this->_invoiceService->getApiUrl('cancel_delay_issue', $invoiceStage);
                $this->_loggerInterface->debug('CancelDelayIssue apiUrl:'. print_r($apiUrl, true));

                // 組合送綠界格式
                $data = [
                    'MerchantID' => $accountInfo['MerchantId'],
                    'Tsr' => $ecpayInvoiceOdSob,
                ];

                $input = [
                    'MerchantID' => $accountInfo['MerchantId'],
                    'RqHeader' => [
                        'Timestamp' => time(),
                        'Revision' => '3.0.0',
                    ],
    
                    'Data' => $data,
                ];
    
                $response = $postService->post($input, $apiUrl);
    
                $this->_loggerInterface->debug('CancelDelayIssue input:' . print_r($input,true));
                $this->_loggerInterface->debug('CancelDelayIssue response:' . print_r($response,true));

                if (isset($response['Data']['RtnCode']) && $response['Data']['RtnCode'] == 1) {

                    // 回傳資料寫入備註
                    $comment = $response['Data']['RtnMsg']; 
                    $status = false;
                    $isVisibleOnFront = false;

                    $this->_orderService->setOrderCommentForBack($orderId, $comment, $status, $isVisibleOnFront);

                    // 清除原先發票欄位
                    $this->_orderService->setOrderData($orderId, 'ecpay_invoice_tag', 0);
                    $this->_orderService->setOrderData($orderId, 'ecpay_invoice_issue_type', 0);
                    $this->_orderService->setOrderData($orderId, 'ecpay_invoice_od_sob', '');

                    $this->_orderService->setOrderData($orderId, 'ecpay_invoice_number', '');
                    $this->_orderService->setOrderData($orderId, 'ecpay_invoice_date', '');
                    $this->_orderService->setOrderData($orderId, 'ecpay_invoice_random_number', '');

                    // 回傳成功狀態到前端
                    // 轉為JSON格式
                    $responseArray = [
                        'code'  => '0999',
                        'msg'   => __('code_0999'),
                        'data'  => '',
                    ];

                    return json_encode($responseArray);
                }
                else {

                    // 回傳資料寫入備註
                    $comment = '(' . $response['Data']['RtnCode'] .')'. $response['Data']['RtnMsg'] ; 
                    $status = false;
                    $isVisibleOnFront = false;

                    $this->_orderService->setOrderCommentForBack($orderId, $comment, $status, $isVisibleOnFront);

                    // 轉為JSON格式
                    $responseArray = [
                        'code'  => '1007',
                        'msg'   => __('code_1007'),
                        'data'  => '',
                    ];

                    return json_encode($responseArray);
                }

            }
        }
        else {

            // 轉為JSON格式
            $responseArray = [
                'code'  => '1005',
                'msg'   => __('code_1005'),
                'data'  => '',
            ];

            return json_encode($responseArray);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getInvoiceTag($orderId, $protectCode)
    {
        $responseArray = [
            'code'  => '0000',
            'msg'   => '這是查詢發票開立標籤API',
            'data'  => '',
        ];

        // 解密訂單編號
        $enctyOrderId = str_replace(' ', '+', $orderId) ;
        $orderId      = $this->_encryptionsService->decrypt($enctyOrderId);
        $orderId = (int) $orderId ;

        $this->_loggerInterface->debug('getInvoiceTag enctyOrderId:'. print_r($enctyOrderId,true));
        $this->_loggerInterface->debug('getInvoiceTag orderId:'. print_r($orderId,true));

        // 取出訂單protect_code資訊
        $protectCodeFromOrder = $this->_orderService->getProtectCode($orderId);
        $this->_loggerInterface->debug('getInvoiceTag protectCodeFromOrder:'. print_r($protectCodeFromOrder,true));

        // 驗證訂單欄位protect_code 是否正確
        if($protectCodeFromOrder != $protectCode){

            $this->_loggerInterface->debug('getInvoiceTag protect_code驗證錯誤:'. print_r($protectCode,true));

            // 轉為JSON格式
            $responseArray = [
                'code'  => '0001',
                'msg'   => __('code_0001'),
                'data'  => '',
            ];

            return json_encode($responseArray);
        }

        // 取出發票開立標籤
        $ecpayInvoiceTag = $this->_orderService->getEcpayInvoiceTag($orderId);
        $this->_loggerInterface->debug('getInvoiceTag ecpayInvoiceTag:'. print_r($ecpayInvoiceTag,true));

        // 轉為JSON格式
        $responseArray = [
            'code'  => '0999',
            'msg'   => __('code_0999'),
            'data'  => $ecpayInvoiceTag,
        ];

        return json_encode($responseArray);
    }

    /**
     * {@inheritdoc}
     */
    public function getInvoiceMainConfig()
    {
        $responseArray = [
            'code'  => '0000',
            'msg'   => '這是查詢發票模組啟用API',
            'data'  => '',
        ];

        // 判斷發票模組是否啟動
        $ecpayEnableInvoice = $this->_mainService->getMainConfig('ecpay_enabled_invoice') ;
        $this->_loggerInterface->debug('createInvoice ecpayEnableInvoice:'. print_r($ecpayEnableInvoice,true));

        // 轉為JSON格式
        $responseArray = [
            'code'  => '0999',
            'msg'   => __('code_0999'),
            'data'  => $ecpayEnableInvoice,
        ];

        // 轉為JSON格式
        return json_encode($responseArray);
    }

    /**
     * @return array $accountInfo
     */
    protected function getInvoiceAccountInfo($invoiceStage)
    {
        if ($invoiceStage == 1) {

            // 取出 KEY IV MID (測試模式)
            $accountInfo = $this->_invoiceService->getStageAccount();
            $this->_loggerInterface->debug('getInvoiceAccountInfo accountInfo:' . print_r($accountInfo, true));

        } else {

            // 取出 KEY IV MID (正式模式)
            $invoiceMerchantId = $this->_mainService->getInvoiceConfig('invoice_mid');
            $invoiceHashKey    = $this->_mainService->getInvoiceConfig('invoice_hashkey');
            $invoiceHashIv     = $this->_mainService->getInvoiceConfig('invoice_hashiv');

            $this->_loggerInterface->debug('getInvoiceAccountInfo invoiceMerchantId:' . print_r($invoiceMerchantId, true));
            $this->_loggerInterface->debug('getInvoiceAccountInfo invoiceHashKey:' . print_r($invoiceHashKey, true));
            $this->_loggerInterface->debug('getInvoiceAccountInfo invoiceHashIv:' . print_r($invoiceHashIv, true));

            $accountInfo = [
                'MerchantId' => $invoiceMerchantId,
                'HashKey'    => $invoiceHashKey,
                'HashIv'     => $invoiceHashIv,
            ];
        }

        return $accountInfo;
    }
}