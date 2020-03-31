<?php
/**
 * @author    Tigren Solutions <info@tigren.com>
 * @copyright Copyright (c) 2019 Tigren Solutions <https://www.tigren.com>. All rights reserved.
 * @license   Open Software License ("OSL") v. 3.0
 */
declare(strict_types=1);

namespace Tigren\NewsletterGraphQl\Model\GraphQl\Resolver;

use Exception;
use Magento\Authorization\Model\UserContextInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Query\Resolver\ContextInterface;
use Magento\Framework\GraphQl\Query\Resolver\Value;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Framework\ObjectManagerInterface;
use Magento\Newsletter\Model\Subscriber;
use Magento\Newsletter\Model\SubscriberFactory;
use Magento\Framework\Validator\EmailAddress as EmailValidator;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Framework\GraphQl\Exception\GraphQlAuthorizationException;
use Magento\Customer\Api\AccountManagementInterface as CustomerAccountManagement;
use Magento\Framework\Phrase;
use Magento\Store\Model\StoreManagerInterface;

/**
 * @inheritdoc
 */
class Subscribe implements ResolverInterface
{
    /**
     * Subscriber factory
     *
     * @var SubscriberFactory
     */
    protected $_subscriberFactory;
    /**
     * @var ObjectManagerInterface
     */
    protected $_objectManager;
    /**
     * @var StoreManagerInterface
     */
    protected $_storeManager;
    /**
     * @var UserContextInterface
     */
    protected $userContext;
    /**
     * @var CustomerRepositoryInterface
     */
    protected $_customerRepositoryInterface;
    /**
     * @var CustomerAccountManagement
     */
    protected $customerAccountManagement;
    /**
     * @var EmailValidator
     */
    private $emailValidator;

    /**
     * Subscribe constructor.
     * @param SubscriberFactory $subscriberFactory
     * @param ObjectManagerInterface $objectManager
     * @param StoreManagerInterface $storeManager
     * @param UserContextInterface $userContext
     * @param CustomerRepositoryInterface $customerRepositoryInterface
     * @param CustomerAccountManagement $customerAccountManagement
     * @param EmailValidator|null $emailValidator
     */
    public function __construct(
        SubscriberFactory $subscriberFactory,
        ObjectManagerInterface $objectManager,
        StoreManagerInterface $storeManager,
        UserContextInterface $userContext,
        CustomerRepositoryInterface $customerRepositoryInterface,
        CustomerAccountManagement $customerAccountManagement,
        EmailValidator $emailValidator = null
    ) {
        $this->_subscriberFactory = $subscriberFactory;
        $this->emailValidator = $emailValidator ?: ObjectManager::getInstance()->get(EmailValidator::class);
        $this->_objectManager = $objectManager;
        $this->_storeManager = $storeManager;
        $this->userContext = $userContext;
        $this->_customerRepositoryInterface = $customerRepositoryInterface;
        $this->customerAccountManagement = $customerAccountManagement;
    }

    /**
     * @param Field $field
     * @param ContextInterface $context
     * @param ResolveInfo $info
     * @param array|null $value
     * @param array|null $args
     * @return array|Value|mixed
     * @throws GraphQlInputException
     */
    public function resolve(Field $field, $context, ResolveInfo $info, array $value = null, array $args = null)
    {
        if (!isset($args['email'])) {
            throw new GraphQlInputException(__('"email" value should be specified'));
        }
        $result = [
            'success' => true,
            'message' => ''
        ];

        $email = $args['email'];

        try {
            if (!$this->emailValidator->isValid($email)) {
                throw new LocalizedException(__('Please enter a valid email address.'));
            }

            if ($this->_objectManager->get(ScopeConfigInterface::class)
                    ->getValue(
                        Subscriber::XML_PATH_ALLOW_GUEST_SUBSCRIBE_FLAG,
                        ScopeInterface::SCOPE_STORE
                    ) != 1
                && (false === $context->getExtensionAttributes()->getIsCustomer())
            ) {
                throw new GraphQlAuthorizationException(
                    __(
                        'Sorry, but the administrator denied subscription for guests. Please register'
                    )
                );
            }

            $websiteId = $this->_storeManager->getStore()->getWebsiteId();
            $customerId = $this->userContext->getUserId();
            if ($customerId) {
                $customer = $this->_customerRepositoryInterface->getById($customerId);
                if ($customer->getEmail() != $email && !$this->customerAccountManagement->isEmailAvailable($email,
                        $websiteId)) {
                    throw new LocalizedException(
                        __('This email address is already assigned to another user.')
                    );
                }
            }

            $subscriber = $this->_subscriberFactory->create()->loadByEmail($email);
            if ($subscriber->getId()
                && (int)$subscriber->getSubscriberStatus() === Subscriber::STATUS_SUBSCRIBED
            ) {
                throw new LocalizedException(
                    __('This email address is already subscribed.')
                );
            }

            $status = (int)$this->_subscriberFactory->create()->subscribe($email);
            $result['message'] = $this->getSuccessMessage($status);
        } catch (LocalizedException $e) {
            $result = [
                'success' => false,
                'message' => $e->getMessage()
            ];
        } catch (Exception $e) {
            $result = [
                'success' => false,
                'message' => 'Something went wrong with the subscription.'
            ];
        }

        return $result;
    }

    /**
     * Get success message
     *
     * @param int $status
     * @return Phrase
     */
    private function getSuccessMessage(int $status): Phrase
    {
        if ($status === Subscriber::STATUS_NOT_ACTIVE) {
            return __('The confirmation request has been sent.');
        }

        return __('Thank you for your subscription.');
    }
}
