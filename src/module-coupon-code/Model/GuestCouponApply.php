<?php
/**
 * @author Tigren Solutions <info@tigren.com>
 * @copyright Copyright (c) 2019 Tigren Solutions <https://www.tigren.com>. All rights reserved.
 * @license Open Software License ("OSL") v. 3.0
 */

namespace Tigren\CouponCode\Model;

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
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     * @param \Magento\SalesRule\Model\CouponFactory $couponFactory
     * @param \Magento\Framework\Message\ManagerInterface $messagemanager
     * @param \Magento\Quote\Api\CartRepositoryInterface $quoteRepository
     */
    public function __construct(
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Magento\SalesRule\Model\CouponFactory $couponFactory,
        \Magento\Framework\Message\ManagerInterface $messagemanager,
        QuoteIdMaskFactory $quoteIdMaskFactory,
        \Magento\Quote\Api\CartRepositoryInterface $quoteRepository
    ) {
        parent::__construct($objectManager, $couponFactory, $messagemanager, $quoteRepository);
        $this->quoteIdMaskFactory = $quoteIdMaskFactory;
    }

    /**
     * @param $cartId
     * @return mixed
     */
    public function getQuoteId($cartId){
        /** @var \Magento\Quote\Model\QuoteIdMask $quoteIdMask */
        $quoteIdMask = $this->quoteIdMaskFactory->create()->load($cartId, 'masked_id');
        return $quoteIdMask->getQuoteId();
    }

}
