<?php
/**
 * @author    Tigren Solutions <info@tigren.com>
 * @copyright Copyright (c) 2019 Tigren Solutions <https://www.tigren.com>. All rights reserved.
 * @license   Open Software License ("OSL") v. 3.0
 */
declare(strict_types=1);

namespace Tigren\NewsletterGraphQl\Model\GraphQl\Resolver\Customer;

use Exception;
use Magento\Customer\Api\CustomerRepositoryInterface as CustomerRepository;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\CustomerGraphQl\Model\Customer\GetCustomer;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Exception\GraphQlAuthorizationException;
use Magento\Newsletter\Model\Subscriber;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Framework\ObjectManagerInterface;

/**
 * @inheritdoc
 */
class Subscribe implements ResolverInterface
{
    /**
     * @var GetCustomer
     */
    private $getCustomer;

    /**
     * @var \Magento\Newsletter\Model\SubscriberFactory
     */
    protected $subscriberFactory;

    /**
     * @var CustomerRepository
     */
    protected $customerRepository;

    /**
     * Subscribe constructor.
     * @param \Magento\Newsletter\Model\SubscriberFactory $subscriberFactory
     * @param CustomerRepository $customerRepository
     * @param GetCustomer $getCustomer
     */
    public function __construct(
        \Magento\Newsletter\Model\SubscriberFactory $subscriberFactory,
        CustomerRepository $customerRepository,
        GetCustomer $getCustomer
    ) {
        $this->getCustomer = $getCustomer;
        $this->customerRepository = $customerRepository;
        $this->subscriberFactory = $subscriberFactory;
    }


    /**
     * @param Field $field
     * @param \Magento\Framework\GraphQl\Query\Resolver\ContextInterface $context
     * @param ResolveInfo $info
     * @param array|null $value
     * @param array|null $args
     * @return \Magento\Framework\GraphQl\Query\Resolver\Value|mixed|void
     * @throws GraphQlAuthorizationException
     * @throws GraphQlInputException
     */
    public function resolve(Field $field, $context, ResolveInfo $info, array $value = null, array $args = null)
    {
        /** @var \Magento\GraphQl\Model\Query\ContextInterface $context */
        if (false === $context->getExtensionAttributes()->getIsCustomer()) {
            throw new GraphQlAuthorizationException(__('The current customer isn\'t authorized.'));
        }
        if (!isset($args['is_subscribed'])) {
            throw new GraphQlInputException(__('Specify the value.'));
        }
        $result = [
            'success' => true,
            'message' => ''
        ];

        try {
            $customer = $this->getCustomer->execute($context);
            $customerId = $customer->getId();
            $isSubscribedState = $customer->getExtensionAttributes()
                ->getIsSubscribed();
            $isSubscribedParam = (boolean)$args['is_subscribed'];
            if ($isSubscribedParam !== $isSubscribedState) {
                // No need to validate customer and customer address while saving subscription preferences
                $this->setIgnoreValidationFlag($customer);
                $this->customerRepository->save($customer);
                if ($isSubscribedParam) {
                    $subscribeModel = $this->subscriberFactory->create()
                        ->subscribeCustomerById($customerId);
                    $subscribeStatus = $subscribeModel->getStatus();
                    if ($subscribeStatus == Subscriber::STATUS_SUBSCRIBED) {
                        $result = [
                            'success' => true,
                            'message' => 'We have saved your subscription.'
                        ];
                    } else {
                        $result = [
                            'success' => true,
                            'message' => 'A confirmation request has been sent.'
                        ];
                    }
                } else {
                    $this->subscriberFactory->create()
                        ->unsubscribeCustomerById($customerId);
                    $result = [
                        'success' => true,
                        'message' => 'We have removed your newsletter subscription.'
                    ];
                }
            } else {
                $result = [
                    'success' => true,
                    'message' => 'We have updated your subscription.'
                ];
            }
        } catch (\Exception $e) {
            $result = [
                'success' => false,
                'message' => 'Something went wrong while saving your subscription.'
            ];
        }
        return $result;
    }

    /**
     * Set ignore_validation_flag to skip unnecessary address and customer validation
     *
     * @param CustomerInterface $customer
     * @return void
     */
    private function setIgnoreValidationFlag(CustomerInterface $customer): void
    {
        $customer->setData('ignore_validation_flag', true);
    }
}
