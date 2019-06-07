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
use Magento\Checkout\Model\Cart;
use Magento\Customer\Model\Customer;
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
 * Class RemoveItem
 * @package Tigren\CompareGraphQl\Model\Resolver\Mutation
 */
class RemoveItem implements ResolverInterface
{
    /**
     * @var ProductRepositoryInterface
     */
    protected $productRepository;

    /**
     * @var EventManager
     */
    protected $_eventManager;

    /**
     * @var StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * @var ObjectManagerInterface
     */
    protected $_objectManager;

    /**
     * Compare item factory
     *
     * @var ItemFactory
     */
    protected $_compareItemFactory;

    /**
     * @var Session
     */
    protected $_customerSession;

    /**
     * @var Data
     */
    protected $_helper;

    /**
     * RemoveItem constructor.
     * @param ProductRepositoryInterface $productRepository
     * @param EventManager $eventManager
     * @param Cart $cart
     * @param StoreManagerInterface $storeManager
     * @param ItemFactory $compareItemFactory
     * @param Session $session
     * @param Data $helper
     * @param ObjectManagerInterface $objectManager
     */
    public function __construct(
        ProductRepositoryInterface $productRepository,
        EventManager $eventManager,
        StoreManagerInterface $storeManager,
        ItemFactory $compareItemFactory,
        Session $session,
        Data $helper,
        ObjectManagerInterface $objectManager
    ) {
        $this->productRepository = $productRepository;
        $this->eventManager = $eventManager;
        $this->_helper = $helper;
        $this->_storeManager = $storeManager;
        $this->_compareItemFactory = $compareItemFactory;
        $this->_customerSession = $session;
        $this->_objectManager = $objectManager;
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
        if (!isset($args['item'])) {
            throw new GraphQlInputException(__('Specify the "item" value.'));
        }

        $productId = (int)$args['item'];
        if ($productId) {
            $storeId = $this->_storeManager->getStore()->getId();
            try {
                $product = $this->productRepository->getById($productId, false, $storeId);
            } catch (NoSuchEntityException $e) {
                $product = null;
            }

            if ($product) {
                /** @var $item Item */
                $item = $this->_compareItemFactory->create();
                $customerId = null;
                $visitorId = null;
                if (!isset($value['model'])) {
                    $sessionId = $this->_customerSession->getSessionId();
                    $visitorId = $this->_helper->getVisitorId($sessionId);
                } else {
                    /** @var Customer $customer */
                    $customer = $value['model'];
                    $customerId = $customer->getId();
                }
                if ($customerId) {
                    $item->setCustomerId($customerId);
                } else {
                    $item->addVisitorId($visitorId);
                }

                $item->loadByProduct($product);
                /** @var $helper Compare */
                $helper = $this->_objectManager->get(Compare::class);
                if ($item->getId()) {
                    $item->delete();
                    $helper->calculate();
                }
            }
        }

        return true;
    }

}
