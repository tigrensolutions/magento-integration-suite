<?php
/**
 * @author    Tigren Solutions <info@tigren.com>
 * @copyright Copyright (c) 2019 Tigren Solutions <https://www.tigren.com>. All rights reserved.
 * @license   Open Software License ("OSL") v. 3.0
 */

declare(strict_types=1);

namespace Tigren\CatalogGraphQl\Model\Resolver\Search;

use Magento\CatalogSearch\Model\Advanced;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;

/**
 * Class Result
 * @package Tigren\CatalogGraphQl\Model\Resolver\Search
 */
class Result implements ResolverInterface
{
    /**
     * Catalog search advanced
     *
     * @var Advanced
     */
    protected $_catalogSearchAdvanced;

    /**
     * Result constructor.
     * @param Advanced $catalogSearchAdvanced
     */
    public function __construct(
        Advanced $catalogSearchAdvanced
    ) {
        $this->_catalogSearchAdvanced = $catalogSearchAdvanced;
    }

    /**
     * @inheritdoc
     */
    public function resolve(Field $field, $context, ResolveInfo $info, array $value = null, array $args = null)
    {
        $result = [];
        if (empty($args['query'])) {
            return null;
        }
        parse_str($args['query'], $query);
        $this->_catalogSearchAdvanced->addFilters($query);
        return [
            'model' => $this->_catalogSearchAdvanced
        ];
    }
}
