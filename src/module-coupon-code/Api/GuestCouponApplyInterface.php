<?php
/**
 * @author Tigren Solutions <info@tigren.com>
 * @copyright Copyright (c) 2019 Tigren Solutions <https://www.tigren.com>. All rights reserved.
 * @license Open Software License ("OSL") v. 3.0
 */

namespace Tigren\CouponCode\Api;

/**
 * Interface ReviewManagementInterface
 * @api
 */
/**
 * Interface CouponApplyInterface
 * @package Tigren\CouponCode\Api
 */
/**
 * Interface CouponApplyInterface
 * @package Tigren\CouponCode\Api
 */
interface GuestCouponApplyInterface
{


    /**
     * @param $cartId
     * @param Data\CouponCodeInterface $couponCode
     * @return mixed
     */
    public function submit(\Tigren\CouponCode\Api\Data\CouponCodeInterface $couponCode);

}
