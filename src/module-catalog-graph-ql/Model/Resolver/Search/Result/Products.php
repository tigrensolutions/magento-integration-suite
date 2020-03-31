<?php
/**
 * @author    Tigren Solutions <info@tigren.com>
 * @copyright Copyright (c) 2019 Tigren Solutions <https://www.tigren.com>. All rights reserved.
 * @license   Open Software License ("OSL") v. 3.0
 */

declare(strict_types=1);

namespace Tigren\CatalogGraphQl\Model\Resolver\Search\Result;

use Magento\Catalog\Model\Product;
use Magento\CatalogSearch\Model\Advanced;
use Magento\CatalogSearch\Model\ResourceModel\Advanced\Collection;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\FieldTranslator;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;

/**
 * Class Products
 * @package Tigren\CatalogGraphQl\Model\Resolver\Search
 */
class Products implements ResolverInterface
{
    /**
     * @var FieldTranslator
     */
    private $fieldTranslator;

    /**
     * Products constructor.
     * @param FieldTranslator $fieldTranslator
     */
    public function __construct(
        FieldTranslator $fieldTranslator
    ) {
        $this->fieldTranslator = $fieldTranslator;
    }

    /**
     * @inheritdoc
     */
    public function resolve(Field $field, $context, ResolveInfo $info, array $value = null, array $args = null)
    {
        /** @var Advanced $searchModel */
        $searchModel = $this->_getSearchModel($value);
        /** @var Collection $collection */
        $collection = $searchModel->getProductCollection();

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
        $attributes = $this->getProductFields($info);
        // Methods that perform extra fetches post-load
        if (in_array('media_gallery_entries', $attributes)) {
            $collection->addMediaGalleryData();
        }
        if (in_array('options', $attributes)) {
            $collection->addOptionsToResult();
        }
        $collection->load();

        $productArray = [];
        /** @var Product $product */
        foreach ($collection->getItems() as $product) {
            $productArray[$product->getId()] = $product->getData();
            $productArray[$product->getId()]['model'] = $product;
        }

        $data = [
            'total_count' => $collection->getSize(),
            'items' => $productArray,
            'page_info' => [
                'page_size' => $args['pageSize'],
                'current_page' => $args['currentPage']
            ]
        ];

        return $data;


    }

    /**
     * Get search model
     *
     * @param array $value
     * @return Advanced
     * @throws LocalizedException
     */
    private function _getSearchModel(array $value)
    {
        if (!isset($value['model'])) {
            throw new LocalizedException(__('"model" value should be specified'));
        }
        return $value['model'];
    }

    /**
     * Return field names for all requested product fields.
     *
     * @param ResolveInfo $info
     * @return string[]
     */
    private function getProductFields(ResolveInfo $info): array
    {
        $fieldNames = [];
        foreach ($info->fieldNodes as $node) {
            if ($node->name->value !== 'products') {
                continue;
            }
            foreach ($node->selectionSet->selections as $selection) {
                if ($selection->name->value !== 'items') {
                    continue;
                }

                foreach ($selection->selectionSet->selections as $itemSelection) {
                    if ($itemSelection->kind === 'InlineFragment') {
                        foreach ($itemSelection->selectionSet->selections as $inlineSelection) {
                            if ($inlineSelection->kind === 'InlineFragment') {
                                continue;
                            }
                            $fieldNames[] = $this->fieldTranslator->translate($inlineSelection->name->value);
                        }
                        continue;
                    }
                    $fieldNames[] = $this->fieldTranslator->translate($itemSelection->name->value);
                }
            }
        }

        return $fieldNames;
    }

}
