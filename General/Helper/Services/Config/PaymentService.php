<?php

namespace Ecpay\General\Helper\Services\Config;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Sales\Model\Order;

use Ecpay\General\Helper\Services\Common\MailService;
use Ecpay\General\Helper\Services\Config\MainService;
use Ecpay\Sdk\Factories\Factory;
use Ecpay\Sdk\Services\CheckMacValueService;
use Ecpay\Sdk\Services\UrlService;

class PaymentService extends AbstractHelper
{
    /**
     * 不指定付款方式。
     */
    public const ALL = 'ALL';

    /**
     * 信用卡付費
     */
    public const CREDIT = 'Credit';

    /**
     * 網路 ATM
     */
    public const WEBATM = 'WebATM';

    /**
     * 自動櫃員機
     */
    public const ATM = 'ATM';

    /**
     * 超商代碼
     */
    public const CVS = 'CVS';

    /**
     * 超商條碼
     */
    public const BARCODE = 'BARCODE';

    /**
     * Apple pay
     */
    public const APPLEPAY = 'ApplePay';

    /**
     * TWQR
     */
    public const TWQR = 'TWQR';

    /**
     * BNPL
     */
    public const BNPL = 'BNPL';

    /**
     * 付款成功代碼
     */
    public const PAYMENT_SUCCESS_CODE = 1;

    /**
     * 銀聯選項
     */
    public const UNIONPAY_OPITONAL = 0;
    public const UNIONPAY_ENABLED  = 1;
    public const UNIONPAY_DISABLED = 2;

    /**
     * @var MailService
     */
    protected $_mailService;

    /**
     * @var MainService
     */
    protected $_mainService;

    /**
     * @var array API payment info url success return code
     */
    private $paymentInfoSuccessCodes = array(
        'ATM'     => 2,
        'BNPL'    => 2,
        'CVS'     => 10100073,
        'BARCODE' => 10100073,
    );

    /**
     * @param Context $context
     */
    public function __construct(
        Context $context,

        MainService $mainService,
        MailService $mailService
    )
    {
        $this->_mainService = $mainService;
        $this->_mailService = $mailService;

        parent::__construct($context);
    }

    /**
     * 比對 checkMacValue
     *
     * @param  array  $accountInfo
     * @param  array  $input
     * @return bool
     */
    public function checkMacValue(array $accountInfo, array $input)
    {
        $checkMacValueService = new CheckMacValueService(
            $accountInfo['HashKey'],
            $accountInfo['HashIv'],
            CheckMacValueService::METHOD_SHA256,
        );

        return ($checkMacValueService->generate($input) === $input['CheckMacValue']);
    }

    /**
     * 前往 AIO
     *
     * @param  array  $accountInfo
     * @param  array  $input
     * @param  string $apiUrl
     * @return string
     */
    public function checkout(array $accountInfo, array $input, string $apiUrl)
    {
        $factory = new Factory([
            'hashKey' => $accountInfo['HashKey'],
            'hashIv'  => $accountInfo['HashIv'],
        ]);

        $autoSubmitFormService = $factory->create('AutoSubmitFormWithCmvService');

        $input = $this->checkoutPrepare($input);

        return $autoSubmitFormService->generate($input, $apiUrl);
    }

