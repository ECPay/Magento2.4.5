<?php
namespace Ecpay\General\Setup;

use Magento\Framework\Setup\UpgradeSchemaInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\ModuleContextInterface;

class UpgradeSchema implements UpgradeSchemaInterface
{
	public function upgrade(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $installer = $setup;
		$installer->startSetup();

        if (version_compare($context->getVersion(), '2.4.2305290', '<')) {
            $installer->getConnection()->addColumn(
                $installer->getTable('sales_order'),
                'ecpay_payment_merchant_trade_no',
                [
                    'type'     => 'text',
                    'nullable' => false,
                    'default'  => '',
                    'length'   => 255,
                    'comment'  => 'Payment Merchant Trade No',
                ]
            );
        }

		$installer->endSetup();
	}
}