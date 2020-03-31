<?php
/**
 * @author    Tigren Solutions <info@tigren.com>
 * @copyright Copyright (c) 2019 Tigren Solutions <https://www.tigren.com>. All rights reserved.
 * @license   Open Software License ("OSL") v. 3.0
 */

declare(strict_types=1);

namespace Tigren\CatalogGraphQl\Model\Graphql\Resolver;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Exception\GraphQlNoSuchEntityException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Catalog\Model\ProductFactory;

/**
 * @inheritdoc
 */
class Product implements ResolverInterface
{

    /**
     * @var ProductFactory
     */
    private $productFactory;

    /**
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
        $productId = $this->getProductId($args);
        $data = $this->getProductData($productId);

        return $data;
    }

    /**
     * @param array $args
     * @return int
     * @throws GraphQlInputException
     */
    private function getProductId(array $args): int
    {
        if (!isset($args['id'])) {
            throw new GraphQlInputException(__('"Product id should be specified'));
        }

        return (int)$args['id'];
    }

    /**
     * @param int $productId
     * @return array
     */
    private function getProductData(int $productId): array
    {
        $product = $this->productFactory->create()->load($productId);
        if (!$product->getId()) {
            throw new GraphQlNoSuchEntityException(__($e->getMessage()), $e);
        }
        $productData = $product->getData();
        $productData['model'] = $product;
        return [
            'item' => $productData
        ];
    }
}