    /**
     * 整理傳送到 AIO 的參數
     *
     * @param  array $input
     * @return array $send
     */
    public function checkoutPrepare(array $input)
    {
        // Set SDK parameters
        $send = [
            'MerchantID'        => $input['merchantId'],
            'MerchantTradeNo'   => $input['merchantTradeNo'],
            'MerchantTradeDate' => date('Y/m/d H:i:s'),
            'PaymentType'       => 'aio',
            'TotalAmount'       => $input['totalAmount'],
            'TradeDesc'         => UrlService::ecpayUrlEncode($this->_mainService->getModuleDescription()),
            'ItemName'          => $input['itemName'],
            'ChoosePayment'     => $this->getChoosePayment($input['paymentMethod']),
            'EncryptType'       => 1,
            'ReturnURL'         => $input['returnUrl'],
            'ClientBackURL'     => $input['clientBackUrl'],
            'CustomField1'      => $input['enctyOrderId'],

            // todo: 測試用，前景回傳結果
            // 'OrderResultURL'    => $input['returnUrl'],
            // 'ClientRedirectURL' => $input['paymentInfoUrl'],
        ];

        // Set the extend information
        $additionalInformation = $input['additionalInformation'];
        switch ($send['ChoosePayment']) {
            case self::CREDIT:
                $send['UnionPay'] = self::UNIONPAY_DISABLED;

                // 分期付款額外參數
                if ($input['paymentMethod'] == 'ecpay_credit_installment_gateway') {
                    // 期數檢查，若不是啟用期數則帶錯誤值讓 AIO 擋
                    $isValid = (isset($additionalInformation['ecpay_credit_installment']) && $this->isValidCreditInstallment($additionalInformation['ecpay_credit_installment']));
                    $send['CreditInstallment'] = ($isValid) ? str_replace('credit_', '', $additionalInformation['ecpay_credit_installment']) : 'N';
                }
                break;
            case self::ATM:
                $expireDate = $this->_mainService->getPaymentModuleConfig('payment/ecpay_atm_gateway', 'expire_date');
                $send['ExpireDate'] = ($expireDate === '') ? 3 : $expireDate;
                $send['PaymentInfoURL'] = $input['paymentInfoUrl'];
                break;
            case self::CVS:
                $expireDate = $this->_mainService->getPaymentModuleConfig('payment/ecpay_cvs_gateway', 'expire_date');
                $expireDate = ($expireDate === '') ? 7 : $expireDate;
                $send['StoreExpireDate'] = intval($expireDate) * 24 * 60;
                $send['PaymentInfoURL'] = $input['paymentInfoUrl'];
                break;
            case self::BARCODE:
                $expireDate = $this->_mainService->getPaymentModuleConfig('payment/ecpay_barcode_gateway', 'expire_date');
                $send['StoreExpireDate'] = ($expireDate === '') ? 7 : $expireDate;
                $send['PaymentInfoURL'] = $input['paymentInfoUrl'];
                break;
            case self::BNPL:
                $send['PaymentInfoURL'] = $input['paymentInfoUrl'];
                break;
            case self::TWQR:
                $send['NeedExtraPaidInfo'] = 'Y';
                break;
        }
        return $send;
    }

    /**
     * 轉換訂購商品格式符合金流訂單API
     *
     * @param  array   $orderItem
     * @return string  $itemName
     */
    public function convertToPaymentItemName($orderItem)
    {
        $itemName = '';

        foreach ($orderItem as $key => $value) {

            $itemName .= $value['name'] . '#' ;
        }

        return $itemName;
    }

    /**
     * Get the amount
     *
     * @param  mixed $amount Amount
     * @return integer
     */
    public function getAmount($amount = 0)
    {
        return round($amount, 0);
    }

    /**
     * 取出API介接網址
     *
     * @param  string  $action
     * @param  int     $stage
     * @return string  $url
     */
    public function getApiUrl(string $action = 'check_out', int $stage = 1)
    {

        if ($stage == 1) {

            switch ($action) {

                case 'check_out':
                    $url = 'https://payment-stage.ecpay.com.tw/Cashier/AioCheckOut/V5' ;
                break;

                default:
                    $url = '' ;
                break;
            }

        } else {

            switch ($action) {

                case 'check_out':
                    $url = 'https://payment.ecpay.com.tw/Cashier/AioCheckOut/V5' ;
                break;

                default:
                    $url = '' ;
                break;
            }
        }

        return $url;
    }

