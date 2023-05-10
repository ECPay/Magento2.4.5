<?php 
namespace Ecpay\General\Model;

class EcpayLogistic extends \Magento\Framework\Model\AbstractModel
{
    public function _construct()
    {
        $this->_init("Ecpay\General\Model\ResourceModel\EcpayLogistic");
    }
}