<?php
/**
 * @author    Tigren Solutions <info@tigren.com>
 * @copyright Copyright (c) 2019 Tigren Solutions <https://www.tigren.com>. All rights reserved.
 * @license   Open Software License ("OSL") v. 3.0
 */
declare(strict_types=1);

namespace Tigren\CustomerGraphQl\Model\Resolver;

use Magento\Authorization\Model\UserContextInterface;
use Magento\Customer\Model\Address;
use Magento\Customer\Model\ResourceModel\Address\Collection;
use Magento\Customer\Model\ResourceModel\Address\CollectionFactory as AddressCollectionFactory;
use Magento\CustomerGraphQl\Model\Customer\Address\ExtractCustomerAddressData;
use Magento\CustomerGraphQl\Model\Customer\GetCustomer;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlAuthorizationException;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\GraphQl\Model\Query\ContextInterface;
use Magento\Customer\Api\Data\CustomerInterface;

/**
 * Class CustomerAddresses
 * @package Tigren\CustomerGraphQl\Model\Resolver
 */
class CustomerAddresses implements ResolverInterface
{
    /**
     * @var UserContextInterface
     */
    protected $userContext;
    /**
     * @var ExtractCustomerAddressData
     */
    private $extractCustomerAddressData;
    /**
     * @var AddressCollectionFactory
     */
    private $addressCollectionFactory;
    /**
     * @var GetCustomer
     */
    private $getCustomer;

    /**
     * CustomerAddresses constructor.
     * @param ExtractCustomerAddressData $extractCustomerAddressData
     * @param AddressCollectionFactory $addressCollectionFactory
     * @param UserContextInterface $userContext
     * @param GetCustomer $getCustomer
     */
    public function __construct(
        ExtractCustomerAddressData $extractCustomerAddressData,
        AddressCollectionFactory $addressCollectionFactory,
        UserContextInterface $userContext,
        GetCustomer $getCustomer
    ) {
        $this->extractCustomerAddressData = $extractCustomerAddressData;
        $this->addressCollectionFactory = $addressCollectionFactory;
        $this->userContext = $userContext;
        $this->getCustomer = $getCustomer;
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
        if ($args['currentPage'] < 1) {
            throw new GraphQlInputException(__('currentPage value must be greater than 0.'));
        }
        if ($args['pageSize'] < 1) {
            throw new GraphQlInputException(__('pageSize value must be greater than 0.'));
        }
        $customer = $this->getCustomer->execute($context);
        $collection = $this->getAddressCollection($customer);
        $addressesData = [];
        $totalCount = $collection->getSize();
        if (!$totalCount) {
            return [];
        }
        $pageSize = $args['pageSize'];
        $curPage = $args['currentPage'];
        $pageInfo = [
            'page_size' => $pageSize,
            'current_page' => $curPage,
            'total_pages' => ceil($totalCount / $pageSize)
        ];
        $collection->setPageSize($pageSize)->setCurPage($curPage);
        /** @var Address $address */
        foreach ($collection as $address) {
            $addressesData[] = $this->extractCustomerAddressData->execute($address->getDataModel());
        }
        $data = [
            'total_count' => $totalCount,
            'items' => $addressesData ?: null,
            'page_info' => $pageInfo
        ];
        return $data;
    }

    /**
     * @param CustomerInterface $customer
     * @return Collection
     * @throws LocalizedException
     */
    private function getAddressCollection(CustomerInterface $customer
    ): Collection {
        /** @var Collection $collection */
        $collection = $this->addressCollectionFactory->create();
        $collection->setOrder('entity_id', 'desc');
        $collection->addFieldToFilter(
            'entity_id',
            ['nin' => [$customer->getDefaultShipping(), $customer->getDefaultBilling()]]
        );
        $collection->addAttributeToFilter('parent_id', $customer->getId());
        return $collection;
    }
}
