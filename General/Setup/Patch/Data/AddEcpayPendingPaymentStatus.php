<?php

namespace Ecpay\General\Setup\Patch\Data;

use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;

class AddEcpayPendingPaymentStatus implements DataPatchInterface
{
	/**
     * @var ModuleDataSetupInterface
     */
    private $moduleDataSetup;

    /**
     * AddEcpayPendingPaymentStatus constructor.
     * @param ModuleDataSetupInterface $moduleDataSetup
     */
    public function __construct(ModuleDataSetupInterface $moduleDataSetup)
    {
        $this->moduleDataSetup = $moduleDataSetup;
    }

    /**
     * {@inheritdoc}
     */
    public function apply()
    {
        $this->moduleDataSetup->startSetup();

        // 新增 status
        $salesOrderStatusData[] = ['status' => 'ecpay_pending_payment', 'label' => 'ECPay Pending Payment'];
        $this->moduleDataSetup->getConnection()->insertArray($this->moduleDataSetup->getTable('sales_order_status'), ['status', 'label'], $salesOrderStatusData);

        // 新增 status 關聯設定
        $salesOrderStatusStateData[] = ['status' => 'ecpay_pending_payment', 'state' => 'pending_payment', 'is_default' => 0, 'visible_on_front' => 1];
        $this->moduleDataSetup->getConnection()->insertArray($this->moduleDataSetup->getTable('sales_order_status_state'), ['status', 'state', 'is_default', 'visible_on_front'], $salesOrderStatusStateData);

        $this->moduleDataSetup->endSetup();
    }

    /**
     * {@inheritdoc}
     */
    public static function getDependencies()
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function getAliases()
    {
        return [];
    }

}