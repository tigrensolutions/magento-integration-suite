<?php
/**
 * @author Tigren Solutions <info@tigren.com>
 * @copyright Copyright (c) 2019 Tigren Solutions <https://www.tigren.com>. All rights reserved.
 * @license Open Software License ("OSL") v. 3.0
 */
declare(strict_types=1);

namespace Tigren\WishlistGraphQl\Model\Resolver\Mutation;

use Exception;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Helper\Product;
use Magento\Catalog\Helper\Product\Configuration;
use Magento\Checkout\Model\Cart;
use Magento\CustomerGraphQl\Model\Customer\GetCustomer;
use Magento\Framework\Event\ManagerInterface as EventManager;
use Magento\Framework\Exception\NotFoundException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Wishlist\Model\Item;
use Magento\Wishlist\Model\Item\OptionFactory;
use Magento\Wishlist\Model\ItemFactory;
use Magento\Wishlist\Model\WishlistFactory;

/**
 * Class RemoveItem
 * @package Tigren\WishlistGraphQl\Model\Resolver\Wishlist
 */
class RemoveItem implements ResolverInterface
{
    /**
     * @var ProductRepositoryInterface
     */
    protected $productRepository;

    /**
     * @var WishlistFactory
     */
    protected $wishlistFactory;

    /**
     * @var EventManager
     */
    protected $_eventManager;

    /**
     * @var ItemFactory
     */
    protected $itemFactory;
    /**
     * @var Cart
     */
    protected $cart;
    /**
     * @var Product
     */
    protected $productHelper;
    /**
     * @var Configuration
     */
    protected $productConfig;
    /**
     * @var GetCustomer
     */
    protected $getCustomer;
    /**
     * @var OptionFactory
     */
    private $optionFactory;

    public function __construct(
        ProductRepositoryInterface $productRepository,
        WishlistFactory $wishlistFactory,
        EventManager $eventManager,
        ItemFactory $itemFactory,
        Cart $cart,
        OptionFactory $optionFactory,
        Product $productHelper,
        Configuration $productConfig,
        GetCustomer $getCustomer
    ) {
        $this->productRepository = $productRepository;
        $this->wishlistFactory = $wishlistFactory;
        $this->eventManager = $eventManager;
        $this->itemFactory = $itemFactory;
        $this->cart = $cart;
        $this->optionFactory = $optionFactory;
        $this->productHelper = $productHelper;
        $this->productConfig = $productConfig;
        $this->getCustomer = $getCustomer;
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

        $currentUserId = $context->getUserId();
        $currentUserType = $context->getUserType();
        $customer = $this->getCustomer->execute($currentUserId, $currentUserType);
        $currentUserId = (int)$currentUserId;

        $itemId = $args['item'];
        /* @var $item Item */
        $item = $this->itemFactory->create()->load($itemId);

        if (!$item->getId()) {
            throw new GraphQlInputException(__('This item id is not exist.'));
        }

        $wishlist = $this->wishlistFactory->create()->loadByCustomerId($customer->getId());
        if (!$wishlist) {
            throw new NotFoundException(__('Page not found.'));
        }

        try {
            $item->delete();
            $wishlist->save();
        } catch (Exception $e) {
            throw new Exception(__('We can\'t delete the item from the Wish List right now.'));
        }

        return true;
    }

}
