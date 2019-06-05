<?php
/**
 * @author Tigren Solutions <info@tigren.com>
 * @copyright Copyright (c) 2019 Tigren Solutions <https://www.tigren.com>. All rights reserved.
 * @license Open Software License ("OSL") v. 3.0
 */
declare(strict_types=1);

namespace Tigren\WishlistGraphQl\Model\Resolver\Mutation;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Helper\Product;
use Magento\Catalog\Helper\Product\Configuration;
use Magento\Catalog\Model\Product\Exception as ProductException;
use Magento\Checkout\Model\Cart;
use Magento\CustomerGraphQl\Model\Customer\CheckCustomerAccount;
use Magento\Framework\Event\ManagerInterface as EventManager;
use Magento\Framework\Exception\NotFoundException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Wishlist\Model\Item;
use Magento\Wishlist\Model\Item\OptionFactory;
use Magento\Wishlist\Model\ItemFactory;
use Magento\Wishlist\Model\ResourceModel\Item\Option\Collection;
use Magento\Wishlist\Model\WishlistFactory;

/**
 * Class AddToCart
 * @package Tigren\WishlistGraphQl\Model\Resolver\Wishlist
 */
class AddToCart implements ResolverInterface
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
     * @var CheckCustomerAccount
     */
    private $checkCustomerAccount;
    /**
     * @var OptionFactory
     */
    private $optionFactory;

    /**
     * AddToCart constructor.
     * @param CheckCustomerAccount $checkCustomerAccount
     * @param ProductRepositoryInterface $productRepository
     * @param WishlistFactory $wishlistFactory
     * @param EventManager $eventManager
     * @param ItemFactory $itemFactory
     * @param Cart $cart
     * @param OptionFactory $optionFactory
     * @param Product $productHelper
     * @param Configuration $productConfig
     */
    public function __construct(
        CheckCustomerAccount $checkCustomerAccount,
        ProductRepositoryInterface $productRepository,
        WishlistFactory $wishlistFactory,
        EventManager $eventManager,
        ItemFactory $itemFactory,
        Cart $cart,
        OptionFactory $optionFactory,
        Product $productHelper,
        Configuration $productConfig
    ) {
        $this->checkCustomerAccount = $checkCustomerAccount;
        $this->productRepository = $productRepository;
        $this->wishlistFactory = $wishlistFactory;
        $this->eventManager = $eventManager;
        $this->itemFactory = $itemFactory;
        $this->cart = $cart;
        $this->optionFactory = $optionFactory;
        $this->productHelper = $productHelper;
        $this->productConfig = $productConfig;
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

        if (!isset($args['qty'])) {
            throw new GraphQlInputException(__('Specify the "qty" value.'));
        }

        $currentUserId = $context->getUserId();
        $currentUserType = $context->getUserType();
        $this->checkCustomerAccount->execute($currentUserId, $currentUserType);
        $currentUserId = (int)$currentUserId;

        $itemId = $args['item'];
        /* @var $item Item */
        $item = $this->itemFactory->create()->load($itemId);

        if (!$item->getId()) {
            throw new GraphQlInputException(__('This item id is not exist.'));
        }

        $wishlist = $this->wishlistFactory->create()->loadByCustomerId($currentUserId);
        if (!$wishlist) {
            throw new NotFoundException(__('Page not found.'));
        }

        $item->setQty($args['qty']);

        try {
            /** @var Collection $options */
            $options = $this->optionFactory->create()->getCollection()->addItemFilter([$itemId]);
            $item->setOptions($options->getOptionsByItem($itemId));
            $params = [
                'item' => $itemId,
                'qty' => $args['qty']
            ];
            $buyRequest = $this->productHelper->addParamsToBuyRequest(
                $params,
                ['current_config' => $item->getBuyRequest()]
            );
            $item->mergeBuyRequest($buyRequest);
            $item->addToCart($this->cart, true);
            $this->cart->save()->getQuote()->collectTotals();
            $wishlist->save();
        } catch (ProductException $e) {
            throw new ProductException(__('This product(s) is out of stock.'));
        }

        return true;
    }

}
