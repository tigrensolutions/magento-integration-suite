<?php

namespace Tigren\CatalogGraphQl\Plugin\GraphQL;

use Magento\Store\Model\StoreManagerInterface;

/**
 * Class CategoryTree
 * @package Tigren\CatalogGraphQl\Plugin\GraphQL
 */
class CategoryTree
{
    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * CategoryTree constructor.
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        StoreManagerInterface $storeManager
    ) {
        $this->storeManager = $storeManager;
    }

    /**
     * @param \Magento\CatalogGraphQl\Model\Resolver\CategoryTree $subject
     * @param $field
     * @param $context
     * @param $info
     * @param $value
     * @param $args
     * @return array
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function beforeResolve(
        \Magento\CatalogGraphQl\Model\Resolver\CategoryTree $subject,
        $field,
        $context,
        $info,
        $value,
        $args
    ) {
        if (isset($args['id']) && $args['id'] == -1) {
            $args['id'] = $this->storeManager->getStore()->getRootCategoryId();
        }
        return [$field, $context, $info, $value, $args];
    }

}
