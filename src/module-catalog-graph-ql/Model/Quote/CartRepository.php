<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Tigren\CatalogGraphQl\Model\Quote;

use Magento\Framework\Exception\LocalizedException;
use Tigren\CatalogGraphQl\Api\Quote\CartRepositoryInterface;
use Magento\Quote\Model\QuoteFactory;

/**
 * Cart Item repository class for guest carts.
 */
class CartRepository implements CartRepositoryInterface
{
    /**
     * @var QuoteFactory
     */
    protected $quoteFactory;

    /**
     * Constructs a read service object.
     *
     * @param QuoteFactory $quoteFactory
     */
    public function __construct(
        QuoteFactory $quoteFactory
    ) {
        $this->quoteFactory = $quoteFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function save($items,$cartId)
    {
        $quote = $this->quoteFactory->create()->load($cartId);
        if (!$quote->getId()) {
            return false;
        }
        foreach ($quote->getAllVisibleItems() as $item) {
            if (isset($items[$item->getId()])) {
                $qty = $items[$item->getId()];
                $item->setQty($qty);
                if ($item->getHasError()) {
                    throw new LocalizedException(__($item->getMessage()));
                }
            }
        }
        $quote->collectTotals()->save();
        return true;
    }


}
