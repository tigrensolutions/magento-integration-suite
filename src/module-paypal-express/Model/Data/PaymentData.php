<?php
/**
 * @author    Tigren Solutions <info@tigren.com>
 * @copyright Copyright (c) 2019 Tigren Solutions <https://www.tigren.com>. All rights reserved.
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Tigren\PaypalExpress\Model\Data;

use Magento\Framework\Model\AbstractExtensibleModel;
use Tigren\PaypalExpress\Api\Data\PaymentDataInterface;

/**
 * Class CouponCodeData
 * @package Tigren\CouponCode\Model\Data
 */
class PaymentData extends AbstractExtensibleModel implements
    PaymentDataInterface
{
    /**
     * @return mixed
     */
    public function getPaymentToken()
    {
        return $this->getData(self::PAYMENT_TOKEN);
    }

    /**
     * @param $paymentToken
     * @return $this|mixed
     */
    public function setPaymentToken($paymentToken)
    {
        return $this->setData(self::PAYMENT_TOKEN, $paymentToken);
    }

    /**
     * @return mixed
     */
    public function getPayerId()
    {
        return $this->getData(self::PAYER_ID);
    }

    /**
     * @param $payerId
     * @return $this|mixed
     */
    public function setPayerId($payerId)
    {
        return $this->setData(self::PAYER_ID, $payerId);
    }

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
