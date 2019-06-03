<?php
/**
 * @author Tigren Solutions <info@tigren.com>
 * @copyright Copyright (c) 2019 Tigren Solutions <https://www.tigren.com>. All rights reserved.
 * @license Open Software License ("OSL") v. 3.0
 */
declare(strict_types=1);

namespace Tigren\CustomerGraphQl\Model\Resolver\Mutation;

use Magento\CustomerGraphQl\Model\Customer\CheckCustomerAccount;
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
     * @var CheckCustomerAccount
     */
    private $checkCustomerAccount;

    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * AssignOrder constructor.
     * @param CheckCustomerAccount $checkCustomerAccount
     * @param OrderRepositoryInterface $orderRepository
     */
    public function __construct(
        CheckCustomerAccount $checkCustomerAccount,
        OrderRepositoryInterface $orderRepository
    ) {
        $this->checkCustomerAccount = $checkCustomerAccount;
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
        $this->checkCustomerAccount->execute($currentUserId, $currentUserType);
        $currentUserId = (int)$currentUserId;

        $order = $this->orderRepository->get($args['order']);
        if (!$order->getCustomerId()) {
            //if customer ID wasn't already assigned then assigning.
            $order->setCustomerId($currentUserId);
            $order->setCustomerIsGuest(0);
            $this->orderRepository->save($order);
        }
        return true;
    }

}
