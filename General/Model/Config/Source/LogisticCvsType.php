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
class LogisticCvsType implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [['value' => 'B2C', 'label' => __('B2C')], ['value' => 'C2C', 'label' => __('C2C')]];
    }

    /**
     * Get options in "key-value" format
     *
     * @return array
     */
    public function toArray()
    {
        return ['C2C' => __('C2C'), 'B2C' => __('B2C')];
    }
}
