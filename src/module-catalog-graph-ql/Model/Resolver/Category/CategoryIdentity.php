<?php
/**
 * @author    Tigren Solutions <info@tigren.com>
 * @copyright Copyright (c) 2019 Tigren Solutions <https://www.tigren.com>. All rights reserved.
 * @license   Open Software License ("OSL") v. 3.0
 */

declare(strict_types=1);

namespace Tigren\CatalogGraphQl\Model\Resolver\Category;

use Magento\Framework\GraphQl\Query\Resolver\IdentityInterface;

/**
 * Identity for resolved category
 */
class CategoryIdentity implements IdentityInterface
{
    /** @var string */
    private $cacheTag = 'tigren_cat_c';

    /**
     * Get category ID from resolved data
     *
     * @param array $resolvedData
     * @return string[]
     */
    public function getIdentities(array $resolvedData): array
    {
        return empty($resolvedData['id']) ?
            [] : [$this->cacheTag, sprintf('%s_%s', $this->cacheTag, $resolvedData['id'])];
    }
}
