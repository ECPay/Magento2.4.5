<?php 

namespace Ecpay\General\Model\ResourceModel;

class EcpayLogistic extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{

    public function _construct()
    {
    $this->_init("ecpay_logistic", "entity_id");
    }
}