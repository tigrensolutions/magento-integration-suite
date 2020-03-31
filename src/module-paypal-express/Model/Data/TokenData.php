<?php
/**
 * @author    Tigren Solutions <info@tigren.com>
 * @copyright Copyright (c) 2019 Tigren Solutions <https://www.tigren.com>. All rights reserved.
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Tigren\PaypalExpress\Model\Data;

use Magento\Framework\Model\AbstractExtensibleModel;
use Tigren\PaypalExpress\Api\Data\TokenDataInterface;

/**
 * Class TokenData
 * @package Tigren\PaypalExpress\Model\Data
 */
class TokenData extends AbstractExtensibleModel implements
    TokenDataInterface
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
}
