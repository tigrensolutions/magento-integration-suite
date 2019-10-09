<?php
/**
 * @author Tigren Solutions <info@tigren.com>
 * @copyright Copyright (c) 2019 Tigren Solutions <https://www.tigren.com>. All rights reserved.
 * @license Open Software License ("OSL") v. 3.0
 */
declare(strict_types=1);

namespace Tigren\CustomerGraphQl\Model\Resolver\Mutation;

use Magento\CustomerGraphQl\Model\Customer\GetCustomer;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Sales\Api\OrderRepositoryInterface;

/**
 * Class AssignOrder
 * @package Tigren\CustomerGraphQl\Model\Resolver\Mutation
 */
class AssignOrder implements ResolverInterface
{
    /**
     * @var GetCustomer
     */
    private $getCustomer;

    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * AssignOrder constructor.
     * @param GetCustomer $getCustomer
     * @param OrderRepositoryInterface $orderRepository
     */
    public function __construct(
        GetCustomer $getCustomer,
        OrderRepositoryInterface $orderRepository
    ) {
        $this->getCustomer = $getCustomer;
        $this->orderRepository = $orderRepository;
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
        if (!isset($args['order'])) {
            throw new GraphQlInputException(__('Specify the "order" value.'));
        }
        $currentUserId = $context->getUserId();
        $currentUserType = $context->getUserType();
        $customer = $this->getCustomer->execute($currentUserId, $currentUserType);
        $currentUserId = (int)$currentUserId;

        $order = $this->orderRepository->get($args['order']);
        if (!$order->getCustomerId()) {
            //if customer ID wasn't already assigned then assigning.
            $order->setCustomerId($customer->getId());
            $order->setCustomerIsGuest(0);
            $this->orderRepository->save($order);
        }
        return true;
    }

}
