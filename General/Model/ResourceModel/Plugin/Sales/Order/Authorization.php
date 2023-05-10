<?php

namespace Ecpay\General\Model\ResourceModel\Plugin\Sales\Order;

use Psr\Log\LoggerInterface;
use Magento\Backend\Model\Auth\Session;

use Magento\Authorization\Model\UserContextInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\ResourceModel\Order as ResourceOrder;

class Authorization
{
    protected $_loggerInterface;
    protected $_backendSession;
    protected $_userContext;

    public function __construct(
        LoggerInterface $loggerInterface,
        Session $backendSession,
        UserContextInterface $userContext
    )
    {
        $this->_loggerInterface = $loggerInterface;
        $this->_backendSession = $backendSession;
        $this->_userContext = $userContext;
    }

    /**
     * @param ResourceOrder $subject
     * @param ResourceOrder $result
     * @param \Magento\Framework\Model\AbstractModel $order
     * @return ResourceOrder
     * @throws NoSuchEntityException
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterLoad(
        ResourceOrder $subject,
        ResourceOrder $result,
        \Magento\Framework\Model\AbstractModel $order
    ) {
        if ($order instanceof Order) {
            if (!$this->isAllowed($order)) {
                throw NoSuchEntityException::singleField('orderId', $order->getId());
            }
        }
        return $result;
    }

    protected function isAllowed(Order $order)
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $state =  $objectManager->get('Magento\Framework\App\State');
        $areaCode = $state->getAreaCode();
        $this->_loggerInterface->debug('Authorization State AreaCode:'.  $areaCode);

        if ($areaCode == 'adminhtml' || $areaCode == 'webapi_rest') {
            return true;
        }
        else {
            return $this->_userContext->getUserType() == UserContextInterface::USER_TYPE_CUSTOMER
                ? $order->getCustomerId() == $this->_userContext->getUserId()
                : true;
        }
    }
}