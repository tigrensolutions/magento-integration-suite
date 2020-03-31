<?php
/**
 * @author    Tigren Solutions <info@tigren.com>
 * @copyright Copyright (c) 2019 Tigren Solutions <https://www.tigren.com>. All rights reserved.
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Tigren\CustomerGraphQl\Api\Customer;

/**
 * Interface AccountInterface
 * @package Tigren\CustomerGraphQl\Api\Customer
 */
interface AccountInterface
{
    /**
     * @return mixed
     */
    public function logout();

    /**
     * @return mixed
     */
    public function login();

    /**
     * @param mixed $email
     * * @param mixed $baseUrl
     * @return mixed
     */
    public function resetPassword($email, $baseUrl);
}
