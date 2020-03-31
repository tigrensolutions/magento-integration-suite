<?php
/**
 * @author    Tigren Solutions <info@tigren.com>
 * @copyright Copyright (c) 2019 Tigren Solutions <https://www.tigren.com>. All rights reserved.
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Tigren\CatalogGraphQl\Model\Resolver\Category;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Query\Resolver\ContextInterface;
use Magento\Framework\GraphQl\Query\Resolver\Value;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Tigren\CatalogGraphQl\Model\Resolver\Products\Query\Filter;

/**
 * Class Products
 * @package Tigren\CatalogGraphQl\Model\Resolver\Category
 */
class Products implements ResolverInterface
{
    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * @var Filter
     */
    private $filterQuery;

    /**
     * @param ProductRepositoryInterface $productRepository
     * @param Filter $filterQuery
     */
    public function __construct(
        ProductRepositoryInterface $productRepository,
        Filter $filterQuery
    ) {
        $this->productRepository = $productRepository;
        $this->filterQuery = $filterQuery;
    }

    /**
     * @param Field $field
     * @param ContextInterface $context
     * @param ResolveInfo $info
     * @param array|null $value
     * @param array|null $args
     * @return array|Value|mixed
     * @throws LocalizedException
     */
    public function resolve(Field $field, $context, ResolveInfo $info, array $value = null, array $args = null)
    {
        $searchResult = $this->filterQuery->getResult($info, $args);

        // possible division by 0
        if ($args['pageSize']) {
            $maxPages = ceil($searchResult->getTotalCount() / $args['pageSize']);
        } else {
            $maxPages = 0;
        }

        $currentPage = $args['currentPage'];
        if ($currentPage > $maxPages && $searchResult->getTotalCount() > 0) {
            throw new GraphQlInputException(
                __(
                    'currentPage value %1 specified is greater than the number of pages available.',
                    [$maxPages]
                )
            );
        }

        return [
            'total_count' => $searchResult->getTotalCount(),
            'items' => $searchResult->getProductsSearchResult(),
            'page_info' => [
                'page_size' => $args['pageSize'],
                'current_page' => $args['currentPage']
            ]
        ];
    }
}
