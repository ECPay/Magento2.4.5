<?php

namespace Ecpay\General\Helper\Services\Config;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\UrlInterface;
use Magento\Directory\Helper\Data;

use Ecpay\General\Model\EcpayLogisticFactory;
use Ecpay\General\Helper\Services\Common\OrderService;
use Ecpay\General\Helper\Services\Config\MainService;
use Ecpay\Sdk\Factories\Factory;

class LogisticService extends AbstractHelper
{
    protected $_urlInterface;

    protected $_ecpayLogisticFactory;

    protected $_orderService;
    protected $_mainService;

    protected $_directoryHelper;

    /**
     * @param Context $context
     */
    public function __construct(
        Context $context,
        UrlInterface $urlInterface,

        EcpayLogisticFactory $ecpayLogisticFactory,

        OrderService $orderService,
        MainService $mainService,
        Data $directoryHelper
    )
    {
        $this->_urlInterface = $urlInterface;

        $this->_ecpayLogisticFactory = $ecpayLogisticFactory;

        $this->_orderService = $orderService;
        $this->_mainService = $mainService;
        $this->_directoryHelper = $directoryHelper;

        parent::__construct($context);
    }

    /**
     * 取出測試帳號KEY IV
     * @param  string  $type
     * @return array
     */
    public function getStageAccount($type = '')
    {
        switch ($type) {

            case 'B2C':
                $info = [
                    'MerchantId'    => '2000132',
                    'HashKey'       => '5294y06JbISpM5x9',
                    'HashIv'        => 'v77hoKGq4kWxNNIS',
                ] ;
            break;

            case 'C2C':
                $info = [
                    'MerchantId'    => '2000933',
                    'HashKey'       => 'XBERn1YOvpM9nfZc',
                    'HashIv'        => 'h1ONHk4P4yqbl5LK',
                ] ;
            break;

            default:
                $info = [
                    'MerchantId'    => '2000933',
                    'HashKey'       => 'XBERn1YOvpM9nfZc',
                    'HashIv'        => 'h1ONHk4P4yqbl5LK',
                ] ;
            break;
        }

        return $info;
    }

    /**
     * 取出API介接網址
     * @param  string  $action
     * @param  string  $stage
     * @return string  $url
     */
    public function getApiUrl($action = 'map', $stage = 1, $type = 'C2C', $shippingMethod = '')
    {

        if($stage == 1){

            switch ($action) {

                case 'map':
                    $url = 'https://logistics-stage.ecpay.com.tw/Express/map' ;
                break;

                case 'create':
                    $url = 'https://logistics-stage.ecpay.com.tw/Express/Create' ;
                break;

                case 'print':

                    if ($type == 'C2C') {

                        switch ($shippingMethod) {

                            case 'ecpaylogisticcsvunimart_ecpaylogisticcsvunimart':
                                $url = 'https://logistics-stage.ecpay.com.tw/Express/PrintUniMartC2COrderInfo' ;
                            break;

                            case 'ecpaylogisticcsvfamily_ecpaylogisticcsvfamily':
                                $url = 'https://logistics-stage.ecpay.com.tw/Express/PrintFAMIC2COrderInfo' ;
                            break;

                            case 'ecpaylogisticcsvhilife_ecpaylogisticcsvhilife':
                                $url = 'https://logistics-stage.ecpay.com.tw/Express/PrintHILIFEC2COrderInfo' ;
                            break;

                            case 'ecpaylogisticcsvokmart_ecpaylogisticcsvokmart':
                                $url = 'https://logistics-stage.ecpay.com.tw/Express/PrintOKMARTC2COrderInfo' ;
                            break;

                            case 'ecpaylogistichometcat_ecpaylogistichometcat':
                            case 'ecpaylogistichomepost_ecpaylogistichomepost':
                                $url = 'https://logistics-stage.ecpay.com.tw/helper/printTradeDocument' ;
                            break;

                            default:
                                $url = '' ;
                            break;
                        }

                    } else if ($type == 'B2C') {

                        switch ($shippingMethod) {

                            case 'ecpaylogisticcsvunimart_ecpaylogisticcsvunimart':
                            case 'ecpaylogisticcsvfamily_ecpaylogisticcsvfamily':
                            case 'ecpaylogisticcsvhilife_ecpaylogisticcsvhilife':
                            case 'ecpaylogistichometcat_ecpaylogistichometcat':
                            case 'ecpaylogistichomepost_ecpaylogistichomepost':
                                $url = 'https://logistics-stage.ecpay.com.tw/helper/printTradeDocument' ;
                            break;
                            default:
                                $url = '' ;
                            break;
                        }
                    }

                break;

                default:
                    $url = '' ;
                break;
            }

        } else {

            switch ($action) {

                case 'map':
                    $url = 'https://logistics.ecpay.com.tw/Express/map' ;
                break;

                case 'create':
                    $url = 'https://logistics.ecpay.com.tw/Express/Create' ;
                break;

                case 'print':

                    if ($type == 'C2C') {

                        switch ($shippingMethod) {

                            case 'ecpaylogisticcsvunimart_ecpaylogisticcsvunimart':
                                $url = 'https://logistics.ecpay.com.tw/Express/PrintUniMartC2COrderInfo' ;
                            break;

                            case 'ecpaylogisticcsvfamily_ecpaylogisticcsvfamily':
                                $url = 'https://logistics.ecpay.com.tw/Express/PrintFAMIC2COrderInfo' ;
                            break;

                            case 'ecpaylogisticcsvhilife_ecpaylogisticcsvhilife':
                                $url = 'https://logistics.ecpay.com.tw/Express/PrintHILIFEC2COrderInfo' ;
                            break;

                            case 'ecpaylogisticcsvokmart_ecpaylogisticcsvokmart':
                                $url = 'https://logistics.ecpay.com.tw/Express/PrintOKMARTC2COrderInfo' ;
                            break;

                            case 'ecpaylogistichometcat_ecpaylogistichometcat':
                            case 'ecpaylogistichomepost_ecpaylogistichomepost':
                                $url = 'https://logistics.ecpay.com.tw/helper/printTradeDocument' ;
                            break;

                            default:
                                $url = '' ;
                            break;
                        }

                    } else if ($type == 'B2C') {

                        switch ($shippingMethod) {

                            case 'ecpaylogisticcsvunimart_ecpaylogisticcsvunimart':
                            case 'ecpaylogisticcsvfamily_ecpaylogisticcsvfamily':
                            case 'ecpaylogisticcsvhilife_ecpaylogisticcsvhilife':
                            case 'ecpaylogistichometcat_ecpaylogistichometcat':
                            case 'ecpaylogistichomepost_ecpaylogistichomepost':
                                $url = 'https://logistics.ecpay.com.tw/helper/printTradeDocument' ;
                            break;
                            default:
                                $url = '' ;
                            break;
                        }
                    }

                break;

                default:
                    $url = '' ;
                break;
            }
        }

        return $url ;
    }

