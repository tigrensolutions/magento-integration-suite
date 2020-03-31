<?php
/**
 * @author    Tigren Solutions <info@tigren.com>
 * @copyright Copyright (c) 2019 Tigren Solutions <https://www.tigren.com>. All rights reserved.
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Tigren\CatalogGraphQl\Model\Resolver\Search;

use Magento\Catalog\Model\ProductFactory;
use Magento\Catalog\Model\ResourceModel\Product\Attribute\CollectionFactory as AttributeCollectionFactory;
use Magento\Eav\Model\Entity\Attribute\AbstractAttribute;
use Magento\Framework\Data\Collection\AbstractDb as DbCollection;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\UrlFactory;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Search\Model\ResourceModel\Query\CollectionFactory;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Class DataProvider
 * @package namespace Tigren\CatalogGraphQl\Model\Resolver\Search;
 */
class DataProvider
{
    /**
     * Store manager
     *
     * @var StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * @var array
     */
    protected $_terms;

    /**
     * @var int
     */
    protected $_minPopularity;

    /**
     * @var int
     */
    protected $_maxPopularity;

    /**
     * Url factory
     *
     * @var UrlFactory
     */
    protected $_urlFactory;

    /**
     * Query collection factory
     *
     * @var CollectionFactory
     */
    protected $_queryCollectionFactory;

    /**
     * Attribute collection factory
     *
     * @var AttributeCollectionFactory
     */
    protected $_attributeCollectionFactory;

    /**
     * Product factory
     *
     * @var ProductFactory
     */
    protected $_productFactory;

    /**
     * @var array
     */
    protected $_attributes;

    /**
     * DataProvider constructor.
     * @param StoreManagerInterface $storeManager
     * @param CollectionFactory $queryCollectionFactory
     * @param UrlFactory $urlFactory
     * @param AttributeCollectionFactory $attributeCollectionFactory
     * @param ProductFactory $productFactory
     */
    public function __construct(
        StoreManagerInterface $storeManager,
        CollectionFactory $queryCollectionFactory,
        UrlFactory $urlFactory,
        AttributeCollectionFactory $attributeCollectionFactory,
        ProductFactory $productFactory
    ) {
        $this->_queryCollectionFactory = $queryCollectionFactory;
        $this->_urlFactory = $urlFactory;
        $this->_storeManager = $storeManager;
        $this->_attributeCollectionFactory = $attributeCollectionFactory;
        $this->_productFactory = $productFactory;
    }

    /**
     * @return array
     * @throws NoSuchEntityException
     */
    public function getPopularSearchTerms()
    {
        $this->_loadTerms();
        return $this->_terms;
    }

    /**
     * Load terms and try to sort it by names
     *
     * @return $this
     * @throws NoSuchEntityException
     */
    protected function _loadTerms()
    {
        if (empty($this->_terms)) {
            $this->_terms = [];
            $terms = $this->_queryCollectionFactory->create()->setPopularQueryFilter(
                $this->_storeManager->getStore()->getId()
            )->setPageSize(
                100
            )->load()->getItems();

            if (count($terms) == 0) {
                return $this;
            }

            $this->_maxPopularity = reset($terms)->getPopularity();
            $this->_minPopularity = end($terms)->getPopularity();
            $range = $this->_maxPopularity - $this->_minPopularity;
            $range = $range == 0 ? 1 : $range;
            foreach ($terms as $term) {
                if (!$term->getPopularity()) {
                    continue;
                }
                $term->setRatio(($term->getPopularity() - $this->_minPopularity) / $range);
                $temp[$term->getQueryText()] = $term->getData();
                $termKeys[] = $term->getQueryText();
            }
            natcasesort($termKeys);

            foreach ($termKeys as $termKey) {
                $this->_terms[$termKey] = $temp[$termKey];
            }
        }

        return $this;
    }

    /**
     * @return int
     */
    public function getMaxPopularity()
    {
        return $this->_maxPopularity;
    }

    /**
     * @return int
     */
    public function getMinPopularity()
    {
        return $this->_minPopularity;
    }

    /**
     * @return array|null
     * @throws NoSuchEntityException
     */
    public function getSearchAttributes()
    {
        if ($this->_attributes === null) {
            $this->_attributes = [];
            $product = $this->_productFactory->create();
            $attributes = $this->_attributeCollectionFactory
                ->create()
                ->addHasOptionsFilter()
                ->addDisplayInAdvancedSearchFilter()
                ->addStoreLabel($this->_storeManager->getStore()->getId())
                ->setOrder('main_table.attribute_id', 'asc')
                ->load();
            foreach ($attributes as $attribute) {
                /** @var AbstractAttribute $attribute */
                $attribute->setEntity($product->getResource());
                $this->_attributes[] = [
                    'attribute_code' => $attribute->getAttributeCode(),
                    'entity_type' => '4',
                    'input_type' => $this->getAttributeInputType($attribute),
                    'label' => $attribute->getStoreLabel(),
                ];
            }

        }
        return $this->_attributes;
    }

    /**
     * Retrieve attribute input type
     *
     * @param AbstractAttribute $attribute
     * @return  string
     * @throws LocalizedException
     */
    public function getAttributeInputType($attribute)
    {
        $dataType = $attribute->getBackend()->getType();
        $inputType = $attribute->getFrontend()->getInputType();
        if ($inputType == 'select' || $inputType == 'multiselect') {
            return 'select';
        }

        if ($inputType == 'boolean') {
            return 'yesno';
        }

        if ($inputType == 'price') {
            return 'price';
        }

        if ($dataType == 'int' || $dataType == 'decimal') {
            return 'number';
        }

        if ($dataType == 'datetime') {
            return 'date';
        }

        return 'string';
    }
}
