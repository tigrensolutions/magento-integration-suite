<?php
/**
 * @author    Tigren Solutions <info@tigren.com>
 * @copyright Copyright (c) 2019 Tigren Solutions <https://www.tigren.com>. All rights reserved.
 * @license   Open Software License ("OSL") v. 3.0
 */
declare(strict_types=1);

namespace Tigren\CustomerGraphQl\Model\Resolver;

use DateTime;
use DateTimeInterface;
use Exception;
use IntlDateFormatter;
use Magento\Customer\Api\AddressRepositoryInterface;
use Magento\Customer\Model\Customer;
use Magento\Framework\Escaper;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Creditmemo;
use Magento\Sales\Model\Order\Invoice;
use Magento\Sales\Model\Order\Payment;
use Magento\Sales\Model\Order\Shipment;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory;

/**
 * Class CustomerOrders
 * @package Tigren\CustomerGraphQl\Model\Resolver
 */
class CustomerOrders implements ResolverInterface
{
    /**
     * @var TimezoneInterface
     */
    protected $_localeDate;

    /**
     * Escaper
     *
     * @var Escaper
     */
    protected $_escaper;

    /**
     * @var CollectionFactory
     */
    private $orderCollectionFactory;

    /**
     * @var AddressRepositoryInterface
     */
    private $addressRepository;

    /**
     * @var SerializerInterface
     */
    private $jsonSerializer;

    /**
     * @param CollectionFactory $orderCollectionFactory
     * @param AddressRepositoryInterface $addressRepository
     * @param TimezoneInterface $localeDate
     * @param SerializerInterface $jsonSerializer
     * @param Escaper $jsonSerializer
     */
    public function __construct(
        CollectionFactory $orderCollectionFactory,
        AddressRepositoryInterface $addressRepository,
        TimezoneInterface $localeDate,
        SerializerInterface $jsonSerializer,
        Escaper $_escaper
    ) {
        $this->orderCollectionFactory = $orderCollectionFactory;
        $this->addressRepository = $addressRepository;
        $this->_localeDate = $localeDate;
        $this->jsonSerializer = $jsonSerializer;
        $this->_escaper = $_escaper;
    }

    /**
     * @inheritdoc
     */
    public function resolve(
        Field $field,
        $context,
        ResolveInfo $info,
        array $value = null,
        array $args = null
    ) {
        if (!isset($value['model'])) {
            throw new LocalizedException(__('"id" value should be specified'));
        }
        if ($args['currentPage'] < 1) {
            throw new GraphQlInputException(__('currentPage value must be greater than 0.'));
        }
        if ($args['pageSize'] < 1) {
            throw new GraphQlInputException(__('pageSize value must be greater than 0.'));
        }
        /** @var Customer $customer */
        $customer = $value['model'];

        $collection = $this->orderCollectionFactory->create()->addFieldToSelect(
            '*'
        )->addFieldToFilter(
            'customer_id',
            $customer->getId()
        )->setOrder(
            'created_at',
            'desc'
        );
        $totalCount = $collection->getSize();
        if (!$totalCount) {
            return null;
        }
        $pageSize = $args['pageSize'];
        $curPage = $args['currentPage'];
        $pageInfo = [
            'page_size' => $pageSize,
            'current_page' => $curPage,
            'total_pages' => ceil($totalCount / $pageSize)
        ];
        $collection->setPageSize($pageSize)->setCurPage($curPage);
        $ordersData = [];
        foreach ($collection as $order) {
            $shippingAddress = $order->getShippingAddress();
            $billingAddress = $order->getBillingAddress();
            $items = $order->getAllVisibleItems();
            $data = $order->getData();
            $data['created_at'] = !empty($data['created_at']) ? $this->formatDate($data['created_at']) : '';
            $data['payment'] = $this->getPaymentMethod($order);
            $data['shipping_address'] = $this->getAddressData($shippingAddress);
            $data['billing_address'] = $this->getAddressData($billingAddress);
            $data['items'] = $this->getItemsData($items);
            $data['invoices'] = $this->getInvoices($order);
            $data['shipments'] = $this->getShipments($order);
            $data['creditmemos'] = $this->getCreditmemos($order);
            $ordersData[] = $data;
        }
        $data = [
            'total_count' => $totalCount,
            'items' => $ordersData ?: null,
            'page_info' => $pageInfo
        ];

        return $data;
    }