    /**
     * 取得綠界物流
     * @return array
     */
    public function getEcpayAllLogistics()
    {
        return [
            'ecpaylogisticcsvunimart_ecpaylogisticcsvunimart',
            'ecpaylogisticcsvfamily_ecpaylogisticcsvfamily',
            'ecpaylogisticcsvhilife_ecpaylogisticcsvhilife',
            'ecpaylogisticcsvokmart_ecpaylogisticcsvokmart',
            'ecpaylogistichometcat_ecpaylogistichometcat',
            'ecpaylogistichomepost_ecpaylogistichomepost'
        ];
    }

    /**
     * 取得綠界宅配物流
     * @return array
     */
    public function getEcpayHomeLogistics()
    {
        return [
            'ecpaylogistichometcat_ecpaylogistichometcat',
            'ecpaylogistichomepost_ecpaylogistichomepost'
        ];
    }

    /**
     * 取得綠界超商物流
     * @return array
     */
    public function getEcpayCvsLogistics()
    {
        return [
            'ecpaylogisticcsvunimart_ecpaylogisticcsvunimart',
            'ecpaylogisticcsvfamily_ecpaylogisticcsvfamily',
            'ecpaylogisticcsvhilife_ecpaylogisticcsvhilife',
            'ecpaylogisticcsvokmart_ecpaylogisticcsvokmart'
        ];
    }

    /**
     * 取出CVS API的物流子類型格式
     * @param  string  $type
     * @param  string  $shippingMethod
     * @return string  $logisticsSubType
     */
    public function getCvsLogisticsSubType($type = 'C2C', $shippingMethod = '')
    {

        switch ($shippingMethod) {

            case 'ecpaylogisticcsvunimart_ecpaylogisticcsvunimart':

                $logisticsSubType = ($type == 'C2C') ? 'UNIMARTC2C' : 'UNIMART' ;

            break;
            case 'ecpaylogisticcsvfamily_ecpaylogisticcsvfamily':

                $logisticsSubType = ($type == 'C2C') ? 'FAMIC2C' : 'FAMI' ;

            break;
            case 'ecpaylogisticcsvhilife_ecpaylogisticcsvhilife':

                $logisticsSubType = ($type == 'C2C') ? 'HILIFEC2C' : 'HILIFE' ;

            break;
            case 'ecpaylogisticcsvokmart_ecpaylogisticcsvokmart':

                $logisticsSubType = ($type == 'C2C') ? 'OKMARTC2C' : '' ;

            break;

            default:
                $logisticsSubType = '' ;
            break;
        }

        return $logisticsSubType;
    }

    /**
     * 取得允許的 CVS 訂單最大金額
     * @param  mixed $maxOrderAmount
     * @return int
     */
    public function getCvsAvailableMaxAmount($maxOrderAmount)
    {
        $default = 19999;

        if ($maxOrderAmount == '' || $maxOrderAmount == NULL || $maxOrderAmount == 0) {
            return $default;
        }

        if (intval($maxOrderAmount) <= $default) {
            return $maxOrderAmount;
        }

        return $default;
    }

    /**
     * 取出HOME API的物流子類型格式
     * @param  string  $type
     * @param  string  $shippingMethod
     * @return string  $logisticsSubType
     */
    public function getHomeLogisticsSubType($shippingMethod = '')
    {

        switch ($shippingMethod) {

            case 'ecpaylogistichometcat_ecpaylogistichometcat':

                $logisticsSubType = 'TCAT' ;

            break;
            case 'ecpaylogistichomepost_ecpaylogistichomepost':

                $logisticsSubType = 'POST' ;

            break;

            default:
                $logisticsSubType = '' ;
            break;
        }

        return $logisticsSubType;
    }

