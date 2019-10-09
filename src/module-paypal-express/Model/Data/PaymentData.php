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
 * Class CouponCodeData
 * @package Tigren\CouponCode\Model\Data
 */
class PaymentData extends \Magento\Framework\Model\AbstractExtensibleModel implements
    \Tigren\PaypalExpress\Api\Data\PaymentDataInterface
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
