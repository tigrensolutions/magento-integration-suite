<?php
/**
 * @author    Tigren Solutions <info@tigren.com>
 * @copyright Copyright (c) 2019 Tigren Solutions <https://www.tigren.com>. All rights reserved.
 * @license   Open Software License ("OSL") v. 3.0
 */
declare(strict_types=1);

namespace Tigren\CatalogGraphQl\Model\Graphql\Resolver\Product\Option;

use Magento\Catalog\Model\ProductFactory;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\Exception\LocalizedException;

/**
 * @inheritdoc
 */
class Type implements ResolverInterface
{
    /**
     * @var ProductFactory
     */
    protected $productFactory;

    /**
     * FinalPrice constructor.
     * @param ProductFactory $productFactory
     */
    public function __construct(
        ProductFactory $productFactory
    ) {
        $this->productFactory = $productFactory;
    }

    /**
     * @inheritdoc
     */
    public function resolve(Field $field, $context, ResolveInfo $info, array $value = null, array $args = null)
    {
        if (!isset($value['type'])) {
            throw new LocalizedException(__('"type" value should be specified'));
        }
        return $value['type'];
    }

}
