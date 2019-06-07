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
use Magento\CustomerGraphQl\Model\Customer\CheckCustomerAccount;
use Magento\Framework\DataObject;
use Magento\Framework\Event\ManagerInterface as EventManager;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\NotFoundException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Wishlist\Model\WishlistFactory;

/**
 * Class AddToWishlist
 * @package Tigren\WishlistGraphQl\Model\Resolver\Wishlist
 */
class AddToWishlist implements ResolverInterface
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
     * @var CheckCustomerAccount
     */
    private $checkCustomerAccount;

    /**
     * AddToWishlist constructor.
     * @param CheckCustomerAccount $checkCustomerAccount
     * @param ProductRepositoryInterface $productRepository
     * @param WishlistFactory $wishlistFactory
     * @param EventManager $eventManager
     */
    public function __construct(
        CheckCustomerAccount $checkCustomerAccount,
        ProductRepositoryInterface $productRepository,
        WishlistFactory $wishlistFactory,
        EventManager $eventManager
    ) {
        $this->checkCustomerAccount = $checkCustomerAccount;
        $this->productRepository = $productRepository;
        $this->wishlistFactory = $wishlistFactory;
        $this->eventManager = $eventManager;
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

        $currentUserId = $context->getUserId();
        $currentUserType = $context->getUserType();
        $this->checkCustomerAccount->execute($currentUserId, $currentUserType);
        $currentUserId = (int)$currentUserId;

        $wishlist = $this->wishlistFactory->create()->loadByCustomerId($currentUserId, true);

        if (!$wishlist->getId()) {
            throw new NotFoundException(__('Page not found.'));
        }
        $requestParams = [];
        $requestParams['product'] = $args['product'];
        try {
            $product = $this->productRepository->getById($requestParams['product']);
        } catch (NoSuchEntityException $e) {
            $product = null;
        }

        if (!$product || !$product->isVisibleInCatalog()) {
            throw new NoSuchEntityException(
                __('We can\'t specify a product.')
            );
        }
        try {
            $buyRequest = new DataObject($requestParams);
            $result = $wishlist->addNewItem($product, $buyRequest);
            if (is_string($result)) {
                throw new LocalizedException(__($result));
            }
            $wishlist->save();
            $this->eventManager->dispatch(
                'wishlist_add_product',
                ['wishlist' => $wishlist, 'product' => $product, 'item' => $result]
            );
        } catch (LocalizedException $e) {
            throw new LocalizedException(
                __('We can\'t add the item to Wish List right now: %1.', $e->getMessage())
            );
        } catch (Exception $e) {
            throw new Exception(
                $e->getMessage()
            );
        }
        return true;
    }

}
