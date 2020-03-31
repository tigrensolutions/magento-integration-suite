<?php
/**
 * @author    Tigren Solutions <info@tigren.com>
 * @copyright Copyright (c) 2019 Tigren Solutions <https://www.tigren.com>. All rights reserved.
 * @license   Open Software License ("OSL") v. 3.0
 */
declare(strict_types=1);

namespace Tigren\CompareGraphQl\Helper;

use Exception;
use Magento\Catalog\Helper\Product\Compare;
use Magento\Catalog\Model\Config;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Catalog\Model\ResourceModel\Eav\Attribute;
use Magento\Catalog\Model\ResourceModel\Product\Compare\Item\Collection;
use Magento\Catalog\Model\ResourceModel\Product\Compare\Item\CollectionFactory;
use Magento\Catalog\Model\Session;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Phrase;
use Magento\Framework\Stdlib\DateTime;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Class Data
 * @package Tigren\CompareGraphQl\helper
 */
class Data extends AbstractHelper
{
    /**
     * Product Compare Items Collection
     *
     * @var Collection
     */
    public $_itemCollection;

    /**
     * @var
     */
    public $_attributes;

    /**
     * @var ResourceConnection
     */
    protected $_resource;

    /**
     * @var CollectionFactory
     */
    protected $_itemCollectionFactory;

    /**
     * @var Visibility
     */
    protected $_catalogProductVisibility;

    /**
     * @var Session
     */
    protected $_catalogSession;

    /**
     * @var Config
     */
    protected $_catalogConfig;

    /**
     * @var Compare
     */
    protected $_compareProduct;

    /**
     * @var StoreManagerInterface
     */
    private $_storeManager;

    /**
     * Data constructor.
     * @param ResourceConnection $resource
     * @param CollectionFactory $itemCollectionFactory
     * @param StoreManagerInterface $storeManager
     * @param Visibility $catalogProductVisibility
     * @param Session $catalogSession
     * @param Context $context
     */
    public function __construct(
        ResourceConnection $resource,
        CollectionFactory $itemCollectionFactory,
        StoreManagerInterface $storeManager,
        Visibility $catalogProductVisibility,
        Session $catalogSession,
        Config $config,
        Compare $compare,
        Context $context
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
     * @throws Exception
     */
    public function createVisitor($sessionId, $customerId)
    {
        $visitorData = [
            'customer_id' => $customerId,
            'session_id' => $sessionId,
            'last_visit_at' => (new \DateTime())->format(DateTime::DATETIME_PHP_FORMAT)
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

    /**
     * @return array
     */
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
