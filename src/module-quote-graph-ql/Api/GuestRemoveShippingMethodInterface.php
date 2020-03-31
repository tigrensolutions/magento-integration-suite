<?php
/**
 * @author    Tigren Solutions <info@tigren.com>
 * @copyright Copyright (c) 2019 Tigren Solutions <https://www.tigren.com>. All rights reserved.
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Tigren\QuoteGraphQl\Api;

use Magento\Quote\Api\Data\TotalsInterface;

/**
 * Interface GuestRemoveShippingMethodInterface
 * @package Tigren\QuoteGraphQl\Api
 */
interface GuestRemoveShippingMethodInterface
{
    /**
     * @param string $cartId
     * @return TotalsInterface Quote totals data.
     */
    public function removeShippingMethod($cartId);
}
