<?php 
namespace Ecpay\General\Model\ResourceModel\EcpayLogistic;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    public function _construct()
    {
        $this->_init("Ecpay\General\Model\EcpayLogistic","Ecpay\General\Model\ResourceModel\EcpayLogistic");
    }
}