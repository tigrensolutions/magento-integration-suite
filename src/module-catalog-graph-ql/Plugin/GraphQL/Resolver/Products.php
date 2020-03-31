<?php
/**
 * @author    Tigren Solutions <info@tigren.com>
 * @copyright Copyright (c) 2019 Tigren Solutions <https://www.tigren.com>. All rights reserved.
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Tigren\CatalogGraphQl\Plugin\GraphQL\Resolver;

use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Tigren\Core\Helper\Data;

/**
 * Class Products
 * @package Tigren\CatalogGraphQl\Plugin\GraphQL\Resolver
 */
class Products
{
    /**
     * @var Data
     */
    protected $helper;

    /**
     * Products constructor.
     * @param Data $helper
     */
    public function __construct(
        Data $helper
    ) {
        $this->helper = $helper;
    }

    /**
     * @param \Magento\CatalogGraphQl\Model\Resolver\Products $subject
     * @param callable $proceed
     * @param Field $field
     * @param $context
     * @param ResolveInfo $info
     * @param array|null $value
     * @param array|null $args
     * @return mixed
     */
    public function aroundResolve(
        \Magento\CatalogGraphQl\Model\Resolver\Products $subject,
        callable $proceed,
        Field $field,
        $context,
        ResolveInfo $info,
        array $value = null,
        array $args = null
    ) {
        $result = $proceed($field, $context, $info, $value, $args);
        if (!empty($result['items'])) {
            $apply = false;
            foreach ($result['items'] as $key => $item) {
                if ($apply) {
                    continue;
                }
                $result['items'][$key] = $this->helper->applyMetaConfig($result['items'][$key], 'prod');
                $apply = true;
            }
        }
        return $result;
    }
}
