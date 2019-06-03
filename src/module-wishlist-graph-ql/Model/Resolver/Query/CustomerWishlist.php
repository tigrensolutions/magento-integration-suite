<?php
/**
 * @author Tigren Solutions <info@tigren.com>
 * @copyright Copyright (c) 2019 Tigren Solutions <https://www.tigren.com>. All rights reserved.
 * @license Open Software License ("OSL") v. 3.0
 */
declare(strict_types=1);

namespace Tigren\WishlistGraphQl\Model\Resolver;

use Magento\Catalog\Helper\Product\Configuration as ProductConfig;
use Magento\Customer\Model\Customer;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Wishlist\Helper\Data as WishlistHelper;
use Magento\Wishlist\Model\Item;
use Magento\Wishlist\Model\Item\Option;
use Magento\Wishlist\Model\WishlistFactory;

/**
 * Class CustomerWishlist
 * @package Tigren\WishlistGraphQl\Model\Resolver
 */
class CustomerWishlist implements ResolverInterface
{
    /**
     * Price type final.
     */
    const PRICE_CODE = 'final_price';
    /**
     * @var ProductConfig
     */
    protected $productConfig;
    /**
     * @var WishlistHelper
     */
    protected $wishlistHelper;
    /**
     * @var WishlistFactory
     */
    private $wishlistFactory;

    /**
     * @param WishlistFactory $wishlistFactory
     * @param ProductConfig $productConfig
     * @param WishlistHelper $wishlistHelper
     */
    public function __construct(
        WishlistFactory $wishlistFactory,
        ProductConfig $productConfig,
        WishlistHelper $wishlistHelper
    ) {
        $this->wishlistFactory = $wishlistFactory;
        $this->productConfig = $productConfig;
        $this->wishlistHelper = $wishlistHelper;
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
        if (!isset($value['id'])) {
            throw new LocalizedException(__('"id" value should be specified'));
        }
        /** @var Customer $customer */
        $customerId = $value['id'];

        $wishlistData = [];
        $wishlistCollection = $this->wishlistFactory->create()->loadByCustomerId($customerId)->getItemCollection();
        foreach ($wishlistCollection as $item) {
            $wishlistData[] = $this->getItemData($item);
        }
        return $wishlistData ?: null;
    }

    /**
     * @param Item $wishlistItem
     * @return array
     * @throws LocalizedException
     */
    private function getItemData(Item $wishlistItem)
    {
        $product = $wishlistItem->getProduct();

        return [
            'id' => $wishlistItem->getId(),
            'currency' => 'USD', // hardcode temporarily
            'small_image' => $product->getData('small_image'),
            'url_key' => $product->getUrlKey(),
            'name' => $product->getName(),
            'qty' => (int)$wishlistItem->getQty(),
            'product_id' => $product->getId(),
            'price' => $product->getPrice(),
            'special_price' => $product->getSpecialPrice(),
            'final_price' => $this->getValue($product),
            'type_id' => $product->getTypeId(),
            'description' => $wishlistItem->getDescription() ?: '',
            'is_available' => $product->load($product->getId())->isAvailable()
        ];
    }

    /**
     * @inheritdoc
     */
    private function getValue($product)
    {
        /** @var Option $customOption */
        $customOption = $product->getCustomOption('simple_product');
        $product = $customOption ? $customOption->getProduct() : $product;
        $price = $product->getPriceInfo()->getPrice(self::PRICE_CODE)->getValue();

        return $price;
    }
}