    /**
     * 取出區域
     * @param  int  $zipCode
     * @return string  $return
     */
    public function getPostalName($zipCode = 0)
    {
        $address = [
            0   => '',
            100 => '臺北市中正區',
            103 => '臺北市大同區',
            104 => '臺北市中山區',
            105 => '臺北市松山區',
            106 => '臺北市大安區',
            108 => '臺北市萬華區',
            110 => '臺北市信義區',
            111 => '臺北市士林區',
            112 => '臺北市北投區',
            114 => '臺北市內湖區',
            115 => '臺北市南港區',
            116 => '臺北市文山區',
            200 => '基隆市仁愛區',
            201 => '基隆市信義區',
            202 => '基隆市中正區',
            203 => '基隆市中山區',
            204 => '基隆市安樂區',
            205 => '基隆市暖暖區',
            206 => '基隆市七堵區',
            207 => '新北市萬里區',
            208 => '新北市金山區',
            220 => '新北市板橋區',
            221 => '新北市汐止區',
            222 => '新北市深坑區',
            223 => '新北市石碇區',
            224 => '新北市瑞芳區',
            226 => '新北市平溪區',
            227 => '新北市雙溪區',
            228 => '新北市貢寮區',
            231 => '新北市新店區',
            232 => '新北市坪林區',
            233 => '新北市烏來區',
            234 => '新北市永和區',
            235 => '新北市中和區',
            236 => '新北市土城區',
            237 => '新北市三峽區',
            238 => '新北市樹林區',
            239 => '新北市鶯歌區',
            241 => '新北市三重區',
            242 => '新北市新莊區',
            243 => '新北市泰山區',
            244 => '新北市林口區',
            247 => '新北市蘆洲區',
            248 => '新北市五股區',
            249 => '新北市八里區',
            251 => '新北市淡水區',
            252 => '新北市三芝區',
            253 => '新北市石門區',
            260 => '宜蘭縣宜蘭市',
            261 => '宜蘭縣頭城鎮',
            262 => '宜蘭縣礁溪鄉',
            263 => '宜蘭縣壯圍鄉',
            264 => '宜蘭縣員山鄉',
            265 => '宜蘭縣羅東鎮',
            266 => '宜蘭縣三星鄉',
            267 => '宜蘭縣大同鄉',
            268 => '宜蘭縣五結鄉',
            269 => '宜蘭縣冬山鄉',
            270 => '宜蘭縣蘇澳鎮',
            272 => '宜蘭縣南澳鄉',
            300 => '新竹市',
            302 => '新竹縣竹北市',
            303 => '新竹縣湖口鄉',
            304 => '新竹縣新豐鄉',
            305 => '新竹縣新埔鎮',
            306 => '新竹縣關西鎮',
            307 => '新竹縣芎林鄉',
            308 => '新竹縣寶山鄉',
            310 => '新竹縣竹東鎮',
            311 => '新竹縣五峰鄉',
            312 => '新竹縣橫山鄉',
            313 => '新竹縣尖石鄉',
            314 => '新竹縣北埔鄉',
            315 => '新竹縣峨眉鄉',
            320 => '桃園縣中壢市',
            324 => '桃園縣平鎮市',
            325 => '桃園縣龍潭鄉',
            326 => '桃園縣楊梅鎮',
            327 => '桃園縣新屋鄉',
            328 => '桃園縣觀音鄉',
            330 => '桃園縣桃園市',
            333 => '桃園縣龜山鄉',
            334 => '桃園縣八德市',
            335 => '桃園縣大溪鎮',
            336 => '桃園縣復興鄉',
            337 => '桃園縣大園鄉',
            338 => '桃園縣蘆竹鄉',
            350 => '苗栗縣竹南鎮',
            351 => '苗栗縣頭份鎮',
            352 => '苗栗縣三灣鄉',
            353 => '苗栗縣南庄鄉',
            354 => '苗栗縣獅潭鄉',
            356 => '苗栗縣後龍鎮',
            357 => '苗栗縣通霄鎮',
            358 => '苗栗縣苑裡鎮',
            360 => '苗栗縣苗栗市',
            361 => '苗栗縣造橋鄉',
            362 => '苗栗縣頭屋鄉',
            363 => '苗栗縣公館鄉',
            364 => '苗栗縣大湖鄉',
            365 => '苗栗縣泰安鄉',
            366 => '苗栗縣銅鑼鄉',
            367 => '苗栗縣三義鄉',
            368 => '苗栗縣西湖鄉',
            369 => '苗栗縣卓蘭鎮',
            400 => '臺中市中區',
            401 => '臺中市東區',
            402 => '臺中市南區',
            403 => '臺中市西區',
            404 => '臺中市北區',
            406 => '臺中市北屯區',
            407 => '臺中市西屯區',
            408 => '臺中市南屯區',
            411 => '臺中市太平區',
            412 => '臺中市大里區',
            413 => '臺中市霧峰區',
            414 => '臺中市烏日區',
            420 => '臺中市豐原區',
            421 => '臺中市后里區',
            422 => '臺中市石岡區',
            423 => '臺中市東勢區',
            424 => '臺中市和平區',
            426 => '臺中市新社區',
            427 => '臺中市潭子區',
            428 => '臺中市大雅區',
            429 => '臺中市神岡區',
            432 => '臺中市大肚區',
            433 => '臺中市沙鹿區',
            434 => '臺中市龍井區',
            435 => '臺中市梧棲區',
            436 => '臺中市清水區',
            437 => '臺中市大甲區',
            438 => '臺中市外埔區',
            439 => '臺中市大安區',
            500 => '彰化縣彰化市',
            502 => '彰化縣芬園鄉',
            503 => '彰化縣花壇鄉',
            504 => '彰化縣秀水鄉',
            505 => '彰化縣鹿港鎮',
            506 => '彰化縣福興鄉',
            507 => '彰化縣線西鄉',
            508 => '彰化縣和美鎮',
            509 => '彰化縣伸港鄉',
            510 => '彰化縣員林鎮',
            511 => '彰化縣社頭鄉',
            512 => '彰化縣永靖鄉',
            513 => '彰化縣埔心鄉',
            514 => '彰化縣溪湖鎮',
            515 => '彰化縣大村鄉',
            516 => '彰化縣埔鹽鄉',
            520 => '彰化縣田中鎮',
            521 => '彰化縣北斗鎮',
            522 => '彰化縣田尾鄉',
            523 => '彰化縣埤頭鄉',
            524 => '彰化縣溪州鄉',
            525 => '彰化縣竹塘鄉',
            526 => '彰化縣二林鎮',
            527 => '彰化縣大城鄉',
            528 => '彰化縣芳苑鄉',
            530 => '彰化縣二水鄉',
            540 => '南投縣南投市',
            541 => '南投縣中寮鄉',
            542 => '南投縣草屯鎮',
            544 => '南投縣國姓鄉',
            545 => '南投縣埔里鎮',
            546 => '南投縣仁愛鄉',
            551 => '南投縣名間鄉',
            552 => '南投縣集集鎮',
            553 => '南投縣水里鄉',
            555 => '南投縣魚池鄉',
            556 => '南投縣信義鄉',
            557 => '南投縣竹山鎮',
            558 => '南投縣鹿谷鄉',
            600 => '嘉義市',
            602 => '嘉義縣番路鄉',
            603 => '嘉義縣梅山鄉',
            604 => '嘉義縣竹崎鄉',
            605 => '嘉義縣阿里山',
            606 => '嘉義縣中埔鄉',
            607 => '嘉義縣大埔鄉',
            608 => '嘉義縣水上鄉',
            611 => '嘉義縣鹿草鄉',
            612 => '嘉義縣太保市',
            613 => '嘉義縣朴子市',
            614 => '嘉義縣東石鄉',
            615 => '嘉義縣六腳鄉',
            616 => '嘉義縣新港鄉',
            621 => '嘉義縣民雄鄉',
            622 => '嘉義縣大林鎮',
            623 => '嘉義縣溪口鄉',
            624 => '嘉義縣義竹鄉',
            625 => '嘉義縣布袋鎮',
            630 => '雲林縣斗南鎮',
            631 => '雲林縣大埤鄉',
            632 => '雲林縣虎尾鎮',
            633 => '雲林縣土庫鎮',
            634 => '雲林縣褒忠鄉',
            635 => '雲林縣東勢鄉',
            636 => '雲林縣臺西鄉',
            637 => '雲林縣崙背鄉',
            638 => '雲林縣麥寮鄉',
            640 => '雲林縣斗六市',
            643 => '雲林縣林內鄉',
            646 => '雲林縣古坑鄉',
            647 => '雲林縣莿桐鄉',
            648 => '雲林縣西螺鎮',
            649 => '雲林縣二崙鄉',
            651 => '雲林縣北港鎮',
            652 => '雲林縣水林鄉',
            653 => '雲林縣口湖鄉',
            654 => '雲林縣四湖鄉',
            655 => '雲林縣元長鄉',
            700 => '臺南市中西區',
            701 => '臺南市東區',
            702 => '臺南市南區',
            704 => '臺南市北區',
            708 => '臺南市安平區',
            709 => '臺南市安南區',
            710 => '臺南市永康區',
            711 => '臺南市歸仁區',
            712 => '臺南市新化區',
            713 => '臺南市左鎮區',
            714 => '臺南市玉井區',
            715 => '臺南市楠西區',
            716 => '臺南市南化區',
            717 => '臺南市仁德區',
            718 => '臺南市關廟區',
            719 => '臺南市龍崎區',
            720 => '臺南市官田區',
            721 => '臺南市麻豆區',
            722 => '臺南市佳里區',
            723 => '臺南市西港區',
            724 => '臺南市七股區',
            725 => '臺南市將軍區',
            726 => '臺南市學甲區',
            727 => '臺南市北門區',
            730 => '臺南市新營區',
            731 => '臺南市後壁區',
            732 => '臺南市白河區',
            733 => '臺南市東山區',
            734 => '臺南市六甲區',
            735 => '臺南市下營區',
            736 => '臺南市柳營區',
            737 => '臺南市鹽水區',
            741 => '臺南市善化區',
            742 => '臺南市大內區',
            743 => '臺南市山上區',
            744 => '臺南市新市區',
            745 => '臺南市安定區',
            800 => '高雄市新興區',
            801 => '高雄市前金區',
            802 => '高雄市苓雅區',
            803 => '高雄市鹽埕區',
            804 => '高雄市鼓山區',
            805 => '高雄市旗津區',
            806 => '高雄市前鎮區',
            807 => '高雄市三民區',
            811 => '高雄市楠梓區',
            812 => '高雄市小港區',
            813 => '高雄市左營區',
            814 => '高雄市仁武區',
            815 => '高雄市大社區',
            820 => '高雄市岡山區',
            821 => '高雄市路竹區',
            822 => '高雄市阿蓮區',
            823 => '高雄市田寮區',
            824 => '高雄市燕巢區',
            825 => '高雄市橋頭區',
            826 => '高雄市梓官區',
            827 => '高雄市彌陀區',
            828 => '高雄市永安區',
            829 => '高雄市湖內區',
            830 => '高雄市鳳山區',
            831 => '高雄市大寮區',
            832 => '高雄市林園區',
            833 => '高雄市鳥松區',
            840 => '高雄市大樹區',
            842 => '高雄市旗山區',
            843 => '高雄市美濃區',
            844 => '高雄市六龜區',
            845 => '高雄市內門區',
            846 => '高雄市杉林區',
            847 => '高雄市甲仙區',
            848 => '高雄市桃源區',
            849 => '高雄市那瑪夏區',
            851 => '高雄市茂林區',
            852 => '高雄市茄萣區',
            880 => '澎湖縣馬公市',
            881 => '澎湖縣西嶼鄉',
            882 => '澎湖縣望安鄉',
            883 => '澎湖縣七美鄉',
            884 => '澎湖縣白沙鄉',
            885 => '澎湖縣湖西鄉',
            900 => '屏東縣屏東市',
            901 => '屏東縣三地門',
            902 => '屏東縣霧臺鄉',
            903 => '屏東縣瑪家鄉',
            904 => '屏東縣九如鄉',
            905 => '屏東縣里港鄉',
            906 => '屏東縣高樹鄉',
            907 => '屏東縣鹽埔鄉',
            908 => '屏東縣長治鄉',
            909 => '屏東縣麟洛鄉',
            911 => '屏東縣竹田鄉',
            912 => '屏東縣內埔鄉',
            913 => '屏東縣萬丹鄉',
            920 => '屏東縣潮州鎮',
            921 => '屏東縣泰武鄉',
            922 => '屏東縣來義鄉',
            923 => '屏東縣萬巒鄉',
            924 => '屏東縣崁頂鄉',
            925 => '屏東縣新埤鄉',
            926 => '屏東縣南州鄉',
            927 => '屏東縣林邊鄉',
            928 => '屏東縣東港鎮',
            929 => '屏東縣琉球鄉',
            931 => '屏東縣佳冬鄉',
            932 => '屏東縣新園鄉',
            940 => '屏東縣枋寮鄉',
            941 => '屏東縣枋山鄉',
            942 => '屏東縣春日鄉',
            943 => '屏東縣獅子鄉',
            944 => '屏東縣車城鄉',
            945 => '屏東縣牡丹鄉',
            946 => '屏東縣恆春鎮',
            947 => '屏東縣滿州鄉',
            950 => '臺東縣臺東市',
            951 => '臺東縣綠島鄉',
            952 => '臺東縣蘭嶼鄉',
            953 => '臺東縣延平鄉',
            954 => '臺東縣卑南鄉',
            955 => '臺東縣鹿野鄉',
            956 => '臺東縣關山鎮',
            957 => '臺東縣海端鄉',
            958 => '臺東縣池上鄉',
            959 => '臺東縣東河鄉',
            961 => '臺東縣成功鎮',
            962 => '臺東縣長濱鄉',
            963 => '臺東縣太麻里',
            964 => '臺東縣金峰鄉',
            965 => '臺東縣大武鄉',
            966 => '臺東縣達仁鄉',
            970 => '花蓮縣花蓮市',
            971 => '花蓮縣新城鄉',
            972 => '花蓮縣秀林鄉',
            973 => '花蓮縣吉安鄉',
            974 => '花蓮縣壽豐鄉',
            975 => '花蓮縣鳳林鎮',
            976 => '花蓮縣光復鄉',
            977 => '花蓮縣豐濱鄉',
            978 => '花蓮縣瑞穗鄉',
            979 => '花蓮縣萬榮鄉',
            981 => '花蓮縣玉里鎮',
            982 => '花蓮縣卓溪鄉',
            983 => '花蓮縣富里鄉',
            890 => '金門縣金沙鎮',
            891 => '金門縣金湖鎮',
            892 => '金門縣金寧鄉',
            893 => '金門縣金城鎮',
            894 => '金門縣烈嶼鄉',
            896 => '金門縣烏坵鄉',
            209 => '連江縣南竿鄉',
            210 => '連江縣北竿鄉',
            211 => '連江縣莒光鄉',
            212 => '連江縣東引鄉',
            817 => '南海諸島東沙',
            819 => '南海諸島南沙',
            290 => '釣魚台列嶼'
        ];

        return (isset($address[$zipCode])) ? $address[$zipCode] : '' ;
    }

