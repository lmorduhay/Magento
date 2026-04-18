<?php

namespace Nordcomputer\Showoutofstockprice\ConfigurableProduct\Helper\Data;

use Magento\CatalogInventory\Api\StockRegistryInterface;
use Magento\Catalog\Helper\Image as ImageHelper;
use Magento\Catalog\Model\Product\Image\UrlBuilder;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ObjectManager;

class Data extends \Magento\ConfigurableProduct\Helper\Data
{
    /**
     * @var StockRegistryInterface
     */
    private $stockRegistryInstance;

    public function __construct(
        ImageHelper $imageHelper,
        UrlBuilder $urlBuilder = null,
        ?ScopeConfigInterface $scopeConfig = null,
        ?StockRegistryInterface $stockRegistry = null
    ) {
        parent::__construct($imageHelper, $urlBuilder, $scopeConfig);
        $this->stockRegistryInstance = $stockRegistry
            ?? ObjectManager::getInstance()->get(StockRegistryInterface::class);
    }

    /**
     * Get options including out-of-stock products, with salable info for strikethrough rendering.
     */
    public function getOptions($currentProduct, $allowedProducts)
    {
        $options = [];
        $allowAttributes = $this->getAllowAttributes($currentProduct);

        foreach ($allowedProducts as $product) {
            $productId = $product->getId();

            $stockItem = $this->stockRegistryInstance->getStockItem(
                $product->getId(),
                $product->getStore()->getWebsiteId()
            );
            $isSalable = $stockItem->getIsInStock() && $stockItem->getQty() > 0;

            foreach ($allowAttributes as $attribute) {
                $productAttribute = $attribute->getProductAttribute();
                $productAttributeId = $productAttribute->getId();
                $attributeValue = $product->getData($productAttribute->getAttributeCode());

                // Always include every child in the main options so swatches render
                $options[$productAttributeId][$attributeValue][] = $productId;
                $options['index'][$productId][$productAttributeId] = $attributeValue;

                // Only add salable products to the salable array (JS uses this for strikethrough)
                if ($isSalable) {
                    $options['salable'][$productAttributeId][$attributeValue][] = $productId;
                }
            }
        }

        // Always enable out-of-stock display so the JS disableSwatchForOutOfStockProducts() runs
        $options['canDisplayShowOutOfStockStatus'] = true;

        return $options;
    }
}
