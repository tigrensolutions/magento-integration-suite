<?php

namespace Tigren\CatalogGraphQl\Plugin\GraphQL\Resolver;

use Magento\Store\Model\StoreManagerInterface;
use Tigren\Core\Helper\Data;
use Magento\Cms\Api\Data\PageInterface;

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

    protected $helper;


    /**
     * CategoryTree constructor.
     * @param StoreManagerInterface $storeManager
     * @param Data $helper
     */
    public function __construct(
        StoreManagerInterface $storeManager,
        Data $helper
    ) {
        $this->storeManager = $storeManager;
        $this->helper = $helper;
    }

    public function aroundResolve(
        \Magento\CatalogGraphQl\Model\Resolver\CategoryTree $subject,
        \Closure $proceed,
        $field,
        $context,
        $info,
        $value,
        $args
    ) {
        if (isset($args['id']) && $args['id'] == -1) {
            $args['id'] = $this->storeManager->getStore()->getRootCategoryId();
        }
        $result = $proceed($field, $context, $info, $value, $args);
        return $this->helper->applyMetaConfig($result,'cat');
    }

}
