<?php
/**
 * @author    Tigren Solutions <info@tigren.com>
 * @copyright Copyright (c) 2019 Tigren Solutions <https://www.tigren.com>. All rights reserved.
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Tigren\ProgressiveWebApp\PhpFirebaseCloudMessaging\Recipient;

/**
 * Class Topic
 * @package Tigren\ProgressiveWebApp\PhpFirebaseCloudMessaging\Recipient
 */
class Topic extends Recipient
{
    /**
     * @var
     */
    private $name;

    /**
     * Topic constructor.
     * @param $name
     */
    public function __construct($name)
    {
        $this->name = $name;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getName()
    {
        return $this->name;
    }
}
