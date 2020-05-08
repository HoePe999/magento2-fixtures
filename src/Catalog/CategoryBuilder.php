<?php

namespace TddWizard\Fixtures\Catalog;

use Magento\Catalog\Api\CategoryLinkRepositoryInterface;
use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Catalog\Model\Category;
use \Magento\Catalog\Model\ResourceModel\Category as CategoryResource;
use Magento\Catalog\Api\Data\CategoryInterface;
use Magento\Catalog\Api\Data\CategoryProductLinkInterface;
use Magento\Catalog\Api\Data\CategoryProductLinkInterfaceFactory;
use Magento\Framework\ObjectManagerInterface;

class CategoryBuilder
{
    /**
     * @var CategoryInterface
     */
    private $category;

    /**
     * @var CategoryRepositoryInterface
     */
    private $categoryRepository;

    /**
     * @var CategoryResource
     */
    private $categoryResource;

    /**
     * @var CategoryLinkRepositoryInterface
     */
    private $categoryLinkRepository;

    /**
     * @var CategoryProductLinkInterface
     */
    private $productLinkFactory;

    /**
     * @var int[]
     */
    private $skus = [];

    public function __construct(
        CategoryRepositoryInterface $categoryRepository,
        CategoryResource $categoryResource,
        CategoryLinkRepositoryInterface $categoryLinkRepository,
        CategoryProductLinkInterfaceFactory $productLinkFactory,
        CategoryInterface $category,
        array $skus
    ) {
        $this->categoryRepository = $categoryRepository;
        $this->categoryResource = $categoryResource;
        $this->categoryLinkRepository = $categoryLinkRepository;
        $this->category = $category;
        $this->skus = $skus;
        $this->productLinkFactory = $productLinkFactory;
    }

    public static function topLevelCategory(ObjectManagerInterface $objectManager = null) : CategoryBuilder
    {
        if ($objectManager === null) {
            $objectManager = \Magento\TestFramework\Helper\Bootstrap::getObjectManager();
        }
        /** @var CategoryInterface $category */
        $category = $objectManager->create(CategoryInterface::class);

        $category->setName('Top Level Category');
        $category->setIsActive(true);
        $category->setPath('1/2');

        return new self(
            $objectManager->create(CategoryRepositoryInterface::class),
            $objectManager->create(CategoryResource::class),
            $objectManager->create(CategoryLinkRepositoryInterface::class),
            $objectManager->create(CategoryProductLinkInterfaceFactory::class),
            $category,
            []
        );
    }

    public static function childCategoryOf(
        CategoryFixture $parent,
        ObjectManagerInterface $objectManager = null
    ): CategoryBuilder
    {
        if ($objectManager === null) {
            $objectManager = \Magento\TestFramework\Helper\Bootstrap::getObjectManager();
        }
        /** @var CategoryInterface $category */
        $category = $objectManager->create(CategoryInterface::class);

        $category->setName('Child Category');
        $category->setIsActive(true);
        $category->setPath($parent->getCategory()->getPath());

        return new self(
            $objectManager->create(CategoryRepositoryInterface::class),
            $objectManager->create(CategoryResource::class),
            $objectManager->create(CategoryLinkRepositoryInterface::class),
            $objectManager->create(CategoryProductLinkInterfaceFactory::class),
            $category,
            []
        );
    }

    /**
     * Assigns products by sku. The keys of the array will be used for the sort position
     *
     * @param string[] $skus
     * @return CategoryBuilder
     */
    public function withProducts(array $skus) : CategoryBuilder
    {
        $builder = clone $this;
        $builder->skus = $skus;
        return $builder;
    }

    public function withDescription(string $description) : CategoryBuilder
    {
        $builder = clone $this;
        $builder->category->setCustomAttribute('description', $description);
        return $builder;
    }

    public function withName(string $name) : CategoryBuilder
    {
        $builder = clone $this;
        $builder->category->setName($name);
        return $builder;
    }

    public function withUrlKey(string $urlKey) : CategoryBuilder
    {
        $builder = clone $this;
        $builder->category->setData('url_key', $urlKey);
        return $builder;
    }

    public function withIsActive(bool $isActive) : CategoryBuilder
    {
        $builder = clone $this;
        $builder->category->setIsActive($isActive);
        return $builder;
    }

    public function __clone()
    {
        $this->category = clone $this->category;
    }

    public function build() : CategoryInterface
    {
        $builder = clone $this;
        if (!$builder->category->getData('url_key')) {
            $builder->category->setData('url_key', sha1(uniqid('', true)));
        }

        // Save with global scope if not specified otherwise
        if ($builder->category instanceof Category && !$builder->category->hasData('store_id')) {
            $builder->category->setStoreId(0);
        }
        $builder->categoryResource->save($builder->category);

        foreach ($builder->skus as $position => $sku) {
            /** @var CategoryProductLinkInterface $productLink */
            $productLink = $builder->productLinkFactory->create();
            $productLink->setSku($sku);
            $productLink->setPosition($position);
            $productLink->setCategoryId($builder->category->getId());
            $builder->categoryLinkRepository->save($productLink);
        }
        return $builder->category;
    }
}