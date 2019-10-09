<?php
/**
 * @author Tigren Solutions <info@tigren.com>
 * @copyright Copyright (c) 2019 Tigren Solutions <https://www.tigren.com>. All rights reserved.
 * @license Open Software License ("OSL") v. 3.0
 */

namespace Tigren\CouponCode\Model;

use Tigren\CouponCode\Api\CouponApplyInterface;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;

/**
 * Interface ReviewManagement
 * @api
 */
class CouponApply implements CouponApplyInterface
{

    /**
     * Quote repository.
     *
     * @var \Magento\Quote\Api\CartRepositoryInterface
     */
    protected $quoteRepository;

    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    protected $_objectManager;

    /**
     * Coupon factory
     *
     * @var \Magento\SalesRule\Model\CouponFactory
     */
    protected $couponFactory;

    /**
     * @var \Magento\Framework\Message\ManagerInterface
     */
    protected $messageManager;


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
        \Magento\Quote\Api\CartRepositoryInterface $quoteRepository
    ) {
        $this->quoteRepository = $quoteRepository;
        $this->couponFactory = $couponFactory;
        $this->_objectManager = $objectManager;
        $this->messageManager = $messagemanager;
    }

    /**
     * {@inheritdoc}
     */
    public function submit(\Tigren\CouponCode\Api\Data\CouponCodeInterface $couponCodeDta)
    {
        $result = [
            'sucess' => 1,
            'message' => ''
        ];

        $couponCode = $couponCodeDta->getRemove() == 1
            ? ''
            : trim($couponCodeDta->getCouponCode());
        $quoteId = $this->getQuoteId($couponCodeDta->getCartId());
        /** @var \Magento\Quote\Model\Quote $quote */
        $quote = $this->quoteRepository->getActive($quoteId);
        $oldCouponCode = $quote->getCouponCode();

        $codeLength = strlen($couponCode);
        if (!$codeLength && !strlen($oldCouponCode)) {
            $result = [
                'sucess' => 0,
                'message' => __('Specify the "coupon code" value.')
            ];
        }

        try {
            $isCodeLengthValid = $codeLength && $codeLength <= \Magento\Checkout\Helper\Cart::COUPON_CODE_MAX_LENGTH;

            $itemsCount = $quote->getItemsCount();
            if ($itemsCount) {
                $quote->getShippingAddress()->setCollectShippingRates(true);
                $quote->setCouponCode($isCodeLengthValid ? $couponCode : '')->collectTotals();
                $this->quoteRepository->save($quote);
            }

            if ($codeLength) {
                $escaper = $this->_objectManager->get(\Magento\Framework\Escaper::class);
                $coupon = $this->couponFactory->create();
                $coupon->load($couponCode, 'code');
                if (!$itemsCount) {
                    if ($isCodeLengthValid && $coupon->getId()) {
                        $quote->setCouponCode($couponCode)->save();
                        $result = [
                            'success' => 1,
                            'message' => __('You used coupon code '). $couponCode
                        ];
                    } else {
                        $result = [
                            'success' => 0,
                            'message' => __(
                                'The coupon code "%1" is not valid.',
                                $escaper->escapeHtml($couponCode)
                            )
                        ];
                    }
                } else {
                    if ($isCodeLengthValid && $coupon->getId() && $couponCode == $quote->getCouponCode()) {
                        $result = [
                            'success' => 1,
                            'message' => __('You used coupon code '). $couponCode
                        ];
                    } else {
                        $result = [
                            'success' => 0,
                            'message' => __(
                                'The coupon code "%1" is not valid.',
                                $escaper->escapeHtml($couponCode)
                            )
                        ];
                    }
                }
            } else {
                $result = [
                    'success' => 1,
                    'message' => __('You canceled the coupon code.')
                ];
            }
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
        } catch (\Exception $e) {
            $result = [
                'success' => 0,
                'message' => __('We cannot apply the coupon code.')
            ];
        }

        return json_encode($result);
    }

    public function getQuoteId($cartId){
        return $cartId;
    }

}