    /**
     * 取得 AIO 對應的 ChoosePayment
     *
     * @param  string  $paymentMethod
     * @return string  $choosePayment
     */
    public function getChoosePayment(string $paymentMethod)
    {
        $choosePayment = '' ;

        switch ($paymentMethod) {
            case 'ecpay_credit_gateway':
            case 'ecpay_credit_installment_gateway':
                $choosePayment = self::CREDIT ;
                break;
            case 'ecpay_webatm_gateway':
                $choosePayment = self::WEBATM ;
                break;
            case 'ecpay_atm_gateway':
                $choosePayment = self::ATM ;
                break;
            case 'ecpay_cvs_gateway':
                $choosePayment = self::CVS ;
                break;
            case 'ecpay_barcode_gateway':
                $choosePayment = self::BARCODE ;
                break;
            case 'ecpay_applepay_gateway':
                $choosePayment = self::APPLEPAY ;
                break;
            case 'ecpay_twqr_gateway':
                $choosePayment = self::TWQR ;
                break;
            case 'ecpay_bnpl_gateway':
                $choosePayment = self::BNPL ;
                break;
        }

        return $choosePayment ;
    }

    /**
     * 取得分期期數
     *
     * @return array
     */
    public function getCreditInstallments()
    {
        return [
            'credit_3',
            'credit_6',
            'credit_12',
            'credit_18',
            'credit_24',
            'credit_30N',
        ];
    }

    /**
     * 取得分期期數名稱
     *
     * @param  string $key
     * @return string
     */
    public function getCreditInstallmentName(string $key)
    {
        switch ($key) {
            case 'credit_3':
                return __('Credit(3 Periods)');
            case 'credit_6':
                return __('Credit(6 Periods)');
            case 'credit_12':
                return __('Credit(12 Periods)');
            case 'credit_18':
                return __('Credit(18 Periods)');
            case 'credit_24':
                return __('Credit(24 Periods)');
            case 'credit_30N':
                return __('Dream Installment (available only at Bank SinoPac)');
            default:
                return '';
        }
    }

    /**
     * 取得綠界金流
     *
     * @return array
     */
    public function getEcpayAllPayment()
    {
        return [
            'ecpay_webatm_gateway',
            'ecpay_atm_gateway',
            'ecpay_credit_gateway',
            'ecpay_credit_installment_gateway',
            'ecpay_cvs_gateway',
            'ecpay_barcode_gateway',
            'ecpay_applepay_gateway',
            'ecpay_twqr_gateway',
            'ecpay_bnpl_gateway'
        ];
    }

    /**
     * Get obtaining code comment
     *
     * @param  string $pattern  Message pattern
     * @param  array  $feedback AIO feedback
     * @return string
     */
    public function getObtainingCodeComment($pattern = '', $feedback = array())
    {
        // Filter inputs
        $undefinedMessage = 'undefined';
        if (empty($pattern) === true) {
            return $undefinedMessage;
        }

        $list = array(
            'PaymentType',
            'RtnCode',
            'RtnMsg',
            'BankCode',
            'vAccount',
            'ExpireDate',
            'PaymentNo',
            'Barcode1',
            'Barcode2',
            'Barcode3',
            'Barcode3',
            'BNPLTradeNo',
            'BNPLInstallment'
        );
        $inputs = $this->only($feedback, $list);

        $type = $this->getPaymentMethod($inputs['PaymentType']);
        switch ($type) {
            case 'ATM':
                return sprintf(
                    $pattern,
                    $inputs['RtnCode'],
                    $inputs['RtnMsg'],
                    $inputs['BankCode'],
                    $inputs['vAccount'],
                    $inputs['ExpireDate']
                );
                break;
            case 'CVS':
                return sprintf(
                    $pattern,
                    $inputs['RtnCode'],
                    $inputs['RtnMsg'],
                    $inputs['PaymentNo'],
                    $inputs['ExpireDate']
                );
                break;
            case 'BARCODE':
                return sprintf(
                    $pattern,
                    $inputs['RtnCode'],
                    $inputs['RtnMsg'],
                    $inputs['ExpireDate'],
                    $inputs['Barcode1'],
                    $inputs['Barcode2'],
                    $inputs['Barcode3']
                );
                break;
            case 'BNPL':
                return sprintf(
                    $pattern,
                    $inputs['RtnCode'],
                    $inputs['RtnMsg'],
                    $inputs['BNPLTradeNo'],
                    $inputs['BNPLInstallment'],
                );
                break;
            default:
                break;
        }
        return $undefinedMessage;
    }

