<?php
/**
 * @author Tigren Solutions <info@tigren.com>
 * @copyright Copyright (c) 2019 Tigren Solutions <https://www.tigren.com>. All rights reserved.
 * @license Open Software License ("OSL") v. 3.0
 */

declare(strict_types=1);

namespace Tigren\CustomerGraphQl\Model\Resolver\Mutation;

use Exception;
use Magento\Checkout\Model\Cart;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\Registry;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Controller\AbstractController\OrderViewAuthorization;
use Magento\Sales\Model\Order;

class Reorder implements ResolverInterface
{
    protected $orderRepository;

    protected $_objectManager;

    /**
     * @var Registry
     */
    protected $registry;

    /**
     * @var OrderViewAuthorization
     */
    protected $orderAuthorization;

    public function __construct(
        ObjectManagerInterface $objectManager,
        OrderViewAuthorization $orderAuthorization,
        Registry $registry,
        OrderRepositoryInterface $orderRepository
    ) {
        $this->orderAuthorization = $orderAuthorization;
        $this->registry = $registry;
        $this->_objectManager = $objectManager;
        $this->orderRepository = $orderRepository;
    }

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
                return false;
            } catch (Exception $e) {
                return false;
            }
        }

        $cart->save();
        return true;
    }
}
