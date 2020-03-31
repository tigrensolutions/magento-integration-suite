<?php
/**
 * @author    Tigren Solutions <info@tigren.com>
 * @copyright Copyright (c) 2019 Tigren Solutions <https://www.tigren.com>. All rights reserved.
 * @license   Open Software License ("OSL") v. 3.0
 */
declare(strict_types=1);

namespace Tigren\CustomerGraphQl\Model\Resolver\Mutation;

use Magento\Customer\Api\Data\AddressInterfaceFactory;
use Magento\Customer\Api\Data\RegionInterfaceFactory;
use Magento\CustomerGraphQl\Model\Customer\GetCustomer;
use Magento\Framework\DataObject\Copy;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlAuthorizationException;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\GraphQl\Model\Query\ContextInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Customer\Api\AddressRepositoryInterface;
use Magento\Sales\Model\Order\Address as OrderAddress;

/**
 * Class AssignOrder
 * @package Tigren\CustomerGraphQl\Model\Resolver\Mutation
 */
class AssignOrder implements ResolverInterface
{
    /**
     * @var Copy
     */
    protected $_objectCopyService;

    /**
     * @var AddressInterfaceFactory
     */
    protected $addressFactory;

    /**
     * @var RegionInterfaceFactory
     */
    protected $regionFactory;

    /**
     * @var GetCustomer
     */
    private $getCustomer;

    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * @var AddressRepositoryInterface
     */
    private $addressRepository;

    /**
     * AssignOrder constructor.
     * @param GetCustomer $getCustomer
     * @param Copy $copy
     * @param OrderRepositoryInterface $orderRepository
     * @param AddressInterfaceFactory $addressFactory
     * @param RegionInterfaceFactory $regionFactory
     * @param AddressRepositoryInterface $addressRepository
     */
    public function __construct(
        GetCustomer $getCustomer,
        Copy $copy,
        OrderRepositoryInterface $orderRepository,
        AddressInterfaceFactory $addressFactory,
        RegionInterfaceFactory $regionFactory,
        AddressRepositoryInterface $addressRepository
    ) {
        $this->getCustomer = $getCustomer;
        $this->_objectCopyService = $copy;
        $this->orderRepository = $orderRepository;
        $this->addressFactory = $addressFactory;
        $this->regionFactory = $regionFactory;
        $this->addressRepository = $addressRepository;
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
        /** @var ContextInterface $context */
        if (false === $context->getExtensionAttributes()->getIsCustomer()) {
            throw new GraphQlAuthorizationException(__('The current customer isn\'t authorized.'));
        }
        if (!isset($args['order'])) {
            throw new GraphQlInputException(__('Specify the "order" value.'));
        }
        $customer = $this->getCustomer->execute($context);
        /** @var  $order */
        $order = $this->orderRepository->get($args['order']);
        if (!$order->getCustomerId()) {
            //if customer ID wasn't already assigned then assigning.
            $order->setCustomerId($customer->getId());
            $order->setCustomerIsGuest(0);
            $this->orderRepository->save($order);
        }
        $addresses = $order->getAddresses();
        $processedAddressData = [];
        $customerAddresses = [];
        foreach ($addresses as $orderAddress) {
            $addressData = $this->_objectCopyService
                ->copyFieldsetToTarget('order_address', 'to_customer_address', $orderAddress, []);

            $index = array_search($addressData, $processedAddressData);
            if ($index === false) {
                // create new customer address only if it is unique
                $customerAddress = $this->addressFactory->create(['data' => $addressData]);
                $customerAddress->setIsDefaultBilling(false);
                $customerAddress->setIsDefaultShipping(false);
                if (is_string($orderAddress->getRegion())) {
                    /** @var RegionInterface $region */
                    $region = $this->regionFactory->create();
                    $region->setRegion($orderAddress->getRegion());
                    $region->setRegionCode($orderAddress->getRegionCode());
                    $region->setRegionId($orderAddress->getRegionId());
                    $customerAddress->setRegion($region);
                }

                $processedAddressData[] = $addressData;
                $customerAddresses[] = $customerAddress;
                $index = count($processedAddressData) - 1;
            }

            $customerAddress = $customerAddresses[$index];
            // make sure that address type flags from equal addresses are stored in one resulted address
            if ($orderAddress->getAddressType() == OrderAddress::TYPE_BILLING) {
                $customerAddress->setIsDefaultBilling(true);
            }
            if ($orderAddress->getAddressType() == OrderAddress::TYPE_SHIPPING) {
                $customerAddress->setIsDefaultShipping(true);
            }
            $customerAddress->setCustomerId($customer->getId());
        }
        foreach ($customerAddresses as $address) {
            try {
                $this->addressRepository->save($address);
            } catch (LocalizedException $e) {
                throw new GraphQlInputException(__($e->getMessage()), $e);
            }
        }

        return true;
    }
}
