<?php
/**
 * @author    Tigren Solutions <info@tigren.com>
 * @copyright Copyright (c) 2019 Tigren Solutions <https://www.tigren.com>. All rights reserved.
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Tigren\CouponCode\Model\Data;

use Magento\Framework\Model\AbstractExtensibleModel;
use Tigren\CouponCode\Api\Data\CouponCodeInterface;

/**
 * Class CouponCodeData
 * @package Tigren\CouponCode\Model\Data
 */
class CouponCode extends AbstractExtensibleModel implements
    CouponCodeInterface
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
