<?php

namespace Nordcomputer\Showoutofstockprice\Plugin;

use Magento\ConfigurableProduct\Model\ResourceModel\Attribute\OptionSelectBuilderInterface;
use Magento\Framework\DB\Select;

/**
 * Prevent stock filtering on configurable attribute options so that
 * out-of-stock swatches are still rendered (shown as disabled / strikethrough).
 */
class InStockOptionSelectorPlugin
{
    /**
     * Skip the stock status join — return the select as-is so all option values appear.
     */
    public function afterGetSelect(
        OptionSelectBuilderInterface $subject,
        Select $select
    ) {
        return $select;
    }
}
