<?php
/**
 * @author    Tigren Solutions <info@tigren.com>
 * @copyright Copyright (c) 2019 Tigren Solutions <https://www.tigren.com>. All rights reserved.
 * @license   Open Software License ("OSL") v. 3.0
 */

declare(strict_types=1);

namespace Tigren\CatalogGraphQl\Model\Resolver\Search\Result;

use Magento\CatalogSearch\Model\Advanced;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;

/**
 * Class Criterias
 * @package Tigren\CatalogGraphQl\Model\Resolver\Search
 */
class Criterias implements ResolverInterface
{
    /**
     * Catalog search advanced
     *
     * @var Advanced
     */
    protected $_catalogSearchAdvanced;

    /**
     * Criterias constructor.
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
        /** @var Advanced $searchModel */
        $searchModel = $this->_getSearchModel($value);
        return $searchModel->getSearchCriterias() ?: null;
    }

    /**
     * Get search model
     *
     * @param array $value
     * @return Advanced
     * @throws LocalizedException
     */
    private function _getSearchModel(array $value)
    {
        if (!isset($value['model'])) {
            throw new LocalizedException(__('"model" value should be specified'));
        }
        return $value['model'];
    }
}