    /**
     * 轉換訂購商品格式符合物流訂單API
     * @param  array  $orderItem
     * @return string  $itemName
     */
    public function getItemName($orderItem)
    {
        $itemName = '';

        foreach ($orderItem as $key => $value) {

            $itemName .= $value['name'] . ' ' ;
        }

        return $itemName;
    }

    /**
     * 判斷是否為綠界物流
     * @param  string $shippingMethod
     * @return bool
     */
    public function isEcpayLogistics(string $shippingMethod)
    {
        return in_array($shippingMethod, $this->getEcpayAllLogistics());
    }

    /**
     * 判斷是否為綠界宅配物流
     * @param  string $shippingMethod
     * @return bool
     */
    public function isEcpayHomeLogistics(string $shippingMethod)
    {
        return in_array($shippingMethod, $this->getEcpayHomeLogistics());
    }

    /**
     * 判斷是否為綠界超商物流
     * @param  string $shippingMethod
     * @return bool
     */
    public function isEcpayCvsLogistics(string $shippingMethod)
    {
        return in_array($shippingMethod, $this->getEcpayCvsLogistics());
    }

    /**
     * 超商電子地圖
     *
     * @param  array  $accountInfo
     * @param  array  $input
     * @param  string $apiUrl
     * @return string
     */
    public function mapToEcpay(array $accountInfo, array $input, string $apiUrl)
    {
        $factory = new Factory([
            'hashKey'    => $accountInfo['HashKey'],
            'hashIv'     => $accountInfo['HashIv'],
            'hashMethod' => 'md5',
        ]);

        $autoSubmitFormService = $factory->create('AutoSubmitFormWithCmvService');

        $input = [
            'MerchantID'        => $input['merchantId'],
            'MerchantTradeNo'   => $input['merchantTradeNo'],
            'LogisticsType'     => 'CVS',
            'LogisticsSubType'  => $input['logisticsSubType'],
            'IsCollection'      => $input['isCollection'],
            'ServerReplyURL'    => $input['serverReplyURL'],
        ];

        echo $autoSubmitFormService->generate($input, $apiUrl);
    }

