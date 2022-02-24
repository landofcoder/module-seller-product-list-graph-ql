<?php
declare(strict_types=1);

namespace Lofmp\ProductListGraphQl\Model\Resolver\Products\DataProvider;

use Magento\Catalog\Model\Product\Visibility;
use Magento\CatalogGraphQl\Model\Resolver\Products\DataProvider\Product\CollectionPostProcessor;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Catalog\Api\Data\ProductSearchResultsInterfaceFactory;
use Magento\Framework\Api\SearchResultsInterface;
use Magento\CatalogGraphQl\Model\Resolver\Products\DataProvider\Product\CollectionProcessorInterface;
use Magento\GraphQl\Model\Query\ContextInterface;
use Lofmp\Productlist\Model\ProductFactory as ProductListProductFactory;

/**
 * Product field data provider, used for GraphQL resolver processing.
 */
class Product
{
    /**
     * @var CollectionFactory
     */
    private $collectionFactory;

    /**
     * @var ProductSearchResultsInterfaceFactory
     */
    private $searchResultsFactory;

    /**
     * @var CollectionProcessorInterface
     */
    private $collectionPreProcessor;

    /**
     * @var CollectionPostProcessor
     */
    private $collectionPostProcessor;

    /**
     * @var Visibility
     */
    private $visibility;

    /**
     * @param CollectionFactory $collectionFactory
     * @param ProductSearchResultsInterfaceFactory $searchResultsFactory
     * @param Visibility $visibility
     * @param CollectionProcessorInterface $collectionProcessor
     * @param CollectionPostProcessor $collectionPostProcessor
     * @param ProductListProductFactory $productFactory
     */
    public function __construct(
        CollectionFactory $collectionFactory,
        ProductSearchResultsInterfaceFactory $searchResultsFactory,
        Visibility $visibility,
        CollectionProcessorInterface $collectionProcessor,
        CollectionPostProcessor $collectionPostProcessor,
        ProductListProductFactory $productFactory
    ) {
        $this->collectionFactory = $collectionFactory;
        $this->searchResultsFactory = $searchResultsFactory;
        $this->visibility = $visibility;
        $this->collectionPreProcessor = $collectionProcessor;
        $this->collectionPostProcessor = $collectionPostProcessor;
        $this->productFactory = $productFactory;
    }

    /**
     * Gets list of product data with full data set. Adds eav attributes to result set from passed in array
     *
     * @param SearchCriteriaInterface $searchCriteria
     * @param string[] $attributes
     * @param bool $isSearch
     * @param bool $isChildSearch
     * @param ContextInterface|null $context
     * @param string $source_key
     * @param int $sellerId
     * @return SearchResultsInterface
     */
    public function getList(
        SearchCriteriaInterface $searchCriteria,
        array $attributes = [],
        bool $isSearch = false,
        bool $isChildSearch = false,
        ContextInterface $context = null,
        $sourceKey = 'latest',
        $sellerId = 0
    ): SearchResultsInterface {
        $product = $this->productFactory->create();
        $config = [];
        $collection = null;
        switch ($sourceKey) {
            case 'latest':
                $collection = $product->getLatestProducts($sellerId, $config);
                break;
            case 'newArrival':
                $collection = $product->getNewarrivalProducts($sellerId, $config);
                break;
            case 'special':
                $collection = $product->getSpecialProducts($sellerId, $config);
                break;
            case 'mostPopular':
                $collection = $product->getMostViewedProducts($sellerId, $config);
                break;
            case 'bestseller':
                $collection = $product->getBestsellerProducts($sellerId, $config);
                break;
            case 'topRated':
                $collection = $product->getTopratedProducts($sellerId, $config);
                break;
            case 'random':
                $collection = $product->getRandomProducts($sellerId, $config);
                break;
            case 'featured':
                $collection = $product->getFeaturedProducts($sellerId, $config);
                break;
            case 'deals':
                $collection = $product->getDealsProducts($sellerId, $config);
                break;
            default:
            break;
        }
        $items = [];
        $size = 0;
        if ($collection) {
            $this->collectionPreProcessor->process($collection, $searchCriteria, $attributes, $context);

            if (!$isChildSearch) {
                $visibilityIds = $isSearch
                    ? $this->visibility->getVisibleInSearchIds()
                    : $this->visibility->getVisibleInCatalogIds();
                $collection->setVisibility($visibilityIds);
            }

            $collection->load();
            $this->collectionPostProcessor->process($collection, $attributes);
            $items = $collection->getItems();
            $size = $collection->getSize();
        }
        $searchResult = $this->searchResultsFactory->create();
        $searchResult->setSearchCriteria($searchCriteria);
        $searchResult->setItems($items);
        $searchResult->setTotalCount($size);
        return $searchResult;
    }
}
