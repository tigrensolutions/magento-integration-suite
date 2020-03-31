<?php
/**
 * @author    Tigren Solutions <info@tigren.com>
 * @copyright Copyright (c) 2019 Tigren Solutions <https://www.tigren.com>. All rights reserved.
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Tigren\CatalogGraphQl\Api;

/**
 * Interface CartRepositoryInterface
 * @api
 */
interface CartRepositoryInterface
{
    /**
     * update cart.
     * @param mixed $items
     * @param string $cartId
     * @return boolean
     */
    public function save($items, $cartId);

}
