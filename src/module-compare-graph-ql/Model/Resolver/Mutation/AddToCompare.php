<?php
/**
 * @author Tigren Solutions <info@tigren.com>
 * @copyright Copyright (c) 2019 Tigren Solutions <https://www.tigren.com>. All rights reserved.
 * @license Open Software License ("OSL") v. 3.0
 */
declare(strict_types=1);

namespace Tigren\CompareGraphQl\Model\Resolver\Mutation;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Helper\Product\Compare;
use Magento\Catalog\Model\Product\Compare\Item;
use Magento\Catalog\Model\Product\Compare\ItemFactory;
use Magento\Customer\Model\Session;
use Magento\Framework\Event\ManagerInterface as EventManager;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Framework\ObjectManagerInterface;
use Magento\Store\Model\StoreManagerInterface;
use Tigren\CompareGraphQl\Helper\Data;

/**
 * Class AddToCompare
 * @package Tigren\CompareGraphQl\Model\Resolver\Mutation
 */
class AddToCompare implements ResolverInterface
{
    /**
     * @var ProductRepositoryInterface
     */
    protected $productRepository;

    /**
     * @var ObjectManagerInterface
     */
    protected $_objectManager;

    /**
     * @var EventManager
     */
    protected $_eventManager;

    /**
     * @var StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * Customer session
     *
     * @var Session
     */
    protected $_customerSession;

    /**
     * @var Data
     */
    protected $_helper;

    /**
     * @var ItemFactory
     */
    protected $_compareItemFactory;

    /**
     * AddToCompare constructor.
     * @param ProductRepositoryInterface $productRepository
     * @param ObjectManagerInterface $objectManager
     * @param StoreManagerInterface $storeManager
     * @param Session $session
     * @param Data $helper
     * @param ItemFactory $compareItemFactory
     * @param EventManager $eventManager
     */
    public function __construct(
        ProductRepositoryInterface $productRepository,
        ObjectManagerInterface $objectManager,
        StoreManagerInterface $storeManager,
        Session $session,
        Data $helper,
        ItemFactory $compareItemFactory,
        EventManager $eventManager
    ) {
        $this->_compareItemFactory = $compareItemFactory;
        $this->_helper = $helper;
        $this->_customerSession = $session;
        $this->productRepository = $productRepository;
        $this->_objectManager = $objectManager;
        $this->_storeManager = $storeManager;
        $this->_eventManager = $eventManager;
    }

    /**
     * @inheritdoc
     */
    public function resolve(
        Field $field,
        $context,
        ResolveInfo $info,
        array $value = null,
        array $args = null
    ) {
        if (!isset($args['product'])) {
            throw new GraphQlInputException(__('Specify the "product" value.'));
        }
        $sessionId = $this->_customerSession->getSessionId();
        $customerId = null;
        if ($context->getUserId() && $context->getUserType()) {
            $customerId = $context->getUserId();
        }
        $visitorId = $this->_helper->getVisitorId($sessionId);
        if (!$visitorId) {
            $visitorId = $this->_helper->createVisitor($sessionId, $customerId);
        }
        if ($args['product']) {
            $storeId = $this->_storeManager->getStore()->getId();
            try {
                $product = $this->productRepository->getById($args['product'], false, $storeId);
            } catch (NoSuchEntityException $e) {
                $product = null;
            }

            if ($product) {
                /* @var $item Item */
                $item = $this->_compareItemFactory->create();
                if ($this->_helper->isInserted($customerId, $visitorId, $product->getId()) > 0) {
                    return false;
                }
                $this->_addVisitorToItem($item, $customerId, $visitorId);
                $item->loadByProduct($product);

                if (!$item->getId()) {
                    $item->addProductData($product);
                    $item->save();
                }
                $this->_eventManager->dispatch('catalog_product_compare_add_product', ['product' => $product]);
            }

            $this->_objectManager->get(Compare::class)->calculate();
        }
        return true;
    }

    /**
     * @param $item
     * @param $customerId
     * @param $visitorId
     * @return $this
     */
    protected function _addVisitorToItem($item, $customerId, $visitorId)
    {
        $item->addVisitorId($visitorId);
        if ($customerId) {
            $item->setCustomerId($customerId);
        }

        return $this;
    }

}
