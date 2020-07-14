<?php

namespace TddWizard\Fixtures\Catalog;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\Data\ProductWebsiteLinkInterface;
use Magento\Catalog\Api\Data\ProductWebsiteLinkInterfaceFactory;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Api\ProductWebsiteLinkRepositoryInterface;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Model\Product\Visibility;
use Magento\CatalogInventory\Api\Data\StockItemInterface;
use Magento\CatalogInventory\Api\StockItemRepositoryInterface;
use Magento\Framework\ObjectManagerInterface;
use Magento\Indexer\Model\IndexerFactory;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.UnusedPrivateField)
 */
class ProductBuilder
{
    /**
     * @var ProductInterface
     */
    protected $product;
    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;
    /**
     * @var mixed[][]
     */
    private $storeSpecificValues = [];
    /**
     * @var int[]
     */
    private $websiteIds = [];
    /**
     * @var int[]
     */
    private $categoryIds = [];
    /**
     * @var ProductWebsiteLinkRepositoryInterface
     */
    private $websiteLinkRepository;
    /**
     * @var StockItemRepositoryInterface
     */
    private $stockItemRepository;
    /**
     * @var ProductWebsiteLinkInterfaceFactory
     */
    private $websiteLinkFactory;
    /**
     * @var IndexerFactory
     */
    private $indexerFactory;

    public function __construct(
        ProductRepositoryInterface $productRepository,
        StockItemRepositoryInterface $stockItemRepository,
        ProductWebsiteLinkRepositoryInterface $websiteLinkRepository,
        ProductWebsiteLinkInterfaceFactory $websiteLinkFactory,
        IndexerFactory $indexerFactory,
        ProductInterface $product,
        array $websiteIds,
        array $storeSpecificValues
    ) {
        $this->productRepository = $productRepository;
        $this->websiteLinkRepository = $websiteLinkRepository;
        $this->stockItemRepository = $stockItemRepository;
        $this->websiteLinkFactory = $websiteLinkFactory;
        $this->indexerFactory = $indexerFactory;
        $this->product = $product;
        $this->websiteIds = $websiteIds;
        $this->storeSpecificValues = $storeSpecificValues;
    }

    public function __clone()
    {
        $this->product = clone $this->product;
    }

    public static function aSimpleProduct(ObjectManagerInterface $objectManager = null) : ProductBuilder
    {
        if ($objectManager === null) {
            $objectManager = \Magento\TestFramework\Helper\Bootstrap::getObjectManager();
        }
        /** @var ProductInterface $product */
        $product = $objectManager->create(ProductInterface::class);

        $product->setTypeId(\Magento\Catalog\Model\Product\Type::TYPE_SIMPLE)
            ->setAttributeSetId(4)
            ->setName('Simple Product')
            ->setPrice(10)
            ->setVisibility(Visibility::VISIBILITY_BOTH)
            ->setStatus(Status::STATUS_ENABLED);
        $product->addData([
            'tax_class_id' => 1,
            'description' => 'Description',
        ]);
        /** @var StockItemInterface $stockItem */
        $stockItem = $objectManager->create(StockItemInterface::class);
        $stockItem->setManageStock(true)
            ->setQty(100)
            ->setIsQtyDecimal(false)
            ->setIsInStock(true);
        $product->setExtensionAttributes(
            $product->getExtensionAttributes()->setStockItem($stockItem)
        );

        return new static(
            $objectManager->create(ProductRepositoryInterface::class),
            $objectManager->create(StockItemRepositoryInterface::class),
            $objectManager->create(ProductWebsiteLinkRepositoryInterface::class),
            $objectManager->create(ProductWebsiteLinkInterfaceFactory::class),
            $objectManager->create(IndexerFactory::class),
            $product,
            [1],
            []
        );
    }

    public function withData(array $data) : ProductBuilder
    {
        $builder = clone $this;

        $builder->product->addData($data);

        return $builder;
    }

    public function withSku(string $sku) : ProductBuilder
    {
        $builder = clone $this;
        $builder->product->setSku($sku);
        return $builder;
    }

    public function withName(string $name, $storeId = null) : ProductBuilder
    {
        $builder = clone $this;
        if ($storeId) {
            $builder->storeSpecificValues[$storeId][ProductInterface::NAME] = $name;
        } else {
            $builder->product->setName($name);
        }
        return $builder;
    }

