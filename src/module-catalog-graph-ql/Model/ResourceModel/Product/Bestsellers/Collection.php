<?php

namespace Tigren\CatalogGraphQl\Model\ResourceModel\Product\Bestsellers;

class Collection extends \Magento\Catalog\Model\ResourceModel\Product\Collection
{
    /**
     * Init Select
     *
     * @return \Magento\Catalog\Model\ResourceModel\Product\Collection
     */
    protected function _initSelect()
    {
        parent::_initSelect();
        $this->joinOrderedQty();
        return $this;
    }

    protected function joinOrderedQty()
    {
        $connection = $this->getConnection();
        $orderJoinCondition = [
            'order.entity_id = order_items.order_id',
            $connection->quoteInto("order.state <> ?", \Magento\Sales\Model\Order::STATE_CANCELED),
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
