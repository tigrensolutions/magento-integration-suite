<?php
/**
 * @author Tigren Solutions <info@tigren.com>
 * @copyright Copyright (c) 2019 Tigren Solutions <https://www.tigren.com>. All rights reserved.
 * @license Open Software License ("OSL") v. 3.0
 */

namespace Tigren\WishlistGraphQl\Api;

/**
 * Interface for managing customers wishlist.
 * @api
 */
interface WishlistManagementInterface
{
    /**
     * Update Wishlist
     * @param mixed $wishlistUpdate
     * @return mixed
     */
    public function update($wishlistUpdate);

}
