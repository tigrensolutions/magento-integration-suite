<?php
/**
 * @author Tigren Solutions <info@tigren.com>
 * @copyright Copyright (c) 2019 Tigren Solutions <https://www.tigren.com>. All rights reserved.
 * @license Open Software License ("OSL") v. 3.0
 */
declare(strict_types=1);

namespace Tigren\CustomerGraphQl\Helper;

use Magento\Catalog\Model\ResourceModel\Eav\Attribute;
use Magento\Catalog\Model\ResourceModel\Product\Compare\Item\Collection;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Phrase;

/**
 * Class Data
 * @package Tigren\CustomerGraphQl\helper
 */
class Data extends \Magento\Framework\App\Helper\AbstractHelper
{

    /**
     * Product Compare Items Collection
     *
     * @var Collection
     */
    public $_itemCollection;
    public $_attributes;
    /**
     * @var ResourceConnection
     */
    protected $_resource;
    /**
     * @var \Magento\Catalog\Model\ResourceModel\Product\Compare\Item\CollectionFactory
     */
    protected $_itemCollectionFactory;
    /**
     * @var \Magento\Catalog\Model\Product\Visibility
     */
    protected $_catalogProductVisibility;
    /**
     * @var \Magento\Catalog\Model\Session
     */
    protected $_catalogSession;
    protected $_catalogConfig;
    /**
     * @var \Magento\Catalog\Helper\Product\Compare
     */
    protected $_compareProduct;
    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    private $_storeManager;

    /**
     * Data constructor.
     * @param ResourceConnection $resource
     * @param \Magento\Catalog\Model\ResourceModel\Product\Compare\Item\CollectionFactory $itemCollectionFactory
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Catalog\Model\Product\Visibility $catalogProductVisibility
     * @param \Magento\Catalog\Model\Session $catalogSession
     * @param \Magento\Framework\App\Helper\Context $context
     */
    public function __construct(
        ResourceConnection $resource,
        \Magento\Catalog\Model\ResourceModel\Product\Compare\Item\CollectionFactory $itemCollectionFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Catalog\Model\Product\Visibility $catalogProductVisibility,
        \Magento\Catalog\Model\Session $catalogSession,
        \Magento\Catalog\Model\Config $config,
        \Magento\Catalog\Helper\Product\Compare $compare,
        \Magento\Framework\App\Helper\Context $context
    ) {
        $this->_itemCollectionFactory = $itemCollectionFactory;
        $this->_resource = $resource;
        $this->_catalogProductVisibility = $catalogProductVisibility;
        $this->_storeManager = $storeManager;
        $this->_catalogSession = $catalogSession;
        $this->_catalogConfig = $config;
        $this->_compareProduct = $compare;
        parent::__construct($context);
    }

    /**
     * @param $sessionId
     * @param $customerId
     * @return string
     */
    public function createVisitor($sessionId, $customerId)
    {
        $visitorData = [
            'customer_id' => $customerId,
            'session_id' => $sessionId,
            'last_visit_at' => (new \DateTime())->format(\Magento\Framework\Stdlib\DateTime::DATETIME_PHP_FORMAT)
        ];
        $this->getConnection()->insert('customer_visitor', $visitorData);
        $visitorId = $this->getConnection()->fetchOne($this->getConnection()->select()->from(
            'customer_visitor',
            'visitor_id'
        )
            ->where('session_id = ?', $sessionId));
        return $visitorId;
    }

    /**
     * @return AdapterInterface
     */
    public function getConnection()
    {
        return $this->_resource->getConnection(ResourceConnection::DEFAULT_CONNECTION);
    }

    /**
     * @param $sessionId
     * @return string
     */
    public function getVisitorId($sessionId)
    {
        $visitor = $this->getConnection()->select()->from('customer_visitor', 'visitor_id')
            ->where('session_id = ?', $sessionId);
        $visitorId = $this->getConnection()->fetchOne($visitor);
        return $visitorId;
    }

    /**
     * @param $customerId
     * @param $visitorId
     * @return Collection
     * @throws NoSuchEntityException
     */
    public function getCompareCollection($customerId, $visitorId)
    {
        //if (!$this->_itemCollection) {
        $this->_compareProduct->setAllowUsedFlat(false);
        $this->_itemCollection = $this->_itemCollectionFactory->create();
        $this->_itemCollection->useProductItem(true)->setStoreId($this->_storeManager->getStore()->getId());

        if ($customerId) {
            $this->_itemCollection->setCustomerId($customerId);
        } else {
            $this->_itemCollection->setVisitorId($visitorId);
        }

        $this->_itemCollection->addAttributeToSelect(
            $this->_catalogConfig->getProductAttributes()
        )->loadComparableAttributes()->addMinimalPrice()->addTaxPercents()->setVisibility(
            $this->_catalogProductVisibility->getVisibleInSiteIds()
        );
        //}

        return $this->_itemCollection;
    }

    /**
     * @param $customerId
     * @param $visitorId
     * @param $productId
     * @return bool
     */
    public function isInserted($customerId, $visitorId, $productId)
    {
        if ($customerId) {
            $collection = $this->getConnection()->fetchAll(
                $this->getConnection()->select()->from('catalog_compare_item')
                    ->where('customer_id = ?', $customerId)
                    ->where('product_id = ?', $productId)
            );
        } else {
            $collection = $this->getConnection()->fetchAll(
                $this->getConnection()->select()->from('catalog_compare_item')
                    ->where('visitor_id = ?', $visitorId)
                    ->where('product_id = ?', $productId)
            );
        }
        if (count($collection) > 0) {
            return true;
        } else {
            return false;
        }
    }

    public function getAttributes()
    {
        if ($this->_attributes === null) {
            $this->_attributes = $this->_itemCollection->getComparableAttributes();
        }

        return $this->_attributes;
    }

    /**
     * Retrieve Product Attribute Value
     *
     * @param Product $product
     * @param Attribute $attribute
     * @return Phrase|string
     */
    public function getProductAttributeValue($product, $attribute)
    {
        if (!$product->hasData($attribute->getAttributeCode())) {
            return __('N/A');
        }

        if ($attribute->getSourceModel() || in_array(
                $attribute->getFrontendInput(),
                ['select', 'boolean', 'multiselect']
            )
        ) {
            $value = $attribute->getFrontend()->getValue($product);
        } else {
            $value = $product->getData($attribute->getAttributeCode());
        }
        return (string)$value == '' ? __('No') : $value;
    }
}
