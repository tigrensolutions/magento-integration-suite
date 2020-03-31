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

/**
 * Class AddToWishlist
 * @package Tigren\WishlistGraphQl\Model\Resolver\Wishlist
 */
class AllCart implements ResolverInterface
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
     * AllCart constructor.
     * @param WishlistFactory $wishlistFactory
     * @param UserContextInterface $userContext
     * @param ItemCarrier $itemCarrier
     * @param ManagerInterface $messageManager
     * @param GetCustomer $getCustomer
     * @param QuoteFactory $quoteFactory
     * @param Session $checkoutSession
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
        if (!isset($args['items'])) {
            throw new GraphQlInputException(__('Specify the "items" value.'));
        }
        $customer = $this->getCustomer->execute($context);
        $this->customerSession->setCustomerId($customer->getId());
        $wishlistData = \GuzzleHttp\json_decode($args['items'], true);
        $wishlist = $this->wishlistFactory->create()->loadByCustomerId($customer->getId(), true);
        if (!$wishlist) {
            throw new Exception(__('We can\'t specify wishlist'));
        }
        $qty = [];
        foreach ($wishlistData as $itemId => $item) {
            $qty[$itemId] = $item['qty'];
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
