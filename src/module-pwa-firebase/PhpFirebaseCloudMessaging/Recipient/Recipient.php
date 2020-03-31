<?php
/**
 * @author    Tigren Solutions <info@tigren.com>
 * @copyright Copyright (c) 2019 Tigren Solutions <https://www.tigren.com>. All rights reserved.
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Tigren\ProgressiveWebApp\PhpFirebaseCloudMessaging\Recipient;

/**
 * Class Recipient
 * @package Tigren\ProgressiveWebApp\PhpFirebaseCloudMessaging\Recipient
 */
class Recipient
{
    /**
     * @var
     */
    private $to;

    /**
     * @param $to
     * @return $this
     */
    public function setTo($to)
    {
        $this->to = $to;
        return $this;
    }

    /**
     * @return mixed
     */
    public function toJson()
    {
        return $this->to;
    }
}
