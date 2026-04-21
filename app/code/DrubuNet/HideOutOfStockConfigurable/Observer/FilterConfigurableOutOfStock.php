<?php
declare(strict_types=1);

namespace DrubuNet\HideOutOfStockConfigurable\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

/**
 * Intentionally empty: configurable products must stay visible in category
 * listings even when all children are out of stock, so the swatch renderer
 * can display every size as strikethrough.
 */
class FilterConfigurableOutOfStock implements ObserverInterface
{
    public function execute(Observer $observer)
    {
        // no-op — the frontend swatch renderer handles disabled/strikethrough state
    }
}
