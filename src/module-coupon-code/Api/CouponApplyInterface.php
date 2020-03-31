<?php
/**
 * @author    Tigren Solutions <info@tigren.com>
 * @copyright Copyright (c) 2019 Tigren Solutions <https://www.tigren.com>. All rights reserved.
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Tigren\CouponCode\Api;

use Tigren\CouponCode\Api\Data\CouponCodeInterface;

/**
 * Interface CouponApplyInterface
 * @package Tigren\CouponCode\Api
 */
interface CouponApplyInterface
{
    /**
     * @param Data\CouponCodeInterface $couponCode
     * @return mixed
     */
    public function submit(CouponCodeInterface $couponCode);
}
