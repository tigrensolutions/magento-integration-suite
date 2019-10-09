<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Tigren\PaypalExpress\Model\Data;

/**
 * @codeCoverageIgnoreStart
 */
/**
 * Class TokenData
 * @package Tigren\PaypalExpress\Model\Data
 */
class TokenData extends \Magento\Framework\Model\AbstractExtensibleModel implements
    \Tigren\PaypalExpress\Api\Data\TokenDataInterface
{
    /**
     * @return mixed
     */
    public function getQuoteId()
    {
        return $this->getData(self::QUOTE_ID);
    }

    /**
     * @param $quoteId
     * @return $this|mixed
     */
    public function setQuoteId($quoteId)
    {
        return $this->setData(self::QUOTE_ID, $quoteId);
    }

    /**
     * @return mixed
     */
    public function getCustomerId()
    {
        return $this->getData(self::CUSTOMER_ID);
    }

    /**
     * @param $customerId
     * @return $this|mixed
     */
    public function setCustomerId($customerId)
    {
        return $this->setData(self::CUSTOMER_ID, $customerId);
    }
}
