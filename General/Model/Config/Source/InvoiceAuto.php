<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Ecpay\General\Model\Config\Source;

/**
 * @api
 * @since 100.0.2
 */
class InvoiceAuto implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [['value' => 1, 'label' => __('AUTO')], ['value' => 0, 'label' => __('MANUAL')]];
    }

    /**
     * Get options in "key-value" format
     *
     * @return array
     */
    public function toArray()
    {
        return [0 => __('MANUAL'), 1 => __('AUTO')];
    }
}
