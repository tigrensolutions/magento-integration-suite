<?php
/**
 * @author    Tigren Solutions <info@tigren.com>
 * @copyright Copyright (c) 2019 Tigren Solutions <https://www.tigren.com>. All rights reserved.
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Tigren\CouponCode\Model;

use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\ObjectManagerInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Model\QuoteIdMask;
use Magento\SalesRule\Model\CouponFactory;
use Tigren\CouponCode\Api\CouponApplyInterface;
use Magento\Quote\Model\QuoteIdMaskFactory;

/**
 * Interface ReviewManagement
 * @api
 */
class GuestCouponApply extends CouponApply implements CouponApplyInterface
{
    /**
     * @var QuoteIdMaskFactory
     */
    protected $quoteIdMaskFactory;

    /**
     * CouponApply constructor.
     * @param ObjectManagerInterface $objectManager
     * @param CouponFactory $couponFactory
     * @param ManagerInterface $messagemanager
     * @param CartRepositoryInterface $quoteRepository
     */
    public function __construct(
        ObjectManagerInterface $objectManager,
        CouponFactory $couponFactory,
        ManagerInterface $messagemanager,
        QuoteIdMaskFactory $quoteIdMaskFactory,
        CartRepositoryInterface $quoteRepository
    ) {
        parent::__construct($objectManager, $couponFactory, $messagemanager, $quoteRepository);
        $this->quoteIdMaskFactory = $quoteIdMaskFactory;
    }

    /**
     * @param $cartId
     * @return mixed
     */
    public function getQuoteId($cartId)
    {
        /** @var QuoteIdMask $quoteIdMask */
        $quoteIdMask = $this->quoteIdMaskFactory->create()->load($cartId, 'masked_id');
        return $quoteIdMask->getQuoteId();
    }
}
