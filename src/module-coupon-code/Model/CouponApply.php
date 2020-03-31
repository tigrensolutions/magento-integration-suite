<?php
/**
 * @author    Tigren Solutions <info@tigren.com>
 * @copyright Copyright (c) 2019 Tigren Solutions <https://www.tigren.com>. All rights reserved.
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Tigren\CouponCode\Model;

use Exception;
use Magento\Checkout\Helper\Cart;
use Magento\Framework\Escaper;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\ObjectManagerInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Model\Quote;
use Magento\SalesRule\Model\CouponFactory;
use Tigren\CouponCode\Api\CouponApplyInterface;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Tigren\CouponCode\Api\Data\CouponCodeInterface;

/**
 * Interface ReviewManagement
 * @api
 */
class CouponApply implements CouponApplyInterface
{
    /**
     * Quote repository.
     *
     * @var CartRepositoryInterface
     */
    protected $quoteRepository;

    /**
     * @var ObjectManagerInterface
     */
    protected $_objectManager;

    /**
     * Coupon factory
     *
     * @var CouponFactory
     */
    protected $couponFactory;

    /**
     * @var ManagerInterface
     */
    protected $messageManager;


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
        CartRepositoryInterface $quoteRepository
    ) {
        $this->quoteRepository = $quoteRepository;
        $this->couponFactory = $couponFactory;
        $this->_objectManager = $objectManager;
        $this->messageManager = $messagemanager;
    }

    /**
     * {@inheritdoc}
     */
    public function submit(CouponCodeInterface $couponCodeDta)
    {
        $result = [
            'sucess' => 1,
            'message' => ''
        ];

        $couponCode = $couponCodeDta->getRemove() == 1
            ? ''
            : trim($couponCodeDta->getCouponCode());
        $quoteId = $this->getQuoteId($couponCodeDta->getCartId());
        /** @var Quote $quote */
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
            $isCodeLengthValid = $codeLength && $codeLength <= Cart::COUPON_CODE_MAX_LENGTH;

            $itemsCount = $quote->getItemsCount();
            if ($itemsCount) {
                $quote->getShippingAddress()->setCollectShippingRates(true);
                $quote->setCouponCode($isCodeLengthValid ? $couponCode : '')->collectTotals();
                $this->quoteRepository->save($quote);
            }

            if ($codeLength) {
                $escaper = $this->_objectManager->get(Escaper::class);
                $coupon = $this->couponFactory->create();
                $coupon->load($couponCode, 'code');
                if (!$itemsCount) {
                    if ($isCodeLengthValid && $coupon->getId()) {
                        $quote->setCouponCode($couponCode)->save();
                        $result = [
                            'success' => 1,
                            'message' => __('You used coupon code ') . $couponCode
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
                            'message' => __('You used coupon code ') . $couponCode
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
        } catch (LocalizedException $e) {
        } catch (Exception $e) {
            $result = [
                'success' => 0,
                'message' => __('We cannot apply the coupon code.')
            ];
        }

        return json_encode($result);
    }

    /**
     * @param $cartId
     * @return mixed
     */
    public function getQuoteId($cartId)
    {
        return $cartId;
    }
}
