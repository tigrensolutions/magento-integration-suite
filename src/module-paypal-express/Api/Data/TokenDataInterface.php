<?php
/**
 * @author    Tigren Solutions <info@tigren.com>
 * @copyright Copyright (c) 2019 Tigren Solutions <https://www.tigren.com>. All rights reserved.
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Tigren\PaypalExpress\Api\Data;

/**
 * Interface TokenDataInterface
 * @package Tigren\PaypalExpress\Api\Data
 */
interface TokenDataInterface
{
    /**
     *
     */
    const QUOTE_ID = 'quote_id';

    /**
     * @return mixed
     */
    public function getQuoteId();

    /**
     * @param $quoteId
     * @return mixed
     */
    public function setQuoteId($quoteId);
}
