<?php
/**
 * @author    Tigren Solutions <info@tigren.com>
 * @copyright Copyright (c) 2019 Tigren Solutions <https://www.tigren.com>. All rights reserved.
 * @license   Open Software License ("OSL") v. 3.0
 */

declare(strict_types=1);

namespace Tigren\CustomerGraphQl\Model\Resolver\Mutation;

use Exception;
use Magento\Checkout\Model\Cart;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\CustomerGraphQl\Model\Customer\GetCustomer;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlAuthenticationException;
use Magento\Framework\GraphQl\Exception\GraphQlAuthorizationException;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Exception\GraphQlNoSuchEntityException;
use Magento\Framework\GraphQl\Query\Resolver\Value;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Framework\ObjectManagerInterface;
use Magento\GraphQl\Model\Query\ContextInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Controller\AbstractController\OrderViewAuthorization;
use Magento\Sales\Model\Order;

/**
 * Class Reorder
 * @package Tigren\CustomerGraphQl\Model\Resolver\Mutation
 */
class Reorder implements ResolverInterface
{
    /**
     * @var OrderRepositoryInterface
     */
    protected $orderRepository;

    /**
     * @var ObjectManagerInterface
     */
    protected $_objectManager;

    /**
     * @var OrderViewAuthorization
     */
    protected $orderAuthorization;

    /**
     * @var CustomerSession
     */
    protected $customerSession;

    /**
     * @var GetCustomer
     */
    private $getCustomer;

    /**
     * Reorder constructor.
     * @param ObjectManagerInterface $objectManager
     * @param OrderViewAuthorization $orderAuthorization
     * @param OrderRepositoryInterface $orderRepository
     * @param CustomerSession $customerSession
     * @param GetCustomer $getCustomer
     */
    public function __construct(
        ObjectManagerInterface $objectManager,
        OrderViewAuthorization $orderAuthorization,
        OrderRepositoryInterface $orderRepository,
        CustomerSession $customerSession,
        GetCustomer $getCustomer
    ) {
        $this->orderAuthorization = $orderAuthorization;
        $this->_objectManager = $objectManager;
        $this->orderRepository = $orderRepository;
        $this->customerSession = $customerSession;
        $this->getCustomer = $getCustomer;
    }

    /**
     * @param Field $field
     * @param \Magento\Framework\GraphQl\Query\Resolver\ContextInterface $context
     * @param ResolveInfo $info
     * @param array|null $value
     * @param array|null $args
     * @return bool|Value|mixed
     * @throws GraphQlAuthorizationException
     * @throws GraphQlInputException
     * @throws GraphQlAuthenticationException
     * @throws GraphQlNoSuchEntityException
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
        $this->customerSession->setCustomerId($customer->getId());
        $orderId = $args['order'];
        /** @var Order $order */
        $order = $this->orderRepository->get($orderId);

        if (!$this->orderAuthorization->canView($order)) {
            return false;
        }
        /* @var $cart Cart */
        $cart = $this->_objectManager->get(Cart::class);
        $items = $order->getItemsCollection();
        foreach ($items as $item) {
            try {
                $cart->addOrderItem($item);
            } catch (LocalizedException $e) {
                throw new Exception($e->getMessage());
            } catch (Exception $e) {
                throw new Exception('We can\'t add this item to your shopping cart right now.');
            }
        }
        $cart->save();
        return true;
    }
}