    /**
     * 建立物流訂單
     * @param int $orderId
     */
    public function logisticCreateOrder(int $orderId)
    {
        // 物流單建立狀態
        $ecpayShippingTag = $this->_orderService->getEcpayShippingTag($orderId);
        if ($ecpayShippingTag == 0) {
        } else {
            return ['code' => '2006'];
        }

        // 物流方式
        $shippingMethod = $this->_orderService->getShippingMethod($orderId);
        $this->_logger->debug('LogisticService $shippingMethod:'.$shippingMethod);

        // 判斷是否為綠界物流
        if ($this->isEcpayLogistics($shippingMethod)) {

            // 取出訂單總金額
            $baseGrandTota = $this->_orderService->getBaseGrandTotal($orderId);
            $goodsAmount = intval(round($baseGrandTota, 0));
            $this->_logger->debug('LogisticService goodsAmount:'. print_r($goodsAmount,true));

            // 收件人姓名
            $receiverName = $this->_orderService->getShippingName($orderId);
            $this->_logger->debug('LogisticService receiverName:'. print_r($receiverName,true));

            // 收件人電話
            $receiverCellPhone = $this->_orderService->getShippingTelephone($orderId);
            $this->_logger->debug('LogisticService receiverCellPhone:'. print_r($receiverCellPhone,true));

            // 貨到付款判斷
            $paymentMethod = $this->_orderService->getPaymentMethod($orderId);
            $this->_logger->debug('LogisticService paymentMethod:'.$paymentMethod);

            if ($paymentMethod == 'cashondelivery') {
                $isCollection = 'Y';
            } else {
                $isCollection = 'N';
            }

            // 取出是否為測試模式
            $logisticStage = $this->_mainService->getLogisticConfig('enabled_logistic_stage') ;
            $this->_logger->debug('LogisticService logisticStage:'. print_r($logisticStage,true));

            // 取出CvsType
            $logisticCvsType = $this->_mainService->getLogisticConfig('logistic_cvs_type') ;
            $this->_logger->debug('LogisticService logisticCvsType:'. print_r($logisticCvsType,true));

            // 取出 URL
            $apiUrl = $this->getApiUrl('create', $logisticStage, $logisticCvsType);
            $this->_logger->debug('LogisticService apiUrl:'. print_r($apiUrl,true));

            // 判斷測試模式
            if ($logisticStage == 1) {

                // 取出 KEY IV MID (測試模式)
                $accountInfo = $this->getStageAccount($logisticCvsType);
                $this->_logger->debug('LogisticService accountInfo:'. print_r($accountInfo,true));

            } else {

                // 取出 KEY IV MID (正式模式)
                $logisticMerchantId = $this->_mainService->getLogisticConfig('logistic_mid') ;
                $logisticHashKey    = $this->_mainService->getLogisticConfig('logistic_hashkey') ;
                $logisticHashIv     = $this->_mainService->getLogisticConfig('logistic_hashiv') ;

                $this->_logger->debug('LogisticService logisticMerchantId:'. print_r($logisticMerchantId,true));
                $this->_logger->debug('LogisticService logisticHashKey:'. print_r($logisticHashKey,true));
                $this->_logger->debug('LogisticService logisticHashIv:'. print_r($logisticHashIv,true));

                $accountInfo = [
                    'MerchantId' => $logisticMerchantId,
                    'HashKey'    => $logisticHashKey,
                    'HashIv'     => $logisticHashIv,
                ] ;
            }

            // 取出訂單前綴
            $logisticOrderPreFix = $this->_mainService->getLogisticConfig('logistic_order_prefix') ;
            $this->_logger->debug('LogisticService logisticOrderPreFix:'. print_r($logisticOrderPreFix,true));

            // 組合廠商訂單編號
            $merchantTradeNo = $this->_orderService->getMerchantTradeNo($orderId, $logisticOrderPreFix);
            $this->_logger->debug('LogisticService merchantTradeNo:'. print_r($merchantTradeNo,true));

            // 取出寄件人姓名
            $logisticSenderName = $this->_mainService->getLogisticConfig('logistic_sender_name') ;
            $this->_logger->debug('LogisticService logisticSenderName:'. print_r($logisticSenderName,true));

            // 取出寄件人電話
            $logisticSenderCellphone = $this->_mainService->getLogisticConfig('logistic_sender_cellphone') ;
            $this->_logger->debug('LogisticService logisticSenderCellphone:'. print_r($logisticSenderCellphone,true));

            // 貨態回傳網址
            $serverReplyURL = $this->_urlInterface->getUrl('ecpaygeneral/Process/LogisticStatusResponse');
            $this->_logger->debug('LogisticService serverReplyURL:'. print_r($serverReplyURL,true));

            // 綠界訂單顯示商品名稱判斷

            $logisticDispItemName = $this->_mainService->getLogisticConfig('enabled_logistic_disp_item_name') ;
            $this->_logger->debug('LogisticService logisticDispItemName:'. print_r($logisticDispItemName,true));

            $itemNameDefault = __('A Package Of Online Goods');

            if ( $logisticDispItemName == 1 ) {

                // 取出訂單品項
                $salesOrderItem = $this->_orderService->getSalesOrderItemByOrderId($orderId);
                $this->_logger->debug('LogisticService salesOrderItem:'. print_r($salesOrderItem,true));

                // 轉換商品名稱格式
                $itemName = $this->getItemName($salesOrderItem);
                $this->_logger->debug('LogisticService itemName:'. print_r($itemName,true));

                // 判斷是否超過長度，如果超過長度改為預設文字
                if (strlen($itemName) > 50 ) {

                    $itemName = $itemNameDefault;

                    // 寫入備註
                    $comment = '商品名稱超過綠界物流可允許長度強制改為:'.$itemName ;
                    $status = false ;
                    $isVisibleOnFront = false ;

                    $this->_orderService->setOrderCommentForBack($orderId, $comment, $status, $isVisibleOnFront) ;
                }

                // 判斷特殊字元
                if (preg_match('/[\^\'\[\]`!@#%\\\&*+\"<>|_]/', $itemName)) {

                    $itemName = $itemNameDefault;

                    // 寫入備註
                    $comment = '商品名稱存在綠界物流不允許的特殊字元強制改為:'.$itemName ;
                    $status = false ;
                    $isVisibleOnFront = false ;

                    $this->_orderService->setOrderCommentForBack($orderId, $comment, $status, $isVisibleOnFront) ;
                }

            } else {
                $itemName = $itemNameDefault;
            }

            $this->_logger->debug('LogisticService itemName:'. print_r($itemName,true));

            // 判斷宅配種類
            if ($this->isEcpayCvsLogistics($shippingMethod)) {
                // 超商

                // 取出物流子類型
                $logisticsSubType = $this->getCvsLogisticsSubType($logisticCvsType, $shippingMethod);
                $this->_logger->debug('LogisticService logisticsSubType:'. print_r($logisticsSubType,true));

                // 取出超商代碼
                $CVSStoreID = $this->_orderService->getEcpayLogisticCvsStoreId($orderId);
                $this->_logger->debug('LogisticService CVSStoreID:'.$CVSStoreID);

                $CVSStoreName = $this->_orderService->getEcpayLogisticCVSStoreName($orderId);
                $this->_logger->debug('LogisticService CVSStoreName:'.$CVSStoreName);

                $inputLogisticOrder = [
                    'MerchantID'            => $accountInfo['MerchantId'],
                    'MerchantTradeNo'       => $merchantTradeNo,
                    'MerchantTradeDate'     => date('Y/m/d H:i:s'),
                    'LogisticsType'         => 'CVS',
                    'LogisticsSubType'      => $logisticsSubType,
                    'GoodsAmount'           => $goodsAmount,
                    'GoodsName'             => $itemName,
                    'SenderName'            => $logisticSenderName,
                    'SenderCellPhone'       => $logisticSenderCellphone,
                    'ReceiverName'          => $receiverName,
                    'ReceiverCellPhone'     => $receiverCellPhone,
                    'ReceiverStoreID'       => $CVSStoreID,
                    'IsCollection'          => $isCollection,
                    'ServerReplyURL'        => $serverReplyURL,
                ];

                $dataResponse['cvs_store_id'] = $CVSStoreID ;
                $dataResponse['cvs_store_name'] = $CVSStoreName ;

            } elseif ($this->isEcpayHomeLogistics($shippingMethod)) {
                // 宅配

                // 取出物流子類型
                $logisticsSubType = $this->getHomeLogisticsSubType($shippingMethod);
                $this->_logger->debug('LogisticService logisticsSubType:'. print_r($logisticsSubType,true));

                // 取出寄件人郵遞區號
                $logisticSenderZipcode = $this->_mainService->getLogisticConfig('logistic_sender_zipcode') ;
                $this->_logger->debug('LogisticService logisticSenderZipcode:'. print_r($logisticSenderZipcode,true));

                // 取出寄件人地址
                $logisticSenderAddress = $this->_mainService->getLogisticConfig('logistic_sender_address') ;
                $this->_logger->debug('LogisticService logisticSenderAddress:'. print_r($logisticSenderAddress,true));

                // 收件人郵遞區號
                $receiverZipCode = $this->_orderService->getShippingPostcode($orderId);
                $this->_logger->debug('LogisticService receiverZipCode:'. print_r($receiverZipCode,true));

                // 收件人地址
                $receiverStreet = $this->_orderService->getShippingStreet($orderId);
                $this->_logger->debug('LogisticService receiverStreet:'. print_r($receiverStreet,true));

                // 地址郵遞區號組合
                $getPostalName = $this->getPostalName($receiverZipCode);
                $receiverAddress = $getPostalName . $receiverStreet ;

                // 重量單位
                $weightUnit = $this->_directoryHelper->getWeightUnit();

                // 重量計算
                $goodsWeight = $this->_orderService->getWeight($orderId);
                $this->_logger->debug('LogisticService collectRates goodsWeight:'. print_r($goodsWeight,true) . ' ' . $weightUnit);

                // 單位轉換
                if ($weightUnit == 'lbs') {

                    $goodsWeight = $goodsWeight * 0.45359;
                    $this->_logger->debug('LogisticService collectRates goodsWeight:'. print_r($goodsWeight,true) . 'KG');
                }

                $goodsWeight = round($goodsWeight, 3);

                $inputLogisticOrder = [
                    'MerchantID'            => $accountInfo['MerchantId'],
                    'MerchantTradeNo'       => $merchantTradeNo,
                    'MerchantTradeDate'     => date('Y/m/d H:i:s'),
                    'LogisticsType'         => 'HOME',
                    'LogisticsSubType'      => $logisticsSubType,
                    'GoodsAmount'           => $goodsAmount,
                    'GoodsName'             => $itemName,
                    'GoodsWeight'           => $goodsWeight,
                    'SenderName'            => $logisticSenderName,
                    'SenderCellPhone'       => $logisticSenderCellphone,
                    'SenderZipCode'         => $logisticSenderZipcode,
                    'SenderAddress'         => $logisticSenderAddress,
                    'ReceiverName'          => $receiverName,
                    'ReceiverCellPhone'     => $receiverCellPhone,
                    'ReceiverZipCode'       => $receiverZipCode,
                    'ReceiverAddress'       => $receiverAddress,
                    'Temperature'           => '0001',
                    'Distance'              => '00',
                    'Specification'         => '0001',
                    'ScheduledPickupTime'   => '4',
                    'ScheduledDeliveryTime' => '4',
                    'ServerReplyURL'        => $serverReplyURL,
                ];
            }

            $factory = new Factory([
                'hashKey'       => $accountInfo['HashKey'],
                'hashIv'        => $accountInfo['HashIv'],
                'hashMethod'    => 'md5',
            ]);

            $postService = $factory->create('PostWithCmvEncodedStrResponseService');
            $response = $postService->post($inputLogisticOrder, $apiUrl);
            $this->_logger->debug('LogisticService inputLogisticOrder:'. print_r($inputLogisticOrder,true));
            $this->_logger->debug('LogisticService response:'. print_r($response,true));

            if (
                isset($response['RtnCode']) &&
                ( $response['RtnCode'] == 300 || $response['RtnCode'] == 2001 )
            ) {

                // 回傳結果寫入備註
                $comment = '建立物流訂單(成功)，交易單號：' . $response['MerchantTradeNo'] . '，狀態：' . $response['RtnMsg'] . '('. $response['RtnCode'] . ')，綠界科技的物流交易編號：' . $response['1|AllPayLogisticsID'];
                $status = false ;
                $isVisibleOnFront = false ;

                $this->_orderService->setOrderCommentForBack($orderId, $comment, $status, $isVisibleOnFront) ;

                // 寫入綠界物流資料表
                $ecpayLogisticModel = $this->_ecpayLogisticFactory->create();
                $ecpayLogisticModel->addData([
                    'order_id'              => $orderId,
                    'merchant_trade_no'     => $response['MerchantTradeNo'],
                    'rtn_code'              => $response['RtnCode'],
                    'rtn_msg'               => $response['RtnMsg'],
                    'all_pay_logistics_id'  => $response['1|AllPayLogisticsID'],
                    'logistics_type'        => $response['LogisticsType'],
                    'logistics_sub_type'    => $response['LogisticsSubType'],
                    'booking_note'          => $response['BookingNote'],
                    'cvs_payment_no'        => $response['CVSPaymentNo'],
                    'cvs_validation_no'     => $response['CVSValidationNo'],
                ]);

                $saveData = $ecpayLogisticModel->save();

                // 異動旗標
                $this->_orderService->setOrderData($orderId, 'ecpay_shipping_tag', 1) ;

                // 執行既有程序
                $this->_orderService->setOrderShip($orderId) ;

                // 回傳前端資訊
                $dataResponse['order_id']             = $orderId;
                $dataResponse['merchant_trade_no']    = $response['MerchantTradeNo'];
                $dataResponse['rtn_code']             = $response['RtnCode'];
                $dataResponse['rtn_msg']              = $response['RtnMsg'];
                $dataResponse['all_pay_logistics_id'] = $response['1|AllPayLogisticsID'];
                $dataResponse['logistics_type']       = $response['LogisticsType'];
                $dataResponse['logistics_sub_type']   = $response['LogisticsSubType'];
                $dataResponse['booking_note']         = $response['BookingNote'];
                $dataResponse['cvs_payment_no']       = $response['CVSPaymentNo'];
                $dataResponse['cvs_validation_no']    = $response['CVSValidationNo'];

                return [
                    'code' => '0999',
                    'data' => json_encode($dataResponse)
                ];
            } else {

                // 回傳結果寫入備註
                $comment = '建立物流訂單(失敗)，回傳資訊：' . print_r($response,true);
                $status = false ;
                $isVisibleOnFront = false ;
                $this->_orderService->setOrderCommentForBack($orderId, $comment, $status, $isVisibleOnFront) ;

                return ['code' => '2005'];
            }
        } else {
            // 非綠界物流
            return ['code' => '2004'];
        }
    }

    /**
     * 計算中華郵政宅配重量運費
     * @param  int|float $weight  單位僅限KG
     * @param  int $shippingFee1 0-5   KG
     * @param  int $shippingFee2 5-10  KG
     * @param  int $shippingFee3 10-15 KG
     * @param  int $shippingFee4 15-20 KG
     * @return int
     */
    public function calculateHomePostShippingWeight(int|float $weight, int $shippingFee1, int $shippingFee2, int $shippingFee3, int $shippingFee4)
    {
        if ($weight <= 5) {
            return $shippingFee1;
        } else if ($weight > 5 && $weight <= 10) {
            return $shippingFee2;
        } else if ($weight > 10 && $weight <= 15) {
            return $shippingFee3;
        } else if ($weight > 15 && $weight <= 20) {
            return $shippingFee4;
        } else if ($weight > 20) {
            return $shippingFee4;
        }
    }
}