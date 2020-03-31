<?php
/**
 * @author    Tigren Solutions <info@tigren.com>
 * @copyright Copyright (c) 2019 Tigren Solutions <https://www.tigren.com>. All rights reserved.
 * @license   Open Software License ("OSL") v. 3.0
 */
declare(strict_types=1);

namespace Tigren\WishlistGraphQl\Model\Resolver\Mutation;

use Exception;
use Magento\CustomerGraphQl\Model\Customer\GetCustomer;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\NotFoundException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlAuthorizationException;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Exception\GraphQlNoSuchEntityException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\GraphQl\Model\Query\ContextInterface;
use Magento\Quote\Model\Quote;
use Magento\Wishlist\Model\WishlistFactory;
use Magento\Checkout\Model\Cart as CheckoutCart;
use Magento\Wishlist\Helper\Data as WishlistHelper;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Model\MaskedQuoteIdToQuoteIdInterface;

/**
 * Class AddToWishlist
 * @package Tigren\WishlistGraphQl\Model\Resolver\Wishlist
 */
class AddToWishlistFromCart implements ResolverInterface
{

    /**
     * @var WishlistFactory
     */
    protected $wishlistFactory;

    /**
     * @var GetCustomer
     */
    private $getCustomer;

    /**
     * @var CheckoutCart
     */
    protected $cart;

    /**
     * @var WishlistHelper
     */
    protected $wishlistHelper;

    /**
     * @var CartRepositoryInterface
     */
    private $cartRepository;

    /**
     * @var MaskedQuoteIdToQuoteIdInterface
     */
    private $maskedQuoteIdToQuoteId;

    /**
     * AddToWishlist constructor.
     * @param GetCustomer $getCustomer
     * @param WishlistFactory $wishlistFactory
     */
    public function __construct(
        GetCustomer $getCustomer,
        WishlistFactory $wishlistFactory,
        WishlistHelper $wishlistHelper,
        CheckoutCart $cart,
        MaskedQuoteIdToQuoteIdInterface $maskedQuoteIdToQuoteId,
        CartRepositoryInterface $cartRepository
    ) {
        $this->wishlistHelper = $wishlistHelper;
        $this->cart = $cart;
        $this->getCustomer = $getCustomer;
        $this->wishlistFactory = $wishlistFactory;
        $this->maskedQuoteIdToQuoteId = $maskedQuoteIdToQuoteId;
        $this->cartRepository = $cartRepository;
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
            throw new GraphQlInputException(__('Specify the "product" value.'));
        }

        if (!isset($args['cartId'])) {
            throw new GraphQlInputException(__('Specify the "product" value.'));
        }

        $customer = $this->getCustomer->execute($context);
        $wishlist = $this->wishlistFactory->create()->loadByCustomerId($customer->getId(), true);
        try {
            $cartId = $this->maskedQuoteIdToQuoteId->execute($args['cartId']);
        } catch (NoSuchEntityException $exception) {
            throw new GraphQlNoSuchEntityException(
                __('Could not find a cart with ID "%masked_cart_id"', ['masked_cart_id' => $args['cartId']])
            );
        }

        try {
            /** @var Quote $cart */
            $cart = $this->cartRepository->get($cartId);
        } catch (NoSuchEntityException $e) {
            throw new GraphQlNoSuchEntityException(
                __('Could not find a cart with ID "%masked_cart_id"', ['masked_cart_id' => $args['cartId']])
            );
        }
        $itemId = (int)$args['item'];
        $item = $cart->getItemById($itemId);
        if (!$item) {
            throw new LocalizedException(
                __("The cart item doesn't exist.")
            );
        }

        $productId = $item->getProductId();
        $buyRequest = $item->getBuyRequest();
        $wishlist->addNewItem($productId, $buyRequest);

        $cart->removeItem($itemId);
        $cart->save();

        $this->wishlistHelper->calculate();
        $wishlist->save();
        return true;
    }
}
