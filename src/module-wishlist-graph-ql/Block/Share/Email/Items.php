<?php
/**
 * @author    Tigren Solutions <info@tigren.com>
 * @copyright Copyright (c) 2019 Tigren Solutions <https://www.tigren.com>. All rights reserved.
 * @license   Open Software License ("OSL") v. 3.0
 */

/**
 * Wishlist block customer items
 *
 * @author     Magento Core Team <core@magentocommerce.com>
 */

namespace Tigren\WishlistGraphQl\Block\Share\Email;

use Magento\Catalog\Model\Product;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\UrlInterface;
use Magento\Wishlist\Block\AbstractBlock;
use Magento\Wishlist\Model\Item;
use Magento\Wishlist\Model\ResourceModel\Item\Collection;

/**
 * @api
 * @since 100.0.2
 */
class Items extends AbstractBlock
{
    /**
     * @var string
     */
    protected $_template = 'items.phtml';

    /**
     * Retrieve Product View URL
     *
     * @param Product $product
     * @param array $additional
     * @return string
     */
    public function getProductUrl($product, $additional = [])
    {
        $additional['_scope_to_url'] = false;
        return parent::getProductUrl($product, $additional);
    }

    /**
     * @return int
     */
    public function getWishlistItemsCount()
    {
        $items = $this->getWishlistItems();
        return $items->count();
    }

    /**
     * @return Collection|mixed
     */
    public function getWishlistItems()
    {
        return $this->getData('items');
    }

    /**
     * @param Product $product
     * @return string
     * @throws NoSuchEntityException
     */
    public function getImageUrl($product)
    {
        $mediaUrl = $this->_storeManager->getStore()->getBaseUrl(UrlInterface::URL_TYPE_MEDIA);
        return $mediaUrl . 'catalog/product' . $product->getImage();
    }

    /**
     * Check whether wishlist item has description
     *
     * @param Item $item
     * @return bool
     */
    public function hasDescription($item)
    {
        $hasDescription = parent::hasDescription($item);
        if ($hasDescription) {
            return $item->getDescription() !== $this->_wishlistHelper->defaultCommentString();
        }
        return $hasDescription;
    }
}
