<?php 
namespace Ecpay\General\Model\ResourceModel\EcpayPaymentInfo;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    public function _construct()
    {
        $this->_init("Ecpay\General\Model\EcpayPaymentInfo","Ecpay\General\Model\ResourceModel\EcpayPaymentInfo");
    }
}