<?php
/**
 * @author    Tigren Solutions <info@tigren.com>
 * @copyright Copyright (c) 2019 Tigren Solutions <https://www.tigren.com>. All rights reserved.
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Tigren\ProgressiveWebApp\PhpFirebaseCloudMessaging\Recipient;

/**
 * Class Device
 * @package Tigren\ProgressiveWebApp\PhpFirebaseCloudMessaging\Recipient
 */
class Device extends Recipient
{
    /**
     * @var
     */
    private $token;

    /**
     * Device constructor.
     * @param $token
     */
    public function __construct($token)
    {
        $this->token = $token;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getToken()
    {
        return $this->token;
    }
}
