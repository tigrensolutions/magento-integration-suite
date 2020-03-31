<?php
/**
 * @author    Tigren Solutions <info@tigren.com>
 * @copyright Copyright (c) 2019 Tigren Solutions <https://www.tigren.com>. All rights reserved.
 * @license   Open Software License ("OSL") v. 3.0
 */
declare(strict_types=1);

namespace Tigren\CustomerGraphQl\Model\Resolver\Sales\Guest;

use DateTime;
use DateTimeInterface;
use Exception;
use IntlDateFormatter;
use Magento\Customer\Api\AddressRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Escaper;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order\Creditmemo;
use Magento\Sales\Model\Order\Invoice;
use Magento\Sales\Model\Order\Item;
use Magento\Sales\Model\Order\Payment;
use Magento\Sales\Model\Order\Shipment;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory;
use Magento\Store\Model\StoreManagerInterface;

/**
 * @inheritdoc
 */
class Order implements ResolverInterface
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
     * @var OrderRepositoryInterface
     */
    private $orderRepository;
    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;
    /**
     * @var StoreManagerInterface
     */
    private $storeManager;
    /**
     * @var AddressRepositoryInterface
     */
    private $addressRepository;

    /**
     * @var SerializerInterface
     */
    private $jsonSerializer;

    /**
     * Order constructor.
     * @param StoreManagerInterface $storeManager
     * @param AddressRepositoryInterface $addressRepository
     * @param TimezoneInterface $localeDate
     * @param SerializerInterface $jsonSerializer
     * @param Escaper $_escaper
     * @param OrderRepositoryInterface|null $orderRepository
     * @param SearchCriteriaBuilder|null $searchCriteria
     */
    public function __construct(
        StoreManagerInterface $storeManager,
        AddressRepositoryInterface $addressRepository,
        TimezoneInterface $localeDate,
        SerializerInterface $jsonSerializer,
        Escaper $_escaper,
        OrderRepositoryInterface $orderRepository = null,
        SearchCriteriaBuilder $searchCriteria = null
    ) {
        $this->storeManager = $storeManager;
        $this->addressRepository = $addressRepository;
        $this->_localeDate = $localeDate;
        $this->jsonSerializer = $jsonSerializer;
        $this->_escaper = $_escaper;
        $this->orderRepository = $orderRepository ?: ObjectManager::getInstance()
            ->get(OrderRepositoryInterface::class);
        $this->searchCriteriaBuilder = $searchCriteria ?: ObjectManager::getInstance()
            ->get(SearchCriteriaBuilder::class);
    }

    /**
     * @inheritdoc
     */
    public function resolve(Field $field, $context, ResolveInfo $info, array $value = null, array $args = null)
    {
        // It is unique place in the class that process exception and only InputException. It is need because by
        // input data we found order and one more InputException could be throws deeper in stack trace
        try {
            if (!empty($args)
                && isset($args['oar_order_id'], $args['oar_type'])
                && !$this->hasPostDataEmptyFields($args)) {
                $order = $this->loadFromPost($args);

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

                return $data;
            } else {
                throw new GraphQlInputException(__('You entered incorrect data. Please try again.'));
            }
        } catch (InputException $e) {
            throw new LocalizedException(__('The requested order was not found.'));
        }
    }

    /**
     * Check post data for empty fields
     *
     * @param array $postData
     * @return bool
     * @throws NoSuchEntityException
     */
    private function hasPostDataEmptyFields(array $postData)
    {
        return empty($postData['oar_order_id']) || empty($postData['oar_billing_lastname']) ||
            empty($postData['oar_type']) || empty($this->storeManager->getStore()->getId()) ||
            !in_array($postData['oar_type'], ['email', 'zip'], true) ||
            ('email' === $postData['oar_type'] && empty($postData['oar_email'])) ||
            ('zip' === $postData['oar_type'] && empty($postData['oar_zip']));
    }

    /**
     * Load order data from post
     *
     * @param array $postData
     * @return Order
     * @throws InputException
     * @throws CookieSizeLimitReachedException
     * @throws FailureToSendException
     */
    private function loadFromPost(array $postData)
    {
        /** @var $order \Magento\Sales\Model\Order */
        $order = $this->getOrderRecord($postData['oar_order_id']);
        if (!$this->compareStoredBillingDataWithInput($order, $postData)) {
            throw new GraphQlInputException(__('You entered incorrect data. Please try again.'));
        }
        return $order;
    }

    /**
     * Get order by increment_id and store_id
     *
     * @param string $incrementId
     * @return OrderInterface
     * @throws GraphQlInputException
     * @throws NoSuchEntityException
     */
    private function getOrderRecord($incrementId)
    {
        $records = $this->orderRepository->getList(
            $this->searchCriteriaBuilder
                ->addFilter('increment_id', $incrementId)
                ->addFilter('store_id', $this->storeManager->getStore()->getId())
                ->create()
        );

        $items = $records->getItems();
        if (empty($items)) {
            throw new GraphQlInputException(__('You entered incorrect data. Please try again.'));
        }

        return array_shift($items);
    }

    /**
     * Check that billing data from the order and from the input are equal
     *
     * @param \Magento\Sales\Model\Order $order
     * @param array $postData
     * @return bool
     */
    private function compareStoredBillingDataWithInput(
        \Magento\Sales\Model\Order $order,
        array $postData
    ) {
        $type = $postData['oar_type'];
        $email = $postData['oar_email'];
        $lastName = $postData['oar_billing_lastname'];
        $zip = $postData['oar_zip'];
        $billingAddress = $order->getBillingAddress();
        return strtolower($lastName) === strtolower($billingAddress->getLastname()) &&
            ($type === 'email' && strtolower($email) === strtolower($billingAddress->getEmail()) ||
                $type === 'zip' && strtolower($zip) === strtolower($billingAddress->getPostcode()));
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
        if (!($item instanceof Item)) {
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
     * @param \Magento\Sales\Model\Order $order
     * @return |null
     */
    public function getInvoices(\Magento\Sales\Model\Order $order)
    {
        $invoices = [];
        foreach ($order->getInvoiceCollection() as $invoice) {
            /** @var Invoice $invoice */
            $data = [
                'id' => $invoice->getId(),
                'increment_id' => $invoice->getIncrementId(),
                'sub_total' => $invoice->getSubtotalInclTax(),
                'grand_total' => $invoice->getGrandTotal(),
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
     * @param \Magento\Sales\Model\Order $order
     * @return |null
     */
    public function getShipments(\Magento\Sales\Model\Order $order)
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
     * @param \Magento\Sales\Model\Order $order
     * @return |null
     */
    public function getCreditmemos(\Magento\Sales\Model\Order $order)
    {
        $creditmemos = [];
        foreach ($order->getCreditmemosCollection() as $creditmemo) {
            /** @var Creditmemo $creditmemo */
            $data = [
                'id' => $creditmemo->getId(),
                'increment_id' => $creditmemo->getIncrementId(),
                'sub_total' => (float)$creditmemo->getSubtotalInclTax(),
                'grand_total' => (float)$creditmemo->getGrandTotal(),
                'items' => []
            ];
            foreach ($creditmemo->getAllItems() as $item) {
                /** @var Creditmemo\Item $item */
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
