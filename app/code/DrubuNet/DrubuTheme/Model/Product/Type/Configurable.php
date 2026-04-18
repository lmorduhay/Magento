<?php

namespace DrubuNet\DrubuTheme\Model\Product\Type;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\Data\ProductInterfaceFactory;
use Magento\Catalog\Api\ProductAttributeRepositoryInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Config;
use Magento\ConfigurableProduct\Model\Product\Type\Collection\SalableProcessor;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\File\UploaderFactory;

class Configurable extends \Magento\ConfigurableProduct\Model\Product\Type\Configurable
{
    const TYPE_CODE = 'configurable';

    protected $usedProductAttributeIds = '_cache_instance_used_product_attribute_ids';
    protected $usedProductAttributes = '_cache_instance_used_product_attributes';
    protected $_usedAttributes = '_cache_instance_used_attributes';
    protected $_configurableAttributes = '_cache_instance_configurable_attributes';
    protected $_usedProductIds = '_cache_instance_product_ids';
    protected $_usedProducts = '_cache_instance_products';
    private $usedSalableProducts = '_cache_instance_salable_products';
    protected $_isComposite = true;
    protected $_canConfigure = true;
    protected $isSaleableBySku = [];

    protected $_scopeConfig;
    protected $_catalogProductTypeConfigurable;
    protected $_attributeCollectionFactory;
    protected $_productCollectionFactory;
    protected $configurableAttributeFactory;
    protected $_eavAttributeFactory;
    protected $typeConfigurableFactory;
    protected $extensionAttributesJoinProcessor;
    private $cache;
    private $customerSession;
    private $productFactory;
    private $salableProcessor;
    private $productAttributeRepository;
    private $searchCriteriaBuilder;
    private $catalogConfig;

    public function __construct(
        \Magento\Catalog\Model\Product\Option $catalogProductOption,
        \Magento\Eav\Model\Config $eavConfig,
        \Magento\Catalog\Model\Product\Type $catalogProductType,
        \Magento\Framework\Event\ManagerInterface $eventManager,
        \Magento\MediaStorage\Helper\File\Storage\Database $fileStorageDb,
        \Magento\Framework\Filesystem $filesystem,
        \Magento\Framework\Registry $coreRegistry,
        \Psr\Log\LoggerInterface $logger,
        ProductRepositoryInterface $productRepository,
        \Magento\ConfigurableProduct\Model\ResourceModel\Product\Type\ConfigurableFactory $typeConfigurableFactory,
        \Magento\Catalog\Model\ResourceModel\Eav\AttributeFactory $eavAttributeFactory,
        \Magento\ConfigurableProduct\Model\Product\Type\Configurable\AttributeFactory $configurableAttributeFactory,
        \Magento\ConfigurableProduct\Model\ResourceModel\Product\Type\Configurable\Product\CollectionFactory $productCollectionFactory,
        \Magento\ConfigurableProduct\Model\ResourceModel\Product\Type\Configurable\Attribute\CollectionFactory $attributeCollectionFactory,
        \Magento\ConfigurableProduct\Model\ResourceModel\Product\Type\Configurable $catalogProductTypeConfigurable,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\Api\ExtensionAttribute\JoinProcessorInterface $extensionAttributesJoinProcessor,
        \Magento\Framework\Cache\FrontendInterface $cache = null,
        \Magento\Customer\Model\Session $customerSession = null,
        \Magento\Framework\Serialize\Serializer\Json $serializer = null,
        ProductInterfaceFactory $productFactory = null,
        SalableProcessor $salableProcessor = null,
        ProductAttributeRepositoryInterface $productAttributeRepository = null,
        SearchCriteriaBuilder $searchCriteriaBuilder = null,
        UploaderFactory $uploaderFactory = null
    ) {
        $this->typeConfigurableFactory = $typeConfigurableFactory;
        $this->_eavAttributeFactory = $eavAttributeFactory;
        $this->configurableAttributeFactory = $configurableAttributeFactory;
        $this->_productCollectionFactory = $productCollectionFactory;
        $this->_attributeCollectionFactory = $attributeCollectionFactory;
        $this->_catalogProductTypeConfigurable = $catalogProductTypeConfigurable;
        $this->_scopeConfig = $scopeConfig;
        $this->extensionAttributesJoinProcessor = $extensionAttributesJoinProcessor;
        $this->cache = $cache;
        $this->customerSession = $customerSession;
        $this->productFactory = $productFactory ?: ObjectManager::getInstance()
            ->get(ProductInterfaceFactory::class);
        $this->salableProcessor = $salableProcessor ?: ObjectManager::getInstance()->get(SalableProcessor::class);
        $this->productAttributeRepository = $productAttributeRepository ?:
            ObjectManager::getInstance()->get(ProductAttributeRepositoryInterface::class);
        $this->searchCriteriaBuilder = $searchCriteriaBuilder ?:
            ObjectManager::getInstance()->get(SearchCriteriaBuilder::class);
        parent::__construct(
            $catalogProductOption, $eavConfig, $catalogProductType, $eventManager,
            $fileStorageDb, $filesystem, $coreRegistry, $logger, $productRepository,
            $typeConfigurableFactory, $eavAttributeFactory, $configurableAttributeFactory,
            $productCollectionFactory, $attributeCollectionFactory, $catalogProductTypeConfigurable,
            $scopeConfig, $extensionAttributesJoinProcessor, $cache, $customerSession,
            $serializer, $productFactory, $salableProcessor, $productAttributeRepository,
            $searchCriteriaBuilder, $uploaderFactory
        );
    }

