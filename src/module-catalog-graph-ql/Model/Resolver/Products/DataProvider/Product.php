<?php
/**
 * @author    Tigren Solutions <info@tigren.com>
 * @copyright Copyright (c) 2019 Tigren Solutions <https://www.tigren.com>. All rights reserved.
 * @license   Open Software License ("OSL") v. 3.0
 */
declare(strict_types=1);

namespace Tigren\CatalogGraphQl\Model\Resolver\Products\DataProvider;

use Magento\Catalog\Model\Layer;
use Magento\Catalog\Model\Layer\Resolver;
use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Magento\Eav\Model\Entity\Collection\AbstractCollection;
use Magento\Framework\Exception\LocalizedException;

/**
 * Product field data provider, used for GraphQL resolver processing.
 */
class Product
{
    /**
     * @var Layer
     */
    protected $_layerResolver;

    /**
     * Product constructor.
     * @param Resolver $layerResolver
     */
    public function __construct(
        Resolver $layerResolver
    ) {
        $this->_layerResolver = $layerResolver;
    }

    /**
     * @param SearchCriteriaInterface $searchCriteria
     * @param array $attributes
     * @param array $args
     * @return \Magento\Catalog\Model\ResourceModel\Collection\AbstractCollection|Collection|AbstractCollection
     * @throws LocalizedException
     */
    public function getList(
        array $attributes = [],
        array $args = []
    ) {
        $collection = $this->_layerResolver->get()->getProductCollection();
        $collection->setPageSize($args['pageSize']);
        $collection->setCurPage($args['currentPage']);
        if (isset($args['sort']) && is_array($args['sort'])) {
            $currentOrder = key($args['sort']);
            $currentDirection = $args['sort'][$currentOrder];
            if ($currentOrder == 'position') {
                $collection->addAttributeToSort(
                    $currentOrder,
                    $currentDirection
                );
            } else {
                $collection->setOrder($currentOrder, $currentDirection);
            }
        }

        // Methods that perform extra fetches post-load
        if (in_array('media_gallery_entries', $attributes)) {
            $collection->addMediaGalleryData();
        }

        if (in_array('options', $attributes)) {
            $collection->addOptionsToResult();
        }

        return $collection->load();
    }
}
