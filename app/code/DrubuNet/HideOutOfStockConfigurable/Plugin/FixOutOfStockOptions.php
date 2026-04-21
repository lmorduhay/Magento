<?php
declare(strict_types=1);

namespace DrubuNet\HideOutOfStockConfigurable\Plugin;

use Magento\CatalogInventory\Api\StockRegistryInterface;
use Magento\ConfigurableProduct\Model\ResourceModel\Product\Type\Configurable as ConfigurableResource;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Catalog\Model\Product\Attribute\Source\Status;

/**
 * Wraps the Nordcomputer getOptions() to:
 * 1. Include ALL children (including OOS) in the main options so swatches render
 * 2. Build the 'salable' sub-array so the JS marks OOS swatches as strikethrough
 * 3. Set 'canDisplayShowOutOfStockStatus' = true
 */
class FixOutOfStockOptions
{
    private StockRegistryInterface $stockRegistry;
    private ConfigurableResource $configurableResource;
    private CollectionFactory $collectionFactory;

    public function __construct(
        StockRegistryInterface $stockRegistry,
        ConfigurableResource $configurableResource,
        CollectionFactory $collectionFactory
    ) {
        $this->stockRegistry = $stockRegistry;
        $this->configurableResource = $configurableResource;
        $this->collectionFactory = $collectionFactory;
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function aroundGetOptions(
        \Nordcomputer\Showoutofstockprice\ConfigurableProduct\Helper\Data\Data $subject,
        callable $proceed,
        $currentProduct,
        $allowedProducts
    ) {
        // Build a map of product IDs already in allowedProducts
        $allowedById = [];
        foreach ($allowedProducts as $p) {
            $allowedById[$p->getId()] = $p;
        }

        // Find any missing children (OOS ones stripped by other code)
        $allChildIds = [];
        $childrenIdsMap = $this->configurableResource->getChildrenIds($currentProduct->getId());
        foreach ($childrenIdsMap as $group) {
            foreach ($group as $id) {
                $allChildIds[$id] = true;
            }
        }
        $missingIds = array_diff_key($allChildIds, $allowedById);

        if (!empty($missingIds)) {
            $collection = $this->collectionFactory->create();
            $collection->addIdFilter(array_keys($missingIds));
            $collection->setFlag('has_stock_status_filter', true);
            $collection->addAttributeToSelect('*');
            $collection->addFilterByRequiredOptions();
            $collection->setStoreId($currentProduct->getStoreId());

            foreach ($collection->getItems() as $child) {
                if ((int)$child->getStatus() === Status::STATUS_ENABLED) {
                    $allowedProducts[] = $child;
                }
            }
        }

        // Now build the options array with salable info
        $options = [];
        $allowAttributes = $subject->getAllowAttributes($currentProduct);

        foreach ($allowedProducts as $product) {
            $productId = $product->getId();

            $stockItem = $this->stockRegistry->getStockItem(
                (int)$productId,
                (int)$product->getStore()->getWebsiteId()
            );
            $isSalable = $stockItem->getIsInStock() && $stockItem->getQty() > 0;

            foreach ($allowAttributes as $attribute) {
                $productAttribute = $attribute->getProductAttribute();
                $productAttributeId = $productAttribute->getId();
                $attributeValue = $product->getData($productAttribute->getAttributeCode());

                $options[$productAttributeId][$attributeValue][] = $productId;
                $options['index'][$productId][$productAttributeId] = $attributeValue;

                if ($isSalable) {
                    $options['salable'][$productAttributeId][$attributeValue][] = $productId;
                }
            }
        }

        $options['canDisplayShowOutOfStockStatus'] = true;

        return $options;
    }
}
