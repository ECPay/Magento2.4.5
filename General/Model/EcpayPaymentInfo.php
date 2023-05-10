<?php 
namespace Ecpay\General\Model;

class EcpayPaymentInfo extends \Magento\Framework\Model\AbstractModel
{
    public function _construct()
    {
        $this->_init("Ecpay\General\Model\ResourceModel\EcpayPaymentInfo");
    }
}