    /**
     * Get the paymentinfo response
     *
     * @param  string  $orderId
     * @param  array   $paymentInfo
     * @return array
     */
    public function getPaymentInfoResponse(string $orderId, array $paymentInfo)
    {
        $paymentMethod = $this->getPaymentMethod($paymentInfo['PaymentType']);
        $successCode = $this->paymentInfoSuccessCodes[$paymentMethod];

        if (intval($paymentInfo['RtnCode']) == $successCode) {
            switch ($paymentMethod) {
                case self::ATM:
                    $pattern = __('Getting Code Result : (%s)%s, Bank Code : %s, Virtual Account : %s, Payment Deadline : %s');
                    break;
                case self::CVS:
                    $pattern = __('Getting Code Result : (%s)%s, Trade Code : %s, Payment Deadline : %s');
                    break;
                case self::BARCODE:
                    $pattern = __('Getting Code Result : (%s)%s, Payment Deadline : %s, BARCODE 1 : %s, BARCODE 2 : %s, BARCODE 3 : %s');
                    break;
                case self::BNPL:
                    $pattern = __('Getting Code Result : (%s)%s, BNPL Trade No : %s, BNPL Installment : %s');
                    break;
            }

            return [
                'status'  => 1,
                'comment' => $this->getObtainingCodeComment($pattern, $paymentInfo)
            ];
        } else {
            return [
                'status'  => 0,
                'comment' => sprintf(__('Failed To Getting Code, error : %s'), $paymentInfo['RtnMsg'])
            ];
        }
    }

    /**
     * Get the paymentinfo template values
     *
     * @param  array   $paymentInfo
     * @return array   $templateValues
     */
    public function getPaymentInfoTemplateValues(array $paymentInfo)
    {
        $paymentMethod = $this->getPaymentMethod($paymentInfo['PaymentType']);

        $templateValues = [];
        switch($paymentMethod) {
            case 'ATM':
                $templateValues = [
                    'bank_code'   => $paymentInfo['BankCode'],
                    'atm_no'      => implode(' ', str_split($paymentInfo['vAccount'], 4)),
                    'expire_date' => $paymentInfo['ExpireDate'],
                ];
                break;
            case 'CVS':
                $templateValues = [
                    'cvs_no'      => $paymentInfo['PaymentNo'],
                    'expire_date' => $paymentInfo['ExpireDate'],
                ];
                break;
            case 'BARCODE':
                $templateValues = [
                    'barcode_one'   => $paymentInfo['Barcode1'],
                    'barcode_two'   => $paymentInfo['Barcode2'],
                    'barcode_three' => $paymentInfo['Barcode3'],
                    'expire_date'   => $paymentInfo['ExpireDate'],
                ];
                break;
        }

        return $templateValues;
    }

    /**
     * Get the payment method from the payment type
     *
     * @param  string $paymentType Payment type
     * @return string|bool
     */
    public function getPaymentMethod($paymentType = '')
    {
        // Filter inputs
        if (empty($paymentType) === true) {
            return false;
        }

        $pieces = explode('_', $paymentType);
        return $this->getSdkPaymentMethod($pieces[0]);
    }

