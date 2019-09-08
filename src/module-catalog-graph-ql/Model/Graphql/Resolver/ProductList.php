<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Tigren\CatalogGraphQl\Model\Graphql\Resolver;

use Magento\Catalog\Model\Config as CatalogConfig;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\CatalogWidget\Model\Rule;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Rule\Model\Condition\Sql\Builder;
use Magento\Widget\Helper\Conditions;
use Tigren\CatalogGraphQl\Model\ResourceModel\Product\Bestsellers\Collection as BestsellerCollection;
use Tigren\Core\Helper\Data;
use Tigren\Core\Model\Config;
use Zend_Db_Expr;

/**
 * @inheritdoc
 */
class ProductList implements ResolverInterface
{

    /**
     * @var Data
     */
    protected $_helper;
    /**
     * @var TimezoneInterface
     */
    protected $_localeDate;
    /**
     * @var Visibility
     */
    protected $_visibility;
    /**
     * @var CatalogConfig
     */
    protected $_catalogConfig;
    protected $_config;
    /**
     * @var Rule
     */
    protected $rule;
    /**
     * @var Builder
     */
    protected $sqlBuilder;
    /**
     * @var Conditions
     */
    protected $conditionsHelper;

    private $_collectionFactory;

    private $bestsellerCollection;

    private $config;

    /**
     * ProductList constructor.
     * @param Data $helper
     * @param CollectionFactory $collectionFactory
     * @param TimezoneInterface $localeDate
     * @param Visibility $visibility
     * @param CatalogConfig $catalogConfig
     * @param Rule $rule
     * @param Builder $sqlBuilder
     * @param Conditions $conditionsHelper
     * @param BestsellerCollection $bestsellerCollection
     * @param Config $config
     */
    public function __construct(
        Data $helper,
        CollectionFactory $collectionFactory,
        TimezoneInterface $localeDate,
        Visibility $visibility,
        CatalogConfig $catalogConfig,
        Rule $rule,
        Builder $sqlBuilder,
        Conditions $conditionsHelper,
        BestsellerCollection $bestsellerCollection,
        Config $config
    ) {
        $this->_helper = $helper;
        $this->_collectionFactory = $collectionFactory;
        $this->_localeDate = $localeDate;
        $this->_visibility = $visibility;
        $this->_catalogConfig = $catalogConfig;
        $this->rule = $rule;
        $this->sqlBuilder = $sqlBuilder;
        $this->conditionsHelper = $conditionsHelper;
        $this->bestsellerCollection = $bestsellerCollection;
        $this->config = $config;
    }

    /**
     * @inheritdoc
     */
    public function resolve(Field $field, $context, ResolveInfo $info, array $value = null, array $args = null)
    {
        $showOnHomePage = $this->config->showOnHomePage();
        $data = [];
        $data['new_items'] = !empty($showOnHomePage['new']) ? $this->getNewProducts() : [];
        $data['featured_items'] = !empty($showOnHomePage['feature']) ? $this->getFeatureProducts() : [];
        $data['bestseller_items'] = !empty($showOnHomePage['bestseller']) ? $this->getBestsellerProducts() : [];
        return $data;
    }

