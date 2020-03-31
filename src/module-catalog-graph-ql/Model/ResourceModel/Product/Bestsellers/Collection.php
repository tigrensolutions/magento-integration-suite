<?php
/**
 * @author    Tigren Solutions <info@tigren.com>
 * @copyright Copyright (c) 2019 Tigren Solutions <https://www.tigren.com>. All rights reserved.
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Tigren\CatalogGraphQl\Model\ResourceModel\Product\Bestsellers;

use Magento\Sales\Model\Order;

/**
 * Class Collection
 * @package Tigren\CatalogGraphQl\Model\ResourceModel\Product\Bestsellers
 */
class Collection extends \Magento\Catalog\Model\ResourceModel\Product\Collection
{
    /**
     * Init Select
     *
     * @return Collection
     */
    protected function _initSelect()
    {
        parent::_initSelect();
        $this->joinOrderedQty();
        return $this;
    }

    /**
     * @return $this
     */
    protected function joinOrderedQty()
    {
        $connection = $this->getConnection();
        $orderJoinCondition = [
            'order.entity_id = order_items.order_id',
            $connection->quoteInto("order.state <> ?", Order::STATE_CANCELED),
        ];

        $this->getSelect()->join(
            ['order_items' => $this->getTable('sales_order_item')],
            'order_items.product_id=e.entity_id',
            [
                'popularity' => 'COUNT(order_items.qty_ordered)',
            ]
        )->join(
            ['order' => $this->getTable('sales_order')],
            implode(' AND ', $orderJoinCondition),
            []
        );

        $this->getSelect()
            ->where('parent_item_id IS NULL')
            ->order('popularity DESC')
            ->group('e.entity_id');

        return $this;
    }
}
