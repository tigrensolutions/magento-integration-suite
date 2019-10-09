<?php
/**
 * @author Tigren Solutions <info@tigren.com>
 * @copyright Copyright (c) 2019 Tigren Solutions <https://www.tigren.com>. All rights reserved.
 * @license Open Software License ("OSL") v. 3.0
 */

namespace Tigren\PaypalExpress\Api;

/**
 * Interface OnAuthorizationInterface
 * @package Tigren\PaypalExpress\Api
 */
interface OnAuthorizationInterface
{

    /**
     * @param $paymentToken
     * @param $payerId
     * @param $quoteId
     * @param $customerId
     * @return mixed
     */
    public function authorization(\Tigren\PaypalExpress\Api\Data\PaymentDataInterface $paymentData);

}
