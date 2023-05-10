<?php 

namespace Ecpay\General\Model\ResourceModel;

class EcpayPaymentInfo extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{

    public function _construct()
    {
    $this->_init("ecpay_payment_info", "entity_id");
    }
}