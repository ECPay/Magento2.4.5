<?php
namespace Ecpay\General\Controller\Page;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\App\RequestInterface;
use Psr\Log\LoggerInterface ;

class ThankYou extends Action
{
    protected $_pageFactory;
    protected $_requestInterface;
    protected $_loggerInterface;

    public function __construct(
        Context $context,
        PageFactory $pageFactory,
        RequestInterface $requestInterface,
        LoggerInterface $loggerInterface
    ) {
        $this->_pageFactory = $pageFactory;
        $this->_requestInterface = $requestInterface;
        $this->_loggerInterface = $loggerInterface;

        return parent::__construct($context);
    }

    public function execute()
    {
        return $this->_pageFactory->create();

    }
}