<?php

namespace Nordcomputer\Showoutofstockprice\Plugin;

use Magento\Catalog\Model\Product\Attribute\Source\Status;

class ShowOutOfStockProductsPlugin
{
    /**
     * Force all enabled child products (including out-of-stock) into the allowed list.
     */
    public function beforeGetAllowProducts(
        \Magento\ConfigurableProduct\Block\Product\View\Type\Configurable $subject
    ) {
        if (!$subject->hasAllowProducts()) {
            $allProducts = $subject->getProduct()
                ->getTypeInstance()
                ->getUsedProducts($subject->getProduct(), null);

            $products = [];
            foreach ($allProducts as $product) {
                if ((int)$product->getStatus() !== Status::STATUS_DISABLED) {
                    $products[] = $product;
                }
            }
            $subject->setAllowProducts($products);
        }

        return [];
    }
}