    /**
     * @param int $status
     * @param int|null $storeId Pass store ID to set value for specific store.
     *                          Attention: Status is configured per website, will affect all stores of the same website
     * @return ProductBuilder
     */
    public function withStatus(int $status, $storeId = null) : ProductBuilder
    {
        $builder = clone $this;
        if ($storeId) {
            $builder->storeSpecificValues[$storeId][ProductInterface::STATUS] = $status;
        } else {
            $builder->product->setStatus($status);
        }
        return $builder;
    }

    public function withVisibility(int $visibility, $storeId = null) : ProductBuilder
    {
        $builder = clone $this;
        if ($storeId) {
            $builder->storeSpecificValues[$storeId][ProductInterface::VISIBILITY] = $visibility;
        } else {
            $builder->product->setVisibility($visibility);
        }
        return $builder;
    }

    public function withWebsiteIds(array $websiteIds) : ProductBuilder
    {
        $builder = clone $this;
        $builder->websiteIds = $websiteIds;
        return $builder;
    }

    public function withCategoryIds(array $categoryIds) : ProductBuilder
    {
        $builder = clone $this;
        $builder->categoryIds = $categoryIds;
        return $builder;
    }

    public function withPrice(float $price) : ProductBuilder
    {
        $builder = clone $this;
        $builder->product->setPrice($price);
        return $builder;
    }

    public function withTaxClassId($taxClassId) : ProductBuilder
    {
        $builder = clone $this;
        $builder->product->setData('tax_class_id', $taxClassId);
        return $builder;
    }

    public function withIsInStock(bool $inStock) : ProductBuilder
    {
        $builder = clone $this;
        $builder->product->getExtensionAttributes()->getStockItem()->setIsInStock($inStock);
        return $builder;
    }

    public function withStockQty($qty) : ProductBuilder
    {
        $builder = clone $this;
        $builder->product->getExtensionAttributes()->getStockItem()->setQty($qty);
        return $builder;
    }

    public function withBackorders($backorders) : ProductBuilder
    {
        $builder = clone $this;
        $builder->product->getExtensionAttributes()->getStockItem()->setBackorders($backorders);
        return $builder;
    }

    public function withWeight($weight) : ProductBuilder
    {
        $builder = clone $this;
        $builder->product->setWeight($weight);
        return $builder;
    }

    public function withCustomAttributes(array $values, $storeId = null) : ProductBuilder
    {
        $builder = clone $this;
        foreach ($values as $code => $value) {
            if ($storeId) {
                $builder->storeSpecificValues[$storeId][$code] = $value;
            } else {
                $builder->product->setCustomAttribute($code, $value);
            }
        }
        return $builder;
    }

    public function build() : ProductInterface
    {
        try {
            $product = $this->createProduct();
            $this->indexerFactory->create()->load('cataloginventory_stock')->reindexRow($product->getId());
            return $product;
        } catch (\Exception $e) {
            $e->getPrevious();
            if ($this->isTransactionException($e) || $this->isTransactionException($e->getPrevious()))
            {
                throw IndexFailed::becauseInitiallyTriggeredInTransaction($e);
            }
            throw $e;
        }
    }

    private function createProduct(): ProductInterface
    {
        $builder = clone $this;
        if (!$builder->product->getSku()) {
            $builder->product->setSku(sha1(uniqid('', true)));
        }
        $builder->product->setCustomAttribute('url_key', $builder->product->getSku());
        $builder->product->setCategoryIds($builder->categoryIds);
        $product = $builder->productRepository->save($builder->product);
        foreach ($builder->websiteIds as $websiteId) {
            /** @var ProductWebsiteLinkInterface $websiteLink */
            $websiteLink = $builder->websiteLinkFactory->create();
            $websiteLink->setWebsiteId($websiteId)->setSku($product->getSku());
            $builder->websiteLinkRepository->save($websiteLink);
        }
        foreach ($builder->storeSpecificValues as $storeId => $values) {
            /** @var Product $storeProduct */
            $storeProduct = clone $product;
            $storeProduct->setStoreId($storeId);
            $storeProduct->addData($values);
            $storeProduct->save();
        }
        return $product;
    }

    private function isTransactionException($exception)
    {
        if ($exception === null) {
            return false;
        }
        return preg_match(
            '{please retry transaction|DDL statements are not allowed in transactions}i',
            $exception->getMessage()
        );
    }
}
