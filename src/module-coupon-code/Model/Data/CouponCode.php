<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Tigren\CouponCode\Model\Data;

/**
 * @codeCoverageIgnoreStart
 */
/**
 * Class CouponCodeData
 * @package Tigren\CouponCode\Model\Data
 */
class CouponCode extends \Magento\Framework\Model\AbstractExtensibleModel implements
    \Tigren\CouponCode\Api\Data\CouponCodeInterface
{
    /**
     * @return mixed
     */
    public function getCouponCode()
    {
        return $this->getData(self::COUPON_CODE);
    }

    /**
     * @param $couponCode
     * @return $this|mixed
     */
    public function setCouponCode($couponCode)
    {
        return $this->setData(self::COUPON_CODE, $couponCode);
    }

    /**
     * @return mixed
     */
    public function getRemove()
    {
        return $this->getData(self::REMOVE);
    }

    /**
     * @param $remove
     * @return $this|mixed
     */
    public function setRemove($remove)
    {
        return $this->setData(self::REMOVE, $remove);
    }

    /**
     * @return mixed
     */
    public function getCartId()
    {
        return $this->getData(self::CART_ID);
    }

    /**
     * @param $cartId
     * @return $this|mixed
     */
    public function setCartId($cartId)
    {
        return $this->setData(self::CART_ID, $cartId);
    }
}
