<?php
namespace Ecpay\General\Controller\Api;

use Exception;
use Psr\Log\LoggerInterface ;
use Magento\Framework\UrlInterface;

use Ecpay\General\Helper\Services\Common\EncryptionsService;
use Ecpay\General\Helper\Services\Common\OrderService;
use Ecpay\General\Helper\Services\Config\MainService;
use Ecpay\General\Helper\Services\Config\InvoiceService;
use Ecpay\General\Helper\Services\Config\LogisticService;
use Ecpay\General\Helper\Services\Config\PaymentService;
use Ecpay\General\Helper\Foundation\GeneralHelper;

use Ecpay\General\Model\EcpayLogisticFactory;

use Ecpay\Sdk\Factories\Factory;

class Logistic {

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

    protected $_ecpayLogisticFactory;

    public function __construct(
        LoggerInterface $loggerInterface,
        UrlInterface $urlInterface,

        EncryptionsService $encryptionsService,
        OrderService $orderService,
        MainService $mainService,
        InvoiceService $invoiceService,
        LogisticService $logisticService,
        PaymentService $paymentService,
        GeneralHelper $generalHelper,
        EcpayLogisticFactory $ecpayLogisticFactory
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
        $this->_ecpayLogisticFactory = $ecpayLogisticFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function changeStore($orderId, $protectCode)
    {
        $responseArray = [
            'code'  => '0000',
            'msg'   => '這是門市選擇用API',
            'data'  => '',
        ];

        // 判斷模組是否啟動
        $ecpayEnableLogistic = $this->_mainService->getMainConfig('ecpay_enabled_logistic') ;
        $this->_loggerInterface->debug('changeStore ecpayEnableLogistic:'. print_r($ecpayEnableLogistic,true));

        if($ecpayEnableLogistic == 0){

            // 轉為JSON格式
            $responseArray = [
                'code'  => '2003',
                'msg'   => __('code_2003'),
                'data'  => '',
            ];

            header("Content-Type: application/json; charset=utf-8");
            return json_encode($responseArray);

        }

        // 解密訂單編號
        $enctyOrderId = str_replace(' ', '+', $orderId) ;
        $orderId      = $this->_encryptionsService->decrypt($enctyOrderId);
        $orderId = (int) $orderId ;

        $this->_loggerInterface->debug('changeStore enctyOrderId:'. print_r($enctyOrderId,true));
        $this->_loggerInterface->debug('changeStore orderId:'. print_r($orderId,true));

        // 取出訂單protect_code資訊
        $protectCodeFromOrder = $this->_orderService->getProtectCode($orderId);
        $this->_loggerInterface->debug('changeStore protectCodeFromOrder:'. print_r($protectCodeFromOrder,true));

        // 驗證訂單欄位protect_code 是否正確
        if($protectCodeFromOrder != $protectCode){

            $this->_loggerInterface->debug('changeStore protect_code驗證錯誤:'. print_r($protectCode,true));

            // 轉為JSON格式
            $responseArray = [
                'code'  => '0001',
                'msg'   => __('code_0001'),
                'data'  => '',
            ];

            header("Content-Type: application/json; charset=utf-8");
            return json_encode($responseArray);
        }

        // 物流單建立狀態
        $ecpayShippingTag = $this->_orderService->getEcpayShippingTag($orderId);
        $this->_loggerInterface->debug('changeStore $ecpayShippingTag:'.$ecpayShippingTag);

        // 目前僅支援一張物流單
        if($ecpayShippingTag == 1){

            // 轉為JSON格式
            $responseArray = [
                'code'  => '2006',
                'msg'   => __('code_2006'),
                'data'  => '',
            ];

            header("Content-Type: application/json; charset=utf-8");
            return json_encode($responseArray);
        }

        // 取出訂單資訊

        // 物流方式
        $shippingMethod = $this->_orderService->getShippingMethod($orderId);
        $this->_loggerInterface->debug('changeStore $shippingMethod:'.$shippingMethod);

        // 貨到付款判斷
        $paymentMethod = $this->_orderService->getPaymentMethod($orderId);
        $this->_loggerInterface->debug('changeStore paymentMethod:'.$paymentMethod);

        if($paymentMethod == 'cashondelivery'){
            $isCollection = 'Y';
        } else {
            $isCollection = 'N';
        }

        // 超商取貨物流判斷
        if (!$this->_logisticService->isEcpayCvsLogistics($shippingMethod)) {
            // 轉為JSON格式
            $responseArray = [
                'code'  => '2004',
                'msg'   => __('code_2004'),
                'data'  => '',
            ];

            header("Content-Type: application/json; charset=utf-8");
            return json_encode($responseArray);
        }

        // 取出是否為測試模式
        $logisticStage = $this->_mainService->getLogisticConfig('enabled_logistic_stage') ;
        $this->_loggerInterface->debug('changeStore logisticStage:'. print_r($logisticStage,true));

        // 取出CvsType
        $logisticCvsType = $this->_mainService->getLogisticConfig('logistic_cvs_type') ;
        $this->_loggerInterface->debug('changeStore logisticCvsType:'. print_r($logisticCvsType,true));

        // 取出 URL
        $apiUrl = $this->_logisticService->getApiUrl('map', $logisticStage);
        $this->_loggerInterface->debug('changeStore apiUrl:'. print_r($apiUrl,true));

        // 判斷測試模式
        if($logisticStage == 1){

            // 取出 KEY IV MID (測試模式)
            $accountInfo = $this->_logisticService->getStageAccount($logisticCvsType);
            $this->_loggerInterface->debug('changeStore accountInfo:'. print_r($accountInfo,true));

        } else {

            // 取出 KEY IV MID (正式模式)
            $logisticMerchantId = $this->_mainService->getLogisticConfig('logistic_mid') ;
            $logisticHashKey    = $this->_mainService->getLogisticConfig('logistic_hashkey') ;
            $logisticHashIv     = $this->_mainService->getLogisticConfig('logistic_hashiv') ;

            $this->_loggerInterface->debug('changeStore logisticMerchantId:'. print_r($logisticMerchantId,true));
            $this->_loggerInterface->debug('changeStore logisticHashKey:'. print_r($logisticHashKey,true));
            $this->_loggerInterface->debug('changeStore logisticHashIv:'. print_r($logisticHashIv,true));

            $accountInfo = [
                'MerchantId' => $logisticMerchantId,
                'HashKey'    => $logisticHashKey,
                'HashIv'     => $logisticHashIv,
            ] ;
        }

        // 取出訂單前綴
        $logisticOrderPreFix = $this->_mainService->getLogisticConfig('logistic_order_prefix') ;
        $this->_loggerInterface->debug('changeStore logisticOrderPreFix:'. print_r($logisticOrderPreFix,true));

        // 組合廠商訂單編號
        $merchantTradeNo = $this->_orderService->getMerchantTradeNo($orderId, $logisticOrderPreFix);
        $this->_loggerInterface->debug('changeStore merchantTradeNo:'. print_r($merchantTradeNo,true));

        // 貨態回傳網址
        $serverReplyURL = $this->_urlInterface->getUrl("ecpaygeneral/Process/LogisticChangeStoreResponse");
        $this->_loggerInterface->debug('changeStore serverReplyURL:'. print_r($serverReplyURL,true));

        // 取出物流子類型
        $logisticsSubType = $this->_logisticService->getCvsLogisticsSubType($logisticCvsType, $shippingMethod);
        $this->_loggerInterface->debug('changeStore logisticsSubType:'. print_r($logisticsSubType,true));

        // 組合格式
        $factory = new Factory([
            'hashKey' => $accountInfo['HashKey'],
            'hashIv' => $accountInfo['HashIv'],
            'hashMethod' => 'md5',
        ]);

        $autoSubmitFormService = $factory->create('AutoSubmitFormWithCmvService');

        $input = [
            'MerchantID'        => $accountInfo['MerchantId'],
            'MerchantTradeNo'   => $merchantTradeNo,
            'LogisticsType'     => 'CVS',
            'LogisticsSubType'  => $logisticsSubType,
            'IsCollection'      => $isCollection,
            'ServerReplyURL'    => $serverReplyURL,
            'ExtraData'         => $enctyOrderId,
        ];

        $formMap = $autoSubmitFormService->generate($input, $apiUrl);

        $formMap =  str_replace('<!DOCTYPE html><html><head><meta charset="utf-8"></head><body>', '', $formMap) ;
        $formMap =  str_replace('</body></html>', '', $formMap) ;
        $formMap =  str_replace('<script type="text/javascript">document.getElementById("ecpay-form").submit();</script>', '', $formMap) ;

        // 回傳前端資訊
        $dataResponse = [
            'form_map' => $formMap,
        ] ;

        // 轉為JSON格式
        $responseArray = [
            'code'  => '0999',
            'msg'   => __('code_0999'),
            'data'  => $dataResponse,
        ];

        header("Content-Type: application/json; charset=utf-8");
        return json_encode($responseArray);
    }

    /**
     * {@inheritdoc}
     */
    public function createOrder($orderId, $protectCode)
    {
        $responseArray = [
            'code'  => '0000',
            'msg'   => '這是建立物流訂單API',
            'data'  => '',
        ];

        // 判斷物流模組是否啟動
        $ecpayEnableLogistic = $this->_mainService->isLogisticModuleEnable();
        $this->_loggerInterface->debug('createLogisticOrder ecpayEnableLogistic:'. print_r($ecpayEnableLogistic,true));

        if ($ecpayEnableLogistic == 0) {

            // 轉為JSON格式
            $responseArray = [
                'code'  => '2003',
                'msg'   => __('code_2003'),
                'data'  => '',
            ];

            header("Content-Type: application/json; charset=utf-8");
            return json_encode($responseArray);
        }

        // 解密訂單編號
        $enctyOrderId = str_replace(' ', '+', $orderId) ;
        $orderId      = $this->_encryptionsService->decrypt($enctyOrderId);
        $orderId      = intval($orderId);

        // 取出訂單protect_code資訊
        $protectCodeFromOrder = $this->_orderService->getProtectCode($orderId);
        $this->_loggerInterface->debug('createLogisticOrder protectCodeFromOrder:'. print_r($protectCodeFromOrder,true));

        // 驗證訂單欄位protect_code 是否正確
        if ($protectCodeFromOrder != $protectCode) {

            $this->_loggerInterface->debug('createLogisticOrder protect_code驗證錯誤:'. print_r($protectCode,true));

            // 轉為JSON格式
            $responseArray = [
                'code'  => '0001',
                'msg'   => __('code_0001'),
                'data'  => '',
            ];
        } else {

            // 建立物流訂單
            $result = $this->_logisticService->logisticCreateOrder($orderId);
            $resultCode = $result['code'];

            $msgCode = 'code_' . $resultCode;
            if ($resultCode !== '0999') {
                // 轉為JSON格式
                $responseArray = [
                    'code'  => $resultCode,
                    'msg'   => __($msgCode),
                    'data'  => '',
                ];
            } else {
                // 轉為JSON格式
                $responseArray = [
                    'code'  => $resultCode,
                    'msg'   => __($msgCode),
                    'data'  => $result['data'],
                ];
            }
        }

        header("Content-Type: application/json; charset=utf-8");
        return json_encode($responseArray);
    }

    /**
     * {@inheritdoc}
     */
    public function printOrder($orderId, $protectCode)
    {
        $responseArray = [
            'code'  => '0000',
            'msg'   => '這是列印物流訂單API',
            'data'  => '',
        ];

        // 判斷模組是否啟動
        $ecpayEnableLogistic = $this->_mainService->getMainConfig('ecpay_enabled_logistic') ;
        $this->_loggerInterface->debug('printOrder ecpayEnableLogistic:'. print_r($ecpayEnableLogistic,true));

        if($ecpayEnableLogistic == 0){

            // 轉為JSON格式
            $responseArray = [
                'code'  => '2003',
                'msg'   => __('code_2003'),
                'data'  => '',
            ];

            header("Content-Type: application/json; charset=utf-8");
            return json_encode($responseArray);

        }

        // 解密訂單編號
        $enctyOrderId = str_replace(' ', '+', $orderId) ;
        $orderId      = $this->_encryptionsService->decrypt($enctyOrderId);
        $orderId = (int) $orderId ;

        $this->_loggerInterface->debug('printOrder enctyOrderId:'. print_r($enctyOrderId,true));
        $this->_loggerInterface->debug('printOrder orderId:'. print_r($orderId,true));

        // 取出訂單protect_code資訊
        $protectCodeFromOrder = $this->_orderService->getProtectCode($orderId);
        $this->_loggerInterface->debug('printOrder protectCodeFromOrder:'. print_r($protectCodeFromOrder,true));

        // 驗證訂單欄位protect_code 是否正確
        if($protectCodeFromOrder != $protectCode){

            $this->_loggerInterface->debug('printOrder protect_code驗證錯誤:'. print_r($protectCode,true));

            // 轉為JSON格式
            $responseArray = [
                'code'  => '0001',
                'msg'   => __('code_0001'),
                'data'  => '',
            ];

            header("Content-Type: application/json; charset=utf-8");
            return json_encode($responseArray);
        }

        // 物流單建立狀態
        $ecpayShippingTag = $this->_orderService->getEcpayShippingTag($orderId);
        $this->_loggerInterface->debug('printOrder $ecpayShippingTag:'.$ecpayShippingTag);

        // 目前僅支援一張物流單
        if($ecpayShippingTag == 0){

            // 轉為JSON格式
            $responseArray = [
                'code'  => '2007',
                'msg'   => __('code_2007'),
                'data'  => '',
            ];

            header("Content-Type: application/json; charset=utf-8");
            return json_encode($responseArray);
        }

        // 取出訂單資訊

        // 物流方式
        $shippingMethod = $this->_orderService->getShippingMethod($orderId);
        $this->_loggerInterface->debug('printOrder $shippingMethod:'.$shippingMethod);

        // 綠界物流判斷
        if (!$this->_logisticService->isEcpayLogistics($shippingMethod)) {
            // 轉為JSON格式
            $responseArray = [
                'code'  => '2004',
                'msg'   => __('code_2004'),
                'data'  => '',
            ];

            header("Content-Type: application/json; charset=utf-8");
            return json_encode($responseArray);
        }

        // 取出是否為測試模式
        $logisticStage = $this->_mainService->getLogisticConfig('enabled_logistic_stage') ;
        $this->_loggerInterface->debug('printOrder logisticStage:'. print_r($logisticStage,true));

        // 取出CvsType
        $logisticCvsType = $this->_mainService->getLogisticConfig('logistic_cvs_type') ;
        $this->_loggerInterface->debug('printOrder logisticCvsType:'. print_r($logisticCvsType,true));

        // 取出 URL
        $apiUrl = $this->_logisticService->getApiUrl('print', $logisticStage, $logisticCvsType, $shippingMethod);
        $this->_loggerInterface->debug('printOrder apiUrl:'. print_r($apiUrl,true));

        // 判斷測試模式
        if($logisticStage == 1){

            // 取出 KEY IV MID (測試模式)
            $accountInfo = $this->_logisticService->getStageAccount($logisticCvsType);
            $this->_loggerInterface->debug('printOrder accountInfo:'. print_r($accountInfo,true));

        } else {

            // 取出 KEY IV MID (正式模式)
            $logisticMerchantId = $this->_mainService->getLogisticConfig('logistic_mid') ;
            $logisticHashKey    = $this->_mainService->getLogisticConfig('logistic_hashkey') ;
            $logisticHashIv     = $this->_mainService->getLogisticConfig('logistic_hashiv') ;

            $this->_loggerInterface->debug('printOrder logisticMerchantId:'. print_r($logisticMerchantId,true));
            $this->_loggerInterface->debug('printOrder logisticHashKey:'. print_r($logisticHashKey,true));
            $this->_loggerInterface->debug('printOrder logisticHashIv:'. print_r($logisticHashIv,true));

            $accountInfo = [
                'MerchantId' => $logisticMerchantId,
                'HashKey'    => $logisticHashKey,
                'HashIv'     => $logisticHashIv,
            ] ;
        }

        // 取出物流單資訊
        $logisticOrderInfo = $this->_orderService->getEcpayLogisticInfo($orderId);
        $this->_loggerInterface->debug('printOrder logisticOrderInfo:'. print_r($logisticOrderInfo,true));

        $inputPrint['MerchantID'] = $accountInfo['MerchantId'] ;
        $inputPrint['AllPayLogisticsID'] = $logisticOrderInfo['all_pay_logistics_id'] ;

        switch ($shippingMethod) {
            case 'ecpaylogisticcsvunimart_ecpaylogisticcsvunimart':
                $inputPrint['CVSPaymentNo'] = $logisticOrderInfo['cvs_payment_no'] ;
                $inputPrint['CVSValidationNo'] = $logisticOrderInfo['cvs_validation_no'] ;

            break;

            case 'ecpaylogisticcsvfamily_ecpaylogisticcsvfamily':
            case 'ecpaylogisticcsvhilife_ecpaylogisticcsvhilife':
            case 'ecpaylogisticcsvokmart_ecpaylogisticcsvokmart':
                $inputPrint['CVSPaymentNo'] = $logisticOrderInfo['cvs_payment_no'] ;

            break;

            default:
            break;
        }

        // 組合格式

        try {

            $factory = new Factory([
                'hashKey' => $accountInfo['HashKey'],
                'hashIv' => $accountInfo['HashIv'],
                'hashMethod' => 'md5',
            ]);

            $autoSubmitFormService = $factory->create('AutoSubmitFormWithCmvService');

            $formPrint =  $autoSubmitFormService->generate($inputPrint, $apiUrl, '_Blank','ecpay_print');
            $formPrint =  str_replace('<!DOCTYPE html><html><head><meta charset="utf-8"></head><body>', '', $formPrint) ;
            $formPrint =  str_replace('</body></html>', '', $formPrint) ;
            $formPrint =  str_replace('<script type="text/javascript">document.getElementById("ecpay_print").submit();</script>', '', $formPrint) ;

            // 回傳前端資訊
            $dataResponse = [
                'form_print' => $formPrint,
            ] ;

        } catch (RtnException $e) {
            $this->_loggerInterface->debug('printOrder :'. print_r('(' . $e->getCode() . ')' . $e->getMessage()));
        }

        // 往前端送
        $responseArray = [
            'code'  => '0999',
            'msg'   => __('code_0999'),
            'data'  => $dataResponse,
        ];

        // 轉為JSON格式
        header("Content-Type: application/json; charset=utf-8");
        return json_encode($responseArray);
    }

    /**
     * {@inheritdoc}
     */
    public function getLogisticMainConfig()
    {
        $responseArray = [
            'code'  => '0000',
            'msg'   => '這是查詢物流模組啟用API',
            'data'  => '',
        ];

        // 判斷模組是否啟動
        $ecpayEnableLogistic = $this->_mainService->getMainConfig('ecpay_enabled_logistic') ;
        $this->_loggerInterface->debug('createLogisticOrder ecpayEnableLogistic:'. print_r($ecpayEnableLogistic,true));

        // 轉為JSON格式
        $responseArray = [
            'code'  => '0999',
            'msg'   => __('code_0999'),
            'data'  => $ecpayEnableLogistic,
        ];

        // 轉為JSON格式
        header("Content-Type: application/json; charset=utf-8");
        return json_encode($responseArray);
    }

    /**
     * {@inheritdoc}
     */
    public function getShippingMethod($orderId, $protectCode)
    {
        $responseArray = [
            'code'  => '0000',
            'msg'   => '這是查詢物流方式API',
            'data'  => '',
        ];

        // 判斷模組是否啟動
        $ecpayEnableLogistic = $this->_mainService->getMainConfig('ecpay_enabled_logistic') ;
        $this->_loggerInterface->debug('getShippingMethod ecpayEnableLogistic:'. print_r($ecpayEnableLogistic,true));

        if($ecpayEnableLogistic == 0){

            // 轉為JSON格式
            $responseArray = [
                'code'  => '2003',
                'msg'   => __('code_2003'),
                'data'  => '',
            ];

            header("Content-Type: application/json; charset=utf-8");
            return json_encode($responseArray);

        }

        // 解密訂單編號
        $enctyOrderId = str_replace(' ', '+', $orderId) ;
        $orderId      = $this->_encryptionsService->decrypt($enctyOrderId);
        $orderId = (int) $orderId ;

        $this->_loggerInterface->debug('getShippingMethod enctyOrderId:'. print_r($enctyOrderId,true));
        $this->_loggerInterface->debug('getShippingMethod orderId:'. print_r($orderId,true));

        // 取出訂單protect_code資訊
        $protectCodeFromOrder = $this->_orderService->getProtectCode($orderId);
        $this->_loggerInterface->debug('getShippingMethod protectCodeFromOrder:'. print_r($protectCodeFromOrder,true));

        // 驗證訂單欄位protect_code 是否正確
        if($protectCodeFromOrder != $protectCode){

            $this->_loggerInterface->debug('getShippingMethod protect_code驗證錯誤:'. print_r($protectCode,true));

            // 轉為JSON格式
            $responseArray = [
                'code'  => '0001',
                'msg'   => __('code_0001'),
                'data'  => '',
            ];

            header("Content-Type: application/json; charset=utf-8");
            return json_encode($responseArray);
        }

        // 物流方式
        $shippingMethod = $this->_orderService->getShippingMethod($orderId);
        $this->_loggerInterface->debug('getShippingMethod $shippingMethod:'.$shippingMethod);

        // 回傳前端資訊
        $dataResponse = [
            'shipping_method' => $shippingMethod
        ] ;

        // 轉為JSON格式
        $responseArray = [
            'code'  => '0999',
            'msg'   => __('code_0999'),
            'data'  => $dataResponse,
        ];

        header("Content-Type: application/json; charset=utf-8");
        return json_encode($responseArray);
    }

    /**
     * {@inheritdoc}
     */
    public function getShippingTag($orderId, $protectCode)
    {
        $responseArray = [
            'code'  => '0000',
            'msg'   => '這是查詢建立物流單標籤API',
            'data'  => '',
        ];

        // 判斷模組是否啟動
        $ecpayEnableLogistic = $this->_mainService->getMainConfig('ecpay_enabled_logistic') ;
        $this->_loggerInterface->debug('getShippingTag ecpayEnableLogistic:'. print_r($ecpayEnableLogistic,true));

        if($ecpayEnableLogistic == 0){

            // 轉為JSON格式
            $responseArray = [
                'code'  => '2003',
                'msg'   => __('code_2003'),
                'data'  => '',
            ];

            header("Content-Type: application/json; charset=utf-8");
            return json_encode($responseArray);

        }

        // 解密訂單編號
        $enctyOrderId = str_replace(' ', '+', $orderId) ;
        $orderId      = $this->_encryptionsService->decrypt($enctyOrderId);
        $orderId = (int) $orderId ;

        $this->_loggerInterface->debug('getShippingTag enctyOrderId:'. print_r($enctyOrderId,true));
        $this->_loggerInterface->debug('getShippingTag orderId:'. print_r($orderId,true));

        // 取出訂單protect_code資訊
        $protectCodeFromOrder = $this->_orderService->getProtectCode($orderId);
        $this->_loggerInterface->debug('getShippingTag protectCodeFromOrder:'. print_r($protectCodeFromOrder,true));

        // 驗證訂單欄位protect_code 是否正確
        if($protectCodeFromOrder != $protectCode){

            $this->_loggerInterface->debug('getShippingTag protect_code驗證錯誤:'. print_r($protectCode,true));

            // 轉為JSON格式
            $responseArray = [
                'code'  => '0001',
                'msg'   => __('code_0001'),
                'data'  => '',
            ];

            header("Content-Type: application/json; charset=utf-8");
            return json_encode($responseArray);
        }

        // 物流單建立狀態
        $ecpayShippingTag = $this->_orderService->getEcpayShippingTag($orderId);
        $this->_loggerInterface->debug('getEcpayShippingTag $ecpayShippingTag:'.$ecpayShippingTag);

        // 轉為JSON格式
        $responseArray = [
            'code'  => '0999',
            'msg'   => __('code_0999'),
            'data'  => $ecpayShippingTag,
        ];

        header("Content-Type: application/json; charset=utf-8");
        return json_encode($responseArray);
    }


}