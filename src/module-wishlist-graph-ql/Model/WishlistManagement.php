<?php
/**
 * @author Tigren Solutions <info@tigren.com>
 * @copyright Copyright (c) 2019 Tigren Solutions <https://www.tigren.com>. All rights reserved.
 * @license Open Software License ("OSL") v. 3.0
 */

namespace Tigren\WishlistGraphQl\Model;

use Exception;
use Magento\Authorization\Model\UserContextInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Helper\Product;
use Magento\Catalog\Helper\Product\Configuration;
use Magento\Checkout\Model\Cart;
use Magento\Customer\Model\Session;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\ObjectManagerInterface;
use Magento\Wishlist\Controller\WishlistProviderInterface;
use Magento\Wishlist\CustomerData\Wishlist;
use Magento\Wishlist\Helper\Data;
use Magento\Wishlist\Model\Item\OptionFactory;
use Magento\Wishlist\Model\ItemFactory;
use Magento\Wishlist\Model\LocaleQuantityProcessor;
use Magento\Wishlist\Model\WishlistFactory;
use Tigren\WishlistGraphQl\Api\WishlistManagementInterface;

class WishlistManagement implements WishlistManagementInterface
{
    /**
     * @var ProductRepositoryInterface
     */
    protected $productRepository;

    /**
     * @var WishlistProviderInterface
     */
    protected $wishlistProvider;

    /**
     * @var Session
     */
    protected $_customerSession;

    /**
     * @var ManagerInterface
     */
    protected $_eventManager;

    /**
     * @var ObjectManagerInterface
     */
    protected $_objectManager;

    /**
     * @var Wishlist
     */
    protected $customerWishlist;

    /**
     * @var Data
     */
    protected $wishlistHelper;

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
     * @var LocaleQuantityProcessor
     */
    protected $quantityProcessor;
    /**
     * @var WishlistFactory
     */
    protected $wishlistFactory;
    /**
     * @var OptionFactory
     */
    private $optionFactory;
    /**
     * @var UserContextInterface
     */
    private $userContext;
    /**
     * @var \Magento\Wishlist\Model\ItemCarrier
     */
    protected $itemCarrier;

    public function __construct(
        ProductRepositoryInterface $productRepository,
        WishlistProviderInterface $wishlistProvider,
        Session $customerSession,
        ManagerInterface $eventManager,
        ObjectManagerInterface $objectManager,
        Wishlist $customerWishlist,
        Data $wishlistHelper,
        ItemFactory $itemFactory,
        Cart $cart,
        OptionFactory $optionFactory,
        Product $productHelper,
        Configuration $productConfig,
        LocaleQuantityProcessor $quantityProcessor,
        UserContextInterface $userContext,
        \Magento\Wishlist\Model\ItemCarrier $itemCarrier,
        WishlistFactory $wishlistFactory
    ) {
        $this->productRepository = $productRepository;
        $this->wishlistProvider = $wishlistProvider;
        $this->_customerSession = $customerSession;
        $this->_eventManager = $eventManager;
        $this->_objectManager = $objectManager;
        $this->customerWishlist = $customerWishlist;
        $this->wishlistHelper = $wishlistHelper;
        $this->itemFactory = $itemFactory;
        $this->cart = $cart;
        $this->optionFactory = $optionFactory;
        $this->productHelper = $productHelper;
        $this->productConfig = $productConfig;
        $this->quantityProcessor = $quantityProcessor;
        $this->userContext = $userContext;
        $this->wishlistFactory = $wishlistFactory;
        $this->itemCarrier = $itemCarrier;
    }

    /**
     * {@inheritdoc}
     */
    public function update($wishlistUpdate)
    {
        $customerId = $this->userContext->getUserId();
        $wishlist = $this->wishlistFactory->create()->loadByCustomerId($customerId, true);
        if (!$wishlist) {
            throw new Exception (__('We can\'t specify wishlist'));
        }

        foreach ($wishlistUpdate as $itemId => $itemData) {
            $item = $this->_objectManager->create(\Magento\Wishlist\Model\Item::class)->load($itemId);
            $description = $itemData['des'] ?? '';
            $qty = null;
            if (isset($itemData['qty'])) {
                $qty = $this->quantityProcessor->process($itemData['qty']);
            }
            if ($qty == 0) {
                try {
                    $item->delete();
                } catch (Exception $e) {
                    throw new Exception(__('We can\'t delete item from Wish List right now.'));
                }
            }

            // Check that we need to save
            if ($item->getDescription() == $description && $item->getQty() == $qty) {
                continue;
            }
            $item->setDescription($description)->setQty($qty)->save();

        }
        return true;
    }

}