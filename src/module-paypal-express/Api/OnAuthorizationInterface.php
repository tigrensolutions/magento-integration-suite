<?php
/**
 * @author    Tigren Solutions <info@tigren.com>
 * @copyright Copyright (c) 2019 Tigren Solutions <https://www.tigren.com>. All rights reserved.
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Tigren\PaypalExpress\Api;

use Tigren\PaypalExpress\Api\Data\PaymentDataInterface;

/**
 * Interface OnAuthorizationInterface
 * @package Tigren\PaypalExpress\Api
 */
interface OnAuthorizationInterface
{
    /**
     * @param PaymentDataInterface $paymentData
     * @return mixed
     */
    public function authorization(PaymentDataInterface $paymentData);
}
