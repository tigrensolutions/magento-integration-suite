<?php
/**
 * @author Tigren Solutions <info@tigren.com>
 * @copyright Copyright (c) 2019 Tigren Solutions <https://www.tigren.com>. All rights reserved.
 * @license Open Software License ("OSL") v. 3.0
 */

namespace Tigren\PaypalExpress\Api\Data;

/**
 * Interface PaymentDataInterface
 * @package Tigren\PaypalExpress\Api\Data
 */
interface PaymentDataInterface
{
    /**
     *
     */
    const PAYMENT_TOKEN = 'payment_token';

    /**
     *
     */
    const PAYER_ID = 'payer_id';

    /**
     *
     */
    const QUOTE_ID = 'quote_id';

    /**
     *
     */
    const CUSTOMER_ID = 'customer_id';

    /**
     * @return mixed
     */
    public function getPaymentToken();

    /**
     * @param $paymentToken
     * @return mixed
     */
    public function setPaymentToken($paymentToken);

    /**
     * @return mixed
     */
    public function getPayerId();

    /**
     * @param $payerId
     * @return mixed
     */
    public function setPayerId($payerId);

    /**
     * @return mixed
     */
    public function getQuoteId();

    /**
     * @param $quoteId
     * @return mixed
     */
    public function setQuoteId($quoteId);

    /**
     * @return mixed
     */
    public function getCustomerId();


    /**
     * @param $customerId
     * @return mixed
     */
    public function setCustomerId($customerId);

}
