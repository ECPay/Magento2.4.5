<?php
namespace Ecpay\Invoice\Setup;
use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;

class InstallSchema implements InstallSchemaInterface
{
    public function install(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $installer = $setup;
        $installer->startSetup();

        $installer->getConnection()->addColumn(
            $installer->getTable('quote'),
            'ecpay_invoice_carruer_type',
            [
                'type' => 'text',
                'nullable' => false,
                'length'    => 50,
                'comment' => 'Carruer Type',
            ]
        );

        $installer->getConnection()->addColumn(
            $installer->getTable('quote'),
            'ecpay_invoice_type',
            [
                'type' => 'text',
                'nullable' => false,
                'length'    => 50,
                'comment' => 'Invoice Type',
            ]
        );

        $installer->getConnection()->addColumn(
            $installer->getTable('quote'),
            'ecpay_invoice_carruer_num',
            [
                'type' => 'text',
                'nullable' => true,
                'length'    => 50,
                'comment' => 'Carruer Type',
            ]
        );

        $installer->getConnection()->addColumn(
            $installer->getTable('quote'),
            'ecpay_invoice_love_code',
            [
                'type' => 'text',
                'nullable' => true,
                'length'    => 50,
                'comment' => 'Love Code',
            ]
        );

        $installer->getConnection()->addColumn(
            $installer->getTable('quote'),
            'ecpay_invoice_customer_identifier',
            [
                'type' => 'text',
                'nullable' => true,
                'length'    => 10,
                'comment' => 'Customer Identifier',
            ]
        );

        $installer->getConnection()->addColumn(
            $installer->getTable('quote'),
            'ecpay_invoice_customer_company',
            [
                'type' => 'text',
                'nullable' => true,
                'length'    => 60,
                'comment' => 'Customer Company',
            ]
        );

        $installer->getConnection()->addColumn(
            $installer->getTable('sales_order'),
            'ecpay_invoice_carruer_type',
            [
                'type' => 'text',
                'nullable' => false,
                'length'    => 50,
                'comment' => 'Carruer Type',
            ]
        );

        $installer->getConnection()->addColumn(
            $installer->getTable('sales_order'),
            'ecpay_invoice_type',
            [
                'type' => 'text',
                'nullable' => false,
                'length'    => 50,
                'comment' => 'Invoice Type',
            ]
        );

        $installer->getConnection()->addColumn(
            $installer->getTable('sales_order'),
            'ecpay_invoice_carruer_num',
            [
                'type' => 'text',
                'nullable' => true,
                'length'    => 50,
                'comment' => 'Carruer Type',
            ]
        );

        $installer->getConnection()->addColumn(
            $installer->getTable('sales_order'),
            'ecpay_invoice_love_code',
            [
                'type' => 'text',
                'nullable' => true,
                'length'    => 50,
                'comment' => 'Love Code',
            ]
        );

        $installer->getConnection()->addColumn(
            $installer->getTable('sales_order'),
            'ecpay_invoice_customer_identifier',
            [
                'type' => 'text',
                'nullable' => true,
                'length'    => 10,
                'comment' => 'Customer Identifier',
            ]
        );

        $installer->getConnection()->addColumn(
            $installer->getTable('sales_order'),
            'ecpay_invoice_customer_company',
            [
                'type' => 'text',
                'nullable' => true,
                'length'    => 60,
                'comment' => 'Customer Comapny',
            ]
        );

        $installer->getConnection()->addColumn(
            $installer->getTable('sales_order'),
            'ecpay_invoice_number',
            [
                'type' => 'text',
                'nullable' => true,
                'length'    => 10,
                'comment' => 'Invoice Number',
            ]
        );

        $installer->getConnection()->addColumn(
            $installer->getTable('sales_order'),
            'ecpay_invoice_date',
            [
                'type' => 'text',
                'nullable' => true,
                'length'    => 20,
                'comment' => 'Invoice Date',
            ]
        );

        $installer->getConnection()->addColumn(
            $installer->getTable('sales_order'),
            'ecpay_invoice_random_number',
            [
                'type' => 'text',
                'nullable' => true,
                'length'    => 10,
                'comment' => 'Invoice Random Number',
            ]
        );

        $installer->getConnection()->addColumn(
            $installer->getTable('sales_order'),
            'ecpay_invoice_tag',
            [
                'type' => 'integer',
                'nullable' => false,
                'default' => 0,
                'comment' => 'Invoice Tag',
            ]
        );

        $installer->getConnection()->addColumn(
            $installer->getTable('sales_order'),
            'ecpay_invoice_issue_type',
            [
                'type' => 'integer',
                'nullable' => false,
                'default' => 0,
                'comment' => 'Invoice Issue Type',
            ]
        );

        $installer->getConnection()->addColumn(
            $installer->getTable('sales_order'),
            'ecpay_invoice_od_sob',
            [
                'type' => 'text',
                'nullable' => false,
                'length'    => 50,
                'comment' => 'Invoice Od Sob',
            ]
        );

        $installer->getConnection()->addColumn(
            $installer->getTable('sales_order'),
            'ecpay_invoice_auto_tag',
            [
                'type' => 'integer',
                'nullable' => false,
                'default' => 0,
                'comment' => 'Invoice Auto Tag',
            ]
        );

        $installer->getConnection()->addColumn(
            $installer->getTable('sales_order_grid'),
            'ecpay_invoice_carruer_type',
            [
                'type' => 'text',
                'nullable' => false,
                'length'    => 50,
                'comment' => 'Carruer Type',
            ]
        );

        $installer->getConnection()->addColumn(
            $installer->getTable('sales_order_grid'),
            'ecpay_invoice_type',
            [
                'type' => 'text',
                'nullable' => false,
                'length'    => 50,
                'comment' => 'Invoice Type',
            ]
        );

        $installer->getConnection()->addColumn(
            $installer->getTable('sales_order_grid'),
            'ecpay_invoice_carruer_num',
            [
                'type' => 'text',
                'nullable' => true,
                'length'    => 50,
                'comment' => 'Carruer Type',
            ]
        );

        $installer->getConnection()->addColumn(
            $installer->getTable('sales_order_grid'),
            'ecpay_invoice_love_code',
            [
                'type' => 'text',
                'nullable' => true,
                'length'    => 50,
                'comment' => 'Love Code',
            ]
        );

        $installer->getConnection()->addColumn(
            $installer->getTable('sales_order_grid'),
            'ecpay_invoice_customer_identifier',
            [
                'type' => 'text',
                'nullable' => true,
                'length'    => 10,
                'comment' => 'Customer Identifier',
            ]
        );

        $installer->getConnection()->addColumn(
            $installer->getTable('sales_order_grid'),
            'ecpay_invoice_customer_company',
            [
                'type' => 'text',
                'nullable' => true,
                'length'    => 60,
                'comment' => 'Customer Company',
            ]
        );

        $setup->endSetup();
    }
}