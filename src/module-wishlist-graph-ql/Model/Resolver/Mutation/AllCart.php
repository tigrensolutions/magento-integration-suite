<?php
/**
 * @author Tigren Solutions <info@tigren.com>
 * @copyright Copyright (c) 2019 Tigren Solutions <https://www.tigren.com>. All rights reserved.
 * @license Open Software License ("OSL") v. 3.0
 */
declare(strict_types=1);

namespace Tigren\WishlistGraphQl\Model\Resolver\Mutation;

use Exception;
use Magento\Authorization\Model\UserContextInterface;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlAuthorizationException;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\GraphQl\Model\Query\ContextInterface;
use Magento\Wishlist\Model\WishlistFactory;

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
     * @var \Magento\Wishlist\Model\ItemCarrier
     */
    protected $itemCarrier;
    /**
     * @var \Magento\Framework\Message\ManagerInterface
     */
    protected $messageManager;
    /**
     * @var UserContextInterface
     */
    private $userContext;

    /**
     * AllCart constructor.
     * @param WishlistFactory $wishlistFactory
     * @param UserContextInterface $userContext
     * @param \Magento\Wishlist\Model\ItemCarrier $itemCarrier
     * @param \Magento\Framework\Message\ManagerInterface $messageManager
     */
    public function __construct(
        WishlistFactory $wishlistFactory,
        UserContextInterface $userContext,
        \Magento\Wishlist\Model\ItemCarrier $itemCarrier,
        \Magento\Framework\Message\ManagerInterface $messageManager
    ) {
        $this->wishlistFactory = $wishlistFactory;
        $this->userContext = $userContext;
        $this->itemCarrier = $itemCarrier;
        $this->messageManager = $messageManager;
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

        $wishlistData = \GuzzleHttp\json_decode($args['items'], true);
        $customerId = $this->userContext->getUserId();
        $wishlist = $this->wishlistFactory->create()->loadByCustomerId($customerId, true);

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
