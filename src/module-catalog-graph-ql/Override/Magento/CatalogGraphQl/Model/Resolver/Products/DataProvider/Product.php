<?php
/**
 * @author    Tigren Solutions <info@tigren.com>
 * @copyright Copyright (c) 2019 Tigren Solutions <https://www.tigren.com>. All rights reserved.
 * @license   Open Software License ("OSL") v. 3.0
 */
declare(strict_types=1);

namespace Tigren\CatalogGraphQl\Override\Magento\CatalogGraphQl\Model\Resolver\Products\DataProvider;

use Magento\Catalog\Model\Product\Visibility;
use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Catalog\Api\Data\ProductSearchResultsInterfaceFactory;
use Magento\Framework\Api\SearchResultsInterface;
use Magento\CatalogGraphQl\Model\Resolver\Products\DataProvider\Product\CollectionProcessorInterface;
use Magento\Framework\Exception\LocalizedException;

/**
 * Product field data provider, used for GraphQL resolver processing.
 */
class Product extends \Magento\CatalogGraphQl\Model\Resolver\Products\DataProvider\Product
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
    private $collectionProcessor;

    /**
     * @var Visibility
     */
    private $visibility;

    /**
     * @param CollectionFactory $collectionFactory
     * @param ProductSearchResultsInterfaceFactory $searchResultsFactory
     * @param Visibility $visibility
     * @param CollectionProcessorInterface $collectionProcessor
     */
    public function __construct(
        CollectionFactory $collectionFactory,
        ProductSearchResultsInterfaceFactory $searchResultsFactory,
        Visibility $visibility,
        CollectionProcessorInterface $collectionProcessor
    ) {
        $this->collectionFactory = $collectionFactory;
        $this->searchResultsFactory = $searchResultsFactory;
        $this->visibility = $visibility;
        $this->collectionProcessor = $collectionProcessor;
    }

    /**
     * Gets list of product data with full data set. Adds eav attributes to result set from passed in array
     *
     * @param SearchCriteriaInterface $searchCriteria
     * @param string[] $attributes
     * @param bool $isSearch
     * @param bool $isChildSearch
     * @return SearchResultsInterface
     * @throws LocalizedException
     */
    public function getList(
        SearchCriteriaInterface $searchCriteria,
        array $attributes = [],
        bool $isSearch = false,
        bool $isChildSearch = false
    ): SearchResultsInterface {
        /** @var Collection $collection */
        $collection = $this->collectionFactory->create();

        $this->collectionProcessor->process($collection, $searchCriteria, $attributes);

        if (!$isChildSearch) {
            $visibilityIds = $isSearch
                ? $this->visibility->getVisibleInSearchIds()
                : $this->visibility->getVisibleInCatalogIds();
            $collection->setVisibility($visibilityIds);
        }
        $collection->addAttributeToSelect('special_from_date');
        $collection->addAttributeToSelect('special_to_date');
        $collection->load();

        // Methods that perform extra fetches post-load
        if (in_array('media_gallery_entries', $attributes)) {
            $collection->addMediaGalleryData();
        }
        if (in_array('media_gallery', $attributes)) {
            $collection->addMediaGalleryData();
        }
        if (in_array('options', $attributes)) {
            $collection->addOptionsToResult();
        }

        $searchResult = $this->searchResultsFactory->create();
        $searchResult->setSearchCriteria($searchCriteria);
        $searchResult->setItems($collection->getItems());
        $searchResult->setTotalCount($collection->getSize());
        return $searchResult;
    }
}
