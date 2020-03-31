<?php
/**
 * @author    Tigren Solutions <info@tigren.com>
 * @copyright Copyright (c) 2019 Tigren Solutions <https://www.tigren.com>. All rights reserved.
 * @license   Open Software License ("OSL") v. 3.0
 */
declare(strict_types=1);

namespace Tigren\CatalogGraphQl\Model\Resolver\Product;

use Exception;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Option;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\Resolver\ContextInterface;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Framework\Pricing\PriceCurrencyInterface;

/**
 * Format a product's option information to conform to GraphQL schema representation
 */
class Options implements ResolverInterface
{
    /**
     * @var PriceCurrencyInterface
     */
    protected $priceCurrency;

    /**
     * Options constructor.
     * @param PriceCurrencyInterface $priceCurrency
     */
    public function __construct(
        PriceCurrencyInterface $priceCurrency
    ) {
        $this->priceCurrency = $priceCurrency;
    }

    /**
     * @inheritdoc
     *
     * Format product's option data to conform to GraphQL schema
     *
     * @param Field $field
     * @param ContextInterface $context
     * @param ResolveInfo $info
     * @param array|null $value
     * @param array|null $args
     * @return null|array
     * @throws Exception
     */
    public function resolve(
        Field $field,
        $context,
        ResolveInfo $info,
        array $value = null,
        array $args = null
    ) {
        if (!isset($value['model'])) {
            throw new LocalizedException(__('"model" value should be specified'));
        }

        /** @var Product $product */
        $product = $value['model'];

        $options = null;
        if (!empty($product->getOptions())) {
            $options = [];
            /** @var Option $option */
            foreach ($product->getOptions() as $key => $option) {
                $options[$key] = $option->getData();
                $options[$key]['required'] = $option->getIsRequire();
                $options[$key]['product_sku'] = $option->getProductSku();

                $values = $option->getValues() ?: [];
                /** @var Option\Value $value */
                foreach ($values as $valueKey => $value) {
                    $options[$key]['value'][$valueKey] = $value->getData();
                    $options[$key]['value'][$valueKey]['price_type']
                        = $value->getPriceType() !== null ? strtoupper($value->getPriceType()) : 'DYNAMIC';
                    if ($options[$key]['value'][$valueKey]['price_type'] != 'PERCENT') {
                        $options[$key]['value'][$valueKey]['price']
                            = $this->priceCurrency->convertAndRound($options[$key]['value'][$valueKey]['price']);
                    }
                }

                if (empty($values)) {
                    $options[$key]['value'] = $option->getData();
                    $options[$key]['value']['price_type']
                        = $option->getPriceType() !== null ? strtoupper($option->getPriceType()) : 'DYNAMIC';
                    if ($options[$key]['value']['price_type'] != 'PERCENT') {
                        $options[$key]['value']['price']
                            = $this->priceCurrency->convertAndRound($options[$key]['value']['price']);
                    }
                }
            }
        }

        return $options;
    }
}
