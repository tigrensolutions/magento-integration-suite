<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Tigren\CatalogGraphQl\Model\Quote;

use Magento\Framework\Exception\LocalizedException;
use Magento\Quote\Model\QuoteIdMask;
use Magento\Quote\Model\QuoteIdMaskFactory;
use Magento\Quote\Api\CartRepositoryInterface;
use Tigren\CatalogGraphQl\Api\Quote\GuestCartRepositoryInterface;
use Magento\Quote\Model\QuoteFactory;

/**
 * Cart Item repository class for guest carts.
 */
class GuestCartRepository implements GuestCartRepositoryInterface
{
    /**
     * @var QuoteIdMaskFactory
     */
    protected $quoteIdMaskFactory;

    /**
     * @var CartRepositoryInterface
     */
    protected $cartRepository;

    /**
     * @var QuoteFactory
     */
    protected $quoteFactory;

    /**
     * Constructs a read service object.
     *
     * @param QuoteIdMaskFactory $quoteIdMaskFactory
     * @param CartRepositoryInterface $cartRepository
     * @param QuoteFactory $quoteFactory
     */
    public function __construct(
        QuoteIdMaskFactory $quoteIdMaskFactory,
        CartRepositoryInterface $cartRepository,
        QuoteFactory $quoteFactory
    ) {
        $this->quoteIdMaskFactory = $quoteIdMaskFactory;
        $this->cartRepository = $cartRepository;
        $this->quoteFactory = $quoteFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function save($items,$cartId)
    {
        /** @var $quoteIdMask QuoteIdMask */
        $quoteIdMask = $this->quoteIdMaskFactory->create()->load($cartId, 'masked_id');
        $quote = $this->quoteFactory->create()->load($quoteIdMask->getQuoteId());
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
