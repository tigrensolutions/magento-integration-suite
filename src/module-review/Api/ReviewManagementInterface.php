<?php
/**
 * @author    Tigren Solutions <info@tigren.com>
 * @copyright Copyright (c) 2019 Tigren Solutions <https://www.tigren.com>. All rights reserved.
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Tigren\Review\Api;

/**
 * Interface ReviewManagementInterface
 * @api
 */
interface ReviewManagementInterface
{
    /**
     * add reviews.
     * @param string $sku
     * @param mixed $data
     * @return int
     */
    public function submit($sku, $data);
}
