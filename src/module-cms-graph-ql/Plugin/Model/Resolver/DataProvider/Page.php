<?php

namespace Tigren\CmsGraphQl\Plugin\Model\Resolver\DataProvider;

use Closure;
use Tigren\Core\Helper\Data;

/**
 * Class Page
 * @package Tigren\CmsGraphQl\Plugin\Model\Resolver\DataProvider
 */
class Page
{
    /**
     * @var Data
     */
    protected $helper;

    /**
     * Page constructor.
     * @param Data $helper
     */
    public function __construct(
        Data $helper
    ) {
        $this->helper = $helper;
    }

    /**
     * @param \Magento\CmsGraphQl\Model\Resolver\DataProvider\Page $subject
     * @param Closure $proceed
     * @param $pageId
     * @return array
     */
    public function aroundGetData(
        \Magento\CmsGraphQl\Model\Resolver\DataProvider\Page $subject,
        Closure $proceed,
        $pageId
    ) {
        $result = $proceed($pageId);
        return $this->helper->applyMetaConfig($result, 'cms');
    }
}
