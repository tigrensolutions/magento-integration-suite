<?php
/**
 * @author    Tigren Solutions <info@tigren.com>
 * @copyright Copyright (c) 2019 Tigren Solutions <https://www.tigren.com>. All rights reserved.
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Tigren\QuoteGraphQl\Model;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\CartTotalRepositoryInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Address\Total;
use Tigren\QuoteGraphQl\Api\RemoveShippingMethodInterface;

/**
 * Class RemoveShippingMethod
 * @package Tigren\QuoteGraphQl\Model
 */
class RemoveShippingMethod implements RemoveShippingMethodInterface
{
    /**
     * Quote repository.
     *
     * @var CartRepositoryInterface
     */
    protected $quoteRepository;

    /**
     * @var CartTotalRepositoryInterface
     */
    protected $cartTotalsRepository;

    /**
     * RemoveShippingMethod constructor.
     * @param CartRepositoryInterface $quoteRepository
     * @param CartTotalRepositoryInterface $cartTotalsRepository
     */
    public function __construct(
        CartRepositoryInterface $quoteRepository,
        CartTotalRepositoryInterface $cartTotalsRepository
    ) {
        $this->quoteRepository = $quoteRepository;
        $this->cartTotalsRepository = $cartTotalsRepository;
    }

    /**
     * @param string $cartId
     * @return Total|mixed
     * @throws NoSuchEntityException
     */
    public function removeShippingMethod($cartId)
    {
        /** @var Quote $quote */
        $quote = $this->quoteRepository->getActive($cartId);
        $quote->getShippingAddress()->setShippingMethod(null)->setCollectShippingRates(true);  //setting method to null
        $quote->setTotalsCollectedFlag(false)->collectTotals();
        $quote->save();
        return $this->cartTotalsRepository->get($cartId);
    }
}
