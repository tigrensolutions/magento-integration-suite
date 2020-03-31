<?php
/**
 * @author    Tigren Solutions <info@tigren.com>
 * @copyright Copyright (c) 2019 Tigren Solutions <https://www.tigren.com>. All rights reserved.
 * @license   Open Software License ("OSL") v. 3.0
 */
declare(strict_types=1);

namespace Tigren\WishlistGraphQl\Model\Resolver\Mutation;

use Exception;
use Magento\Authorization\Model\UserContextInterface;
use Magento\CustomerGraphQl\Model\Customer\GetCustomer;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlAuthorizationException;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Framework\Message\ManagerInterface;
use Magento\GraphQl\Model\Query\ContextInterface;
use Magento\Quote\Model\QuoteFactory;
use Magento\Wishlist\Model\ItemCarrier;
use Magento\Wishlist\Model\WishlistFactory;
use Magento\Checkout\Model\Session;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Quote\Model\QuoteIdMaskFactory;

/**
 * Class AddToWishlist
 * @package Tigren\WishlistGraphQl\Model\Resolver\Wishlist
 */
class SharedAllCart implements ResolverInterface
{
    /**
     * @var WishlistFactory
     */
    protected $wishlistFactory;

    /**
     * @var ItemCarrier
     */
    protected $itemCarrier;

    /**
     * @var ManagerInterface
     */
    protected $messageManager;
    /**
     * @var QuoteFactory
     */
    protected $quoteFactory;
    /**
     * @var CustomerSession
     */
    protected $customerSession;
    /**
     * @var UserContextInterface
     */
    private $userContext;
    /**
     * @var GetCustomer
     */
    private $getCustomer;
    /**
     * @var Session
     */
    private $checkoutSession;
    /**
     * @var QuoteIdMaskFactory
     */
    private $quoteIdMaskFactory;

    /**
     * AllCart constructor.
     * @param WishlistFactory $wishlistFactory
     * @param UserContextInterface $userContext
     * @param ItemCarrier $itemCarrier
     * @param ManagerInterface $messageManager
     * @param GetCustomer $getCustomer
     * @param QuoteFactory $quoteFactory
     * @param Session $checkoutSession
     * @param QuoteIdMaskFactory $quoteIdMaskFactory
     * @param CustomerSession $customerSession
     */
    public function __construct(
        WishlistFactory $wishlistFactory,
        UserContextInterface $userContext,
        ItemCarrier $itemCarrier,
        ManagerInterface $messageManager,
        GetCustomer $getCustomer,
        QuoteFactory $quoteFactory,
        Session $checkoutSession,
        QuoteIdMaskFactory $quoteIdMaskFactory,
        CustomerSession $customerSession
    ) {
        $this->wishlistFactory = $wishlistFactory;
        $this->userContext = $userContext;
        $this->itemCarrier = $itemCarrier;
        $this->messageManager = $messageManager;
        $this->getCustomer = $getCustomer;
        $this->quoteFactory = $quoteFactory;
        $this->checkoutSession = $checkoutSession;
        $this->customerSession = $customerSession;
        $this->quoteIdMaskFactory = $quoteIdMaskFactory;
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
        if (!isset($args['cart_id'])) {
            throw new GraphQlInputException(__('Specify the "cart_id" value.'));
        }
        if (!isset($args['code'])) {
            throw new GraphQlInputException(__('Specify the "code" value.'));
        }
        if (!isset($args['items'])) {
            throw new GraphQlInputException(__('Specify the "items" value.'));
        }
        $customerId = $context->getUserId() ?: null;

        $quote = $this->quoteFactory->create();

        if ($customerId) {
            /** @var $quoteIdMask QuoteIdMask */
            $quoteIdMask = $this->quoteIdMaskFactory->create()->load($args['cart_id'], 'masked_id');
            $quote->load($quoteIdMask->getQuoteId());
        } else {
            $quote->load($args['cart_id']);
        }
        $this->checkoutSession->setQuoteId($quote->getId());
        $this->customerSession->setCustomerId($customerId);
        $wishlist = $this->wishlistFactory->create()->loadByCode($args['code']);
        $wishlistData = \GuzzleHttp\json_decode($args['items'], true);

        if (!$wishlist) {
            throw new Exception(__('We can\'t specify wishlist'));
        }
        $qty = [];
        foreach ($wishlistData as $key => $item) {
            $qty[$item] = 1;
        }
        $this->messageManager->getMessages()->clear();
        $this->itemCarrier->moveAllToCart($wishlist, $qty);
        $messages = [];
        foreach ($this->messageManager->getMessages()->getItems() as $message) {
            $messages[] = [
                'type' => $message->getType(),
                'text' => $message->getText()
            ];
        }
        return $messages;
    }
}
