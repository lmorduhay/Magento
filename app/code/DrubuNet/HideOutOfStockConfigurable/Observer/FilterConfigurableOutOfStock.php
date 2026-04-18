<?php

namespace DrubuNet\HideOutOfStockConfigurable\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

/**
 * No-op observer: configurable products must stay visible in category listings
 * even when all children are out of stock, so that sizes render as strikethrough.
 */
class FilterConfigurableOutOfStock implements ObserverInterface
{
    public function execute(Observer $observer)
    {
        // Intentionally empty — do not remove any configurable from the collection.
        // The frontend swatch renderer handles the disabled/strikethrough state.
    }
}
