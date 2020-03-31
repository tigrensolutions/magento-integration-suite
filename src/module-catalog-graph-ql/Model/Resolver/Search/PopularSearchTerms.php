<?php
/**
 * @author    Tigren Solutions <info@tigren.com>
 * @copyright Copyright (c) 2019 Tigren Solutions <https://www.tigren.com>. All rights reserved.
 * @license   Open Software License ("OSL") v. 3.0
 */

declare(strict_types=1);

namespace Tigren\CatalogGraphQl\Model\Resolver\Search;

use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;

/**
 * Class PopularSearchTerms
 * @package Tigren\CatalogGraphQl\Model\Resolver\Search
 */
class PopularSearchTerms implements ResolverInterface
{
    /**
     * @var DataProvider
     */
    protected $provider;

    /**
     * AllBrands constructor.
     * @param DataProvider $provider
     */
    public function __construct(
        DataProvider $provider
    ) {
        $this->provider = $provider;
    }

    /**
     * @inheritdoc
     */
    public function resolve(Field $field, $context, ResolveInfo $info, array $value = null, array $args = null)
    {
        $result = [
            'items' => $this->provider->getPopularSearchTerms()
        ];
        return $result;
    }
}