    /**
     * Get ALL child products including out-of-stock.
     * Uses getChildrenIds() from the resource model to avoid recursion,
     * then builds a plain catalog collection bypassing stock filters.
     */
    public function getUsedProducts($product, $requiredAttributeIds = null)
    {
        if (!$product->hasData($this->_usedProducts)) {
            $childrenIdsMap = $this->_catalogProductTypeConfigurable->getChildrenIds($product->getId());
            $childIds = [];
            foreach ($childrenIdsMap as $group) {
                foreach ($group as $id) {
                    $childIds[] = $id;
                }
            }

            if (empty($childIds)) {
                $product->setData($this->_usedProducts, []);
                return [];
            }

            $collectionFactory = ObjectManager::getInstance()
                ->get(\Magento\Catalog\Model\ResourceModel\Product\CollectionFactory::class);
            $collection = $collectionFactory->create();
            $collection->addIdFilter($childIds);
            $collection->setFlag('has_stock_status_filter', true);

            $attributesForSelect = $this->getAttributesForCollection($product);
            if ($requiredAttributeIds) {
                $this->searchCriteriaBuilder->addFilter('attribute_id', $requiredAttributeIds, 'in');
                $requiredAttributes = $this->productAttributeRepository
                    ->getList($this->searchCriteriaBuilder->create())->getItems();
                foreach ($requiredAttributes as $attr) {
                    $attributesForSelect[] = $attr->getAttributeCode();
                }
                $attributesForSelect = array_unique($attributesForSelect);
            }

            $collection
                ->addAttributeToSelect($attributesForSelect)
                ->addFilterByRequiredOptions()
                ->setStoreId($product->getStoreId());

            $collection->addMediaGalleryData();
            $collection->addTierPriceData();

            $usedProducts = array_values($collection->getItems());
            $product->setData($this->_usedProducts, $usedProducts);
        }

        return $product->getData($this->_usedProducts);
    }

    private function getAttributesForCollection(\Magento\Catalog\Model\Product $product)
    {
        $productAttributes = $this->getCatalogConfig()->getProductAttributes();
        $requiredAttributes = [
            'name', 'price', 'weight', 'image', 'thumbnail',
            'status', 'visibility', 'media_gallery'
        ];
        $usedAttributes = array_map(
            function($attr) { return $attr->getAttributeCode(); },
            $this->getUsedProductAttributes($product)
        );
        return array_unique(array_merge($productAttributes, $requiredAttributes, $usedAttributes));
    }

    private function getCatalogConfig()
    {
        if (!$this->catalogConfig) {
            $this->catalogConfig = ObjectManager::getInstance()->get(Config::class);
        }
        return $this->catalogConfig;
    }
}