    /**
     * Retrieve formatting date
     *
     * @param null|string|DateTimeInterface $date
     * @param int $format
     * @param bool $showTime
     * @param null|string $timezone
     * @return string
     * @throws Exception
     */
    public function formatDate(
        $date = null,
        $format = IntlDateFormatter::SHORT,
        $showTime = false,
        $timezone = null
    ) {
        $date = $date instanceof DateTimeInterface ? $date : new DateTime($date);
        return $this->_localeDate->formatDateTime(
            $date,
            $format,
            $showTime ? $format : IntlDateFormatter::NONE,
            null,
            $timezone
        );
    }

    /**
     * @param $order
     * @return array
     * @throws LocalizedException
     */
    private function getPaymentMethod($order)
    {
        /** @var Payment $payment */
        $payment = $order->getPayment();
        $additional = $payment->getAdditionalInformation();

        return [
            'method_title' => $payment->getMethodInstance()->getTitle(),
            'method_code' => $payment->getMethodInstance()->getCode(),
            'additional_data' => $additional ? $this->jsonSerializer->serialize($additional) : null
        ];
    }

    /**
     * @param $address
     * @return |null
     */
    private function getAddressData($address)
    {
        $data = $address ? $address->getData() : null;

        $data['id'] = $data['entity_id'];
        unset($data['entity_id']);
        return $data;
    }

    /**
     * @param $items
     * @return array
     */
    private function getItemsData($items)
    {
        $data = [];
        foreach ($items as $item) {
            $itemData = $item->getData();
            $data[] = [
                'id' => $itemData['item_id'],
                'sku' => $itemData['sku'],
                'name' => $itemData['name'],
                'price' => (float)$itemData['price_incl_tax'],
                'subtotal' => (float)$itemData['row_total_incl_tax'],
                'qty_ordered' => (int)$itemData['qty_ordered'],
                'qty_shipped' => (int)$itemData['qty_shipped'],
                'qty_invoiced' => (int)$itemData['qty_invoiced'],
                'qty_refunded' => (int)$itemData['qty_refunded'],
                'qty_backordered' => (int)$itemData['qty_backordered'],
                'qty_canceled' => (int)$itemData['qty_canceled'],
                'options' => $this->getItemOptions($item)
            ];
        }

        return $data;
    }

    /**
     * Get item options.
     *
     * @return array
     */
    public function getItemOptions($item)
    {
        $result = [];
        if (!($item instanceof Order\Item)) {
            $item = $item->getOrderItem();
        }
        $options = $item->getProductOptions();
        if ($options) {
            if (isset($options['options'])) {
                $result = array_merge($result, $options['options']);
            }
            if (isset($options['additional_options'])) {
                $result = array_merge($result, $options['additional_options']);
            }
            if (isset($options['attributes_info'])) {
                $result = array_merge($result, $options['attributes_info']);
            }
        }
        $options = [];
        foreach ($result as $_option) {
            if (!empty($_option['label']) && !empty($_option['value']) && gettype($_option['value']) == 'string') {
                $options[] = [
                    'label' => $this->escapeHtml($_option['label']),
                    'value' => $_option['value']
                ];
            }
        }
        return $options ?: null;
    }

    /**
     * Escape HTML entities
     *
     * @param string|array $data
     * @param array|null $allowedTags
     * @return string
     */
    public function escapeHtml($data, $allowedTags = null)
    {
        return $this->_escaper->escapeHtml($data, $allowedTags);
    }