    /**
     * Get AIO returnurl response
     *
     * @param  array  $paymentInfo
     * @return array
     */
    public function getReturnUrlResponse(array $paymentInfo)
    {
        if (intval($paymentInfo['RtnCode']) === self::PAYMENT_SUCCESS_CODE) {
            return [
                'status'  => Order::STATE_PROCESSING,
                'comment' => sprintf(__('ECPay Payment Result : (%s)%s, MerchantTradeNo :%s'), $paymentInfo['RtnCode'], $paymentInfo['RtnMsg'], $paymentInfo['MerchantTradeNo'])
            ];
        } else {
            return [
                'status'  => Order::STATE_CANCELED,
                'comment' => sprintf(__('Failed To Pay, error : %s'), $paymentInfo['RtnMsg'])
            ];
        }
    }

    /**
     * 取出測試帳號KEY IV
     *
     * @return array
     */
    public function getStageAccount()
    {
        $info = [
            'MerchantId' => '3002607',
            'HashKey'    => 'pwFHCqoQZGmho4w6',
            'HashIv'     => 'EkRm7iFT261dpevs',
        ];

        return $info;
    }

    /**
     * 取得可用的分期期數
     *
     * @return array
     */
    public function getValidCreditInstallments()
    {
        $creditInstallments = $this->_mainService->getPaymentModuleConfig('payment/ecpay_credit_installment_gateway', 'credit_installment');

        if (empty($creditInstallments)) {
            return [];
        }

        $trimed = trim($creditInstallments);
        return explode(',', $trimed);
    }

    /**
     * 判斷是否為綠界金流
     *
     * @param  string $paymentMethod
     * @return bool
     */
    public function isEcpayPayment(string $paymentMethod)
    {
        return in_array($paymentMethod, $this->getEcpayAllPayment());
    }

    /**
     * 檢查是否為可用的分期期數
     *
     * @param  string $choosenCreditInstallment
     * @return bool
     */
    public function isValidCreditInstallment(string $choosenCreditInstallment)
    {
        $creditInstallments = $this->getValidCreditInstallments();
        return (in_array($choosenCreditInstallment, $creditInstallments));
    }

    /**
     * Filter the inputs
     *
     * @param array $source Source data
     * @param array $whiteList White list
     * @return array
     */
    public function only($source = array(), $whiteList = array())
    {
        $variables = array();

        // Return empty array when do not set white list
        if (empty($whiteList) === true) {
            return $source;
        }

        foreach ($whiteList as $name) {
            if (isset($source[$name]) === true) {
                $variables[$name] = $source[$name];
            } else {
                $variables[$name] = '';
            }
        }
        return $variables;
    }

    /**
     * Send payment response info mail
     * @param array $mailData
     */
    public function sendMail($mailData = array())
    {
        $this->_mailService->send($mailData);
    }

    /**
     * Validate the amounts
     *
     * @param  mixed $source Source amount
     * @param  mixed $target Target amount
     * @return boolean
     */
    public function validAmount($source = 0, $target = 0)
    {
        return ($this->getAmount($source) === $this->getAmount($target));
    }

    /**
     * Get SDK payment method
     *
     * @param  string $paymentType payment type
     * @return string|bool
     */
    private function getSdkPaymentMethod($paymentType = '')
    {
        // Filter inputs
        if (empty($paymentType) === true) {
            return false;
        }

        $lower = strtolower($paymentType);
        switch ($lower) {
            case 'all':
                $sdkPayment = self::ALL;
                break;
            case 'credit':
                $sdkPayment = self::CREDIT;
                break;
            case 'webatm':
                $sdkPayment = self::WEBATM;
                break;
            case 'atm':
                $sdkPayment = self::ATM;
                break;
            case 'cvs':
                $sdkPayment = self::CVS;
                break;
            case 'barcode':
                $sdkPayment = self::BARCODE;
                break;
            case 'applepay':
                $sdkPayment = self::APPLEPAY;
                break;
            case 'twqr':
                $sdkPayment = self::TWQR;
                break;
            case 'bnpl':
                $sdkPayment = self::BNPL;
                break;
            default:
                $sdkPayment = '';
                break;
        }
        return $sdkPayment;
    }
}