    /**
     * @return array
     */
    private function getNewProducts()
    {
        /** @var Collection $collection */
        $todayStartOfDayDate = $this->_localeDate->date()->setTime(0, 0, 0)->format('Y-m-d H:i:s');
        $todayEndOfDayDate = $this->_localeDate->date()->setTime(23, 59, 59)->format('Y-m-d H:i:s');

        /** @var $collection Collection */
        $collection = $this->_collectionFactory->create();
        $collection->setVisibility($this->_visibility->getVisibleInCatalogIds());

        $collection = $this->_addProductAttributesAndPrices(
            $collection
        )->addStoreFilter()->addAttributeToFilter(
            'news_from_date',
            [
                'or' => [
                    0 => ['date' => true, 'to' => $todayEndOfDayDate],
                    1 => ['is' => new Zend_Db_Expr('null')],
                ]
            ],
            'left'
        )->addAttributeToFilter(
            'news_to_date',
            [
                'or' => [
                    0 => ['date' => true, 'from' => $todayStartOfDayDate],
                    1 => ['is' => new Zend_Db_Expr('null')],
                ]
            ],
            'left'
        )->addAttributeToFilter(
            [
                ['attribute' => 'news_from_date', 'is' => new Zend_Db_Expr('not null')],
                ['attribute' => 'news_to_date', 'is' => new Zend_Db_Expr('not null')],
            ]
        )->addAttributeToSort(
            'news_from_date',
            'desc'
        )->setPageSize(
            5
        )->setCurPage(
            1
        );
        $productArray = [];
        /** @var Product $product */
        foreach ($collection->getItems() as $product) {
            $productArray[$product->getId()] = $product->getData();
            $productArray[$product->getId()]['model'] = $product;
        }
        return $productArray;
    }

    /**
     * Add all attributes and apply pricing logic to products collection
     * to get correct values in different products lists.
     * E.g. crosssells, upsells, new products, recently viewed
     *
     * @param Collection $collection
     * @return Collection
     */
    protected function _addProductAttributesAndPrices(
        Collection $collection
    ) {
        return $collection
            ->addMinimalPrice()
            ->addFinalPrice()
            ->addTaxPercents()
            ->addAttributeToSelect($this->_catalogConfig->getProductAttributes())
            ->addUrlRewrite();
    }

    /**
     *
     * @return Collection
     */
    public function getFeatureProducts()
    {
        $conditions = $this->getConditions();
        if (!$conditions) {
            return [];
        }
        /** @var $collection Collection */
        $collection = $this->_collectionFactory->create();
        $collection->setVisibility($this->_visibility->getVisibleInCatalogIds());

        $collection = $this->_addProductAttributesAndPrices($collection)
            ->addStoreFilter()
            ->setPageSize(5)
            ->setCurPage(1);

        $conditions->collectValidatedAttributes($collection);
        $this->sqlBuilder->attachConditionToCollection($collection, $conditions);

        /**
         * Prevent retrieval of duplicate records. This may occur when multiselect product attribute matches
         * several allowed values from condition simultaneously
         */
        $collection->distinct(true);

        $productArray = [];
        /** @var Product $product */
        foreach ($collection->getItems() as $product) {
            $productArray[$product->getId()] = $product->getData();
            $productArray[$product->getId()]['model'] = $product;
        }
        return $productArray;
    }

    private function getConditions()
    {
        $conditions = $this->_helper->getConditionFeature();
        if (!$conditions) {
            return null;
        }
        $conditions = $this->conditionsHelper->decode($conditions);
        foreach ($conditions as $key => $condition) {
            if (!empty($condition['attribute'])
                && in_array($condition['attribute'], ['special_from_date', 'special_to_date'])
            ) {
                $conditions[$key]['value'] = date('Y-m-d H:i:s', strtotime($condition['value']));
            }
        }
        $this->rule->loadPost(['conditions' => $conditions]);
        return $this->rule->getConditions();
    }

    /**
     * @return array
     */
    public function getBestsellerProducts()
    {
        /** @var $collection Collection */
        $collection = $this->bestsellerCollection->setVisibility($this->_visibility->getVisibleInCatalogIds());
        $productArray = [];
        $collection = $this->_addProductAttributesAndPrices($collection)
            ->addStoreFilter()
            ->setPageSize(5)
            ->setCurPage(1);
        /**
         * Prevent retrieval of duplicate records. This may occur when multiselect product attribute matches
         * several allowed values from condition simultaneously
         */
        $collection->distinct(true);

        $productArray = [];
        /** @var Product $product */
        foreach ($collection->getItems() as $product) {
            $productArray[$product->getId()] = $product->getData();
            $productArray[$product->getId()]['model'] = $product;
        }
        return $productArray;
    }

}
