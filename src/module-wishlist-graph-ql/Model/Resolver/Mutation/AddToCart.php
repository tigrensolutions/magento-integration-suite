<?php
/**
 * @author    Tigren Solutions <info@tigren.com>
 * @copyright Copyright (c) 2019 Tigren Solutions <https://www.tigren.com>. All rights reserved.
 * @license   Open Software License ("OSL") v. 3.0
 */
declare(strict_types=1);

namespace Tigren\WishlistGraphQl\Model\Resolver\Mutation;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Helper\Product;
use Magento\Catalog\Helper\Product\Configuration;
use Magento\Catalog\Model\Product\Exception as ProductException;
use Magento\Checkout\Model\Cart;
use Magento\CustomerGraphQl\Model\Customer\GetCustomer;
use Magento\Framework\Api\DataObjectHelper;
use Magento\Framework\Event\ManagerInterface as EventManager;
use Magento\Framework\Exception\NotFoundException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlAuthorizationException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\GraphQl\Model\Query\ContextInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\CartItemInterface;
use Magento\Quote\Model\Quote\ItemFactory as QuoteItem;
use Magento\Quote\Model\QuoteFactory;
use Magento\Wishlist\Model\Item;
use Magento\Wishlist\Model\Item\OptionFactory;
use Magento\Wishlist\Model\ItemFactory;
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
     * @var QuoteFactory
     */
    protected $quoteFactory;

    /**
     * @var QuoteItem
     */
    protected $quoteItemFactory;

    /**
     *
     * @var CartRepositoryInterface
     */
    protected $quoteRepository;

    /**
     * @var GetCustomer
     */
    private $getCustomer;

    /**
     * @var OptionFactory
     */
    private $optionFactory;

    /**
     * @var DataObjectHelper
     */
    private $dataObjectHelper;

    /**
     * AddToCart constructor.
     * @param GetCustomer $getCustomer
     * @param ProductRepositoryInterface $productRepository
     * @param WishlistFactory $wishlistFactory
     * @param EventManager $eventManager
     * @param ItemFactory $itemFactory
     * @param Cart $cart
     * @param OptionFactory $optionFactory
     * @param Product $productHelper
     * @param Configuration $productConfig
     * @param DataObjectHelper $dataObjectHelper
     * @param QuoteFactory $quoteFactory
     * @param QuoteItem $quoteItemFactory
     * @param CartRepositoryInterface $quoteRepository
     */
    public function __construct(
        GetCustomer $getCustomer,
        ProductRepositoryInterface $productRepository,
        WishlistFactory $wishlistFactory,
        EventManager $eventManager,
        ItemFactory $itemFactory,
        Cart $cart,
        OptionFactory $optionFactory,
        Product $productHelper,
        Configuration $productConfig,
        DataObjectHelper $dataObjectHelper,
        QuoteFactory $quoteFactory,
        QuoteItem $quoteItemFactory,
        CartRepositoryInterface $quoteRepository
    ) {
        $this->getCustomer = $getCustomer;
        $this->productRepository = $productRepository;
        $this->wishlistFactory = $wishlistFactory;
        $this->eventManager = $eventManager;
        $this->itemFactory = $itemFactory;
        $this->cart = $cart;
        $this->optionFactory = $optionFactory;
        $this->productHelper = $productHelper;
        $this->productConfig = $productConfig;
        $this->dataObjectHelper = $dataObjectHelper;
        $this->quoteFactory = $quoteFactory;
        $this->quoteItemFactory = $quoteItemFactory;
        $this->quoteRepository = $quoteRepository;
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
        /** @var ContextInterface $context */
        if (false === $context->getExtensionAttributes()->getIsCustomer()) {
            throw new GraphQlAuthorizationException(__('The current customer isn\'t authorized.'));
        }
        if (!isset($args['item'])) {
            throw new GraphQlInputException(__('Specify the "item" value.'));
        }

        if (!isset($args['qty'])) {
            throw new GraphQlInputException(__('Specify the "qty" value.'));
        }

        $customer = $this->getCustomer->execute($context);
        $itemId = $args['item'];
        /* @var $item Item */
        $item = $this->itemFactory->create()->load($itemId);
        $product = $item->getProduct();

        if (!$item->getId()) {
            throw new GraphQlInputException(__('This item id is not exist.'));
        }

        $wishlist = $this->wishlistFactory->create()->loadByCustomerId($customer->getId());
        if (!$wishlist) {
            throw new NotFoundException(__('Page not found.'));
        }
        $quote = $this->quoteFactory->create()->loadByCustomer($customer->getId());
        $cartItem = $this->quoteItemFactory->create();
        $itemData = [
            'qty' => (int)$args['qty'],
            'sku' => $product->getSku(),
            'quote_id' => $quote->getId()
        ];
        try {
            $this->dataObjectHelper->populateWithArray(
                $cartItem,
                $itemData,
                CartItemInterface::class
            );
            $quoteItems = $quote->getItems();
            $quoteItems[] = $cartItem;
            $quote->setItems($quoteItems);
            $this->quoteRepository->save($quote);
            $quote->collectTotals();
            $item->delete();
            $wishlist->save();
        } catch (ProductException $e) {
            throw new ProductException(__('This product(s) is out of stock.'));
        }
        return true;
    }
}