    /**
     * @param Order $order
     * @return |null
     */
    public function getInvoices(Order $order)
    {
        $invoices = [];
        foreach ($order->getInvoiceCollection() as $invoice) {
            /** @var Invoice $invoice */
            $data = [
                'id' => $invoice->getId(),
                'increment_id' => $invoice->getIncrementId(),
                'sub_total' => (float)$invoice->getSubtotal(),
                'grand_total' => (float)$invoice->getGrandTotal(),
                'shipping_amount' => (float)$invoice->getShippingAmount(),
                'shipping_incl_tax' => (float)$invoice->getShippingInclTax(),
                'discount_amount' => (float)$invoice->getDiscountAmount(),
                'tax_amount' => (float)$invoice->getTaxAmount(),
                'items' => []
            ];
            foreach ($invoice->getAllItems() as $item) {
                if ($item->getOrderItem()->getParentItem()) {
                    continue;
                }
                /** @var Invoice\Item $item */
                $data['items'][] = [
                    'name' => $item->getName(),
                    'sku' => $item->getSku(),
                    'price' => (float)$item->getPriceInclTax(),
                    'qty' => (int)$item->getQty(),
                    'row_total' => (float)$item->getRowTotalInclTax(),
                    'options' => $this->getItemOptions($item)
                ];
            }
            $invoices[] = $data;
        }
        return $invoices ?: null;
    }

    /**
     * @param Order $order
     * @return |null
     */
    public function getShipments(Order $order)
    {
        $shipments = [];
        foreach ($order->getShipmentsCollection() as $shipment) {
            /** @var Shipment $shipment */
            $data = [
                'id' => $shipment->getId(),
                'increment_id' => $shipment->getIncrementId(),
                'items' => []
            ];
            foreach ($shipment->getAllItems() as $item) {
                /** @var Shipment\Item $item */
                if ($item->getOrderItem()->getParentItem()) {
                    continue;
                }
                $data['items'][] = [
                    'name' => $item->getName(),
                    'sku' => $item->getSku(),
                    'qty' => (int)$item->getQty(),
                    'options' => $this->getItemOptions($item)
                ];
            }
            $shipments[] = $data;
        }
        return $shipments ?: null;
    }

    /**
     * @param Order $order
     * @return |null
     */
    public function getCreditmemos(Order $order)
    {
        $creditmemos = [];
        foreach ($order->getCreditmemosCollection() as $creditmemo) {
            /** @var Creditmemo $creditmemo */
            $data = [
                'id' => $creditmemo->getId(),
                'increment_id' => $creditmemo->getIncrementId(),
                'sub_total' => (float)$creditmemo->getSubtotal(),
                'grand_total' => (float)$creditmemo->getGrandTotal(),
                'shipping_amount' => (float)$creditmemo->getShippingAmount(),
                'shipping_incl_tax' => (float)$creditmemo->getShippingInclTax(),
                'discount_amount' => (float)$creditmemo->getDiscountAmount(),
                'tax_amount' => (float)$creditmemo->getTaxAmount(),
                'items' => []
            ];
            foreach ($creditmemo->getAllItems() as $item) {
                /** @var Creditmemo\Item $item */
                if ($item->getOrderItem()->getParentItem()) {
                    continue;
                }
                $data['items'][] = [
                    'name' => $item->getName(),
                    'sku' => $item->getSku(),
                    'price' => (float)$item->getPriceInclTax(),
                    'qty' => (int)$item->getQty(),
                    'sub_total' => (float)$item->getRowTotalInclTax(),
                    'discount' => (float)$item->getDiscountAmount(),
                    'row_total' => (float)$item->getRowTotalInclTax() - $item->getDiscountAmount(),
                    'options' => $this->getItemOptions($item)
                ];
            }
            $creditmemos[] = $data;
        }
        return $creditmemos ?: null;
    }
}
