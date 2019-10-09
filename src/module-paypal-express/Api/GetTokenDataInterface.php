<?php
/**
 * @author Tigren Solutions <info@tigren.com>
 * @copyright Copyright (c) 2019 Tigren Solutions <https://www.tigren.com>. All rights reserved.
 * @license Open Software License ("OSL") v. 3.0
 */

namespace Tigren\PaypalExpress\Api;

/**
 * Interface GetTokenDataInterface
 * @package Tigren\PaypalExpress\Api
 */
interface GetTokenDataInterface
{

    /**
     * @param Data\TokenDataInterface $tokenData
     * @return mixed
     */
    public function getTokenData(\Tigren\PaypalExpress\Api\Data\TokenDataInterface $tokenData);

}
