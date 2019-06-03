<?php
/**
 * @author Tigren Solutions <info@tigren.com>
 * @copyright Copyright (c) 2019 Tigren Solutions <https://www.tigren.com>. All rights reserved.
 * @license Open Software License ("OSL") v. 3.0
 */
declare(strict_types=1);

namespace Tigren\CustomerGraphQl\Model\Resolver;

use Magento\Customer\Api\AddressRepositoryInterface;
use Magento\Customer\Model\Customer;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory;

class CustomerOrder implements ResolverInterface
{
    /**
     * @var TimezoneInterface
     */
    protected $_localeDate;
    /**
     * @var CollectionFactory
     */
    private $orderCollectionFactory;
    /**
     * @var AddressRepositoryInterface
     */
    private $addressRepository;

    /**
     * @param CollectionFactory $orderCollectionFactory
     * @param AddressRepositoryInterface $addressRepository
     * @param TimezoneInterface $localeDate
     */
    public function __construct(
        CollectionFactory $orderCollectionFactory,
        AddressRepositoryInterface $addressRepository,
        TimezoneInterface $localeDate
    ) {
        $this->orderCollectionFactory = $orderCollectionFactory;
        $this->addressRepository = $addressRepository;
        $this->_localeDate = $localeDate;
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
        if (!isset($value['id'])) {
            throw new LocalizedException(__('"id" value should be specified'));
        }
        /** @var Customer $customer */
        $customerId = $value['id'];

        $orderCollection = $this->orderCollectionFactory->create()->addFieldToSelect(
            '*'
        )->addFieldToFilter(
            'customer_id',
            $customerId
        )->setOrder(
            'created_at',
            'desc'
        );
        $ordersData = [];
        foreach ($orderCollection as $order) {
            $shippingAddress = $order->getShippingAddress();
            $billingAddress = $order->getBillingAddress();
            $items = $order->getAllVisibleItems();
            $data = $order->getData();
            $data['created_at'] = !empty($data['created_at']) ? $this->formatDate($data['created_at']) : '';
            $data['payment_method'] = $this->getPaymentMethod($order);
            $data['shipping_address'] = $this->getAddressData($shippingAddress);
            $data['billing_address'] = $this->getAddressData($billingAddress);
            $data['items'] = $this->getItemsData($items);
            $ordersData[] = $data;
        }
        return $ordersData ?: null;
    }

    /**
     * Retrieve formatting date
     *
     * @param null|string|\DateTimeInterface $date
     * @param int $format
     * @param bool $showTime
     * @param null|string $timezone
     * @return string
     */
    public function formatDate(
        $date = null,
        $format = \IntlDateFormatter::SHORT,
        $showTime = false,
        $timezone = null
    ) {
        $date = $date instanceof \DateTimeInterface ? $date : new \DateTime($date);
        return $this->_localeDate->formatDateTime(
            $date,
            $format,
            $showTime ? $format : \IntlDateFormatter::NONE,
            null,
            $timezone
        );
    }

    private function getPaymentMethod($order)
    {
        return $order->getPayment()->getMethodInstance()->getTitle();
    }

    private function getAddressData($address)
    {
        $data = $address ? $address->getData() : null;

        $data['id'] = $data['entity_id'];
        unset($data['entity_id']);
        return $data;
    }

    private function getItemsData($items)
    {
        $data = [];
        foreach ($items as $item) {
            $itemData = $item->getData();
            $data[] = [
                'id' => $itemData['item_id'],
                'sku' => $itemData['sku'],
                'price' => (float)$itemData['price_incl_tax'],
                'subtotal' => (float)$itemData['row_total_incl_tax'],
                'quantity' => (int)$itemData['qty_ordered'],
                'name' => $itemData['name'],
            ];
        }
        return $data;
    }
}
