<?php
/**
 * @author Tigren Solutions <info@tigren.com>
 * @copyright Copyright (c) 2019 Tigren Solutions <https://www.tigren.com>. All rights reserved.
 * @license Open Software License ("OSL") v. 3.0
 */
namespace Tigren\CatalogGraphQl\Model;

use Magento\Framework\Exception\LocalizedException;
use Magento\Quote\Model\QuoteFactory;
use Tigren\CatalogGraphQl\Api\CartRepositoryInterface;

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
