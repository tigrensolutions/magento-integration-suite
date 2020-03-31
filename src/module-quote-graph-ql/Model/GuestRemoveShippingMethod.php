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
use Magento\Quote\Model\QuoteIdMask;
use Magento\Quote\Model\QuoteIdMaskFactory;
use Tigren\QuoteGraphQl\Api\GuestRemoveShippingMethodInterface;

/**
 * Class GuestRemoveShippingMethod
 * @package Tigren\QuoteGraphQl\Model
 */
class GuestRemoveShippingMethod implements GuestRemoveShippingMethodInterface
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
     * @var QuoteIdMaskFactory
     */
    protected $quoteIdMaskFactory;

    /**
     * GuestRemoveShippingMethod constructor.
     * @param CartRepositoryInterface $quoteRepository
     * @param QuoteIdMaskFactory $quoteIdMaskFactory
     * @param CartTotalRepositoryInterface $cartTotalsRepository
     */
    public function __construct(
        CartRepositoryInterface $quoteRepository,
        QuoteIdMaskFactory $quoteIdMaskFactory,
        CartTotalRepositoryInterface $cartTotalsRepository
    ) {
        $this->quoteIdMaskFactory = $quoteIdMaskFactory;
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
        $quoteIdMask = $this->quoteIdMaskFactory->create()->load($cartId, 'masked_id');
        $cartId = $quoteIdMask->getQuoteId();
        /** @var Quote $quote */
        $quote = $this->quoteRepository->getActive($cartId);
        $quote->getShippingAddress()->setShippingMethod(null)->setCollectShippingRates(true);  //setting method to null
        $quote->setTotalsCollectedFlag(false)->collectTotals();
        $quote->save();
        return $this->cartTotalsRepository->get($cartId);
    }
}
