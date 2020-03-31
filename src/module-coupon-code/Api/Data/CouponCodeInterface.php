<?php
/**
 * @author    Tigren Solutions <info@tigren.com>
 * @copyright Copyright (c) 2019 Tigren Solutions <https://www.tigren.com>. All rights reserved.
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Tigren\CouponCode\Api\Data;

/**
 * Interface CouponCodeDataInterface
 * @package Tigren\CouponCode\Api\Data
 */
interface CouponCodeInterface
{
    /**
     *
     */
    const COUPON_CODE = 'coupon_code';

    /**
     *
     */
    const REMOVE = 'remove';

    /**
     *
     */
    const CART_ID = 'cart_id';

    /**
     * @return mixed
     */
    public function getCouponCode();

    /**
     * @param $couponCode
     * @return mixed
     */
    public function setCouponCode($couponCode);

    /**
     * @param $remove
     * @return mixed
     */
    public function setRemove($remove);

    /**
     * @return mixed
     */
    public function getRemove();

    /**
     * @return mixed
     */
    public function getCartId();

    /**
     * @param $cartId
     * @return mixed
     */
    public function setCartId($cartId);
}
