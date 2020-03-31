<?php
/**
 * @author    Tigren Solutions <info@tigren.com>
 * @copyright Copyright (c) 2019 Tigren Solutions <https://www.tigren.com>. All rights reserved.
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Tigren\CustomerGraphQL\Model\Customer;

use Magento\Authorization\Model\UserContextInterface;
use Magento\Catalog\Model\Product\Compare\Item;
use Magento\Customer\Api\AccountManagementInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Customer\Helper\View as CustomerViewHelper;
use Magento\Customer\Model\Customer;
use Magento\Customer\Model\CustomerRegistry;
use Magento\Customer\Model\Data\CustomerSecure;
use Magento\Customer\Model\Session;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\MailException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\State\InputMismatchException;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\HTTP\PhpEnvironment\RemoteAddress;
use Magento\Framework\Intl\DateTimeFactory;
use Magento\Framework\Mail\Template\SenderResolverInterface;
use Magento\Framework\Mail\Template\TransportBuilder;
use Magento\Framework\Math\Random;
use Magento\Framework\Reflection\DataObjectProcessor;
use Magento\Framework\Stdlib\DateTime;
use Magento\Framework\Validator\EmailAddress;
use Magento\Security\Model\Config\Source\ResetMethod;
use Magento\Security\Model\ConfigInterface;
use Magento\Security\Model\ResourceModel\PasswordResetRequestEvent\Collection;
use Magento\Security\Model\ResourceModel\PasswordResetRequestEvent\CollectionFactory;
use Magento\Security\Model\SecurityChecker\SecurityCheckerInterface;
use Magento\Security\Model\SecurityManager;
use Magento\Store\Model\StoreManager;
use Tigren\CustomerGraphQl\Api\Customer\AccountInterface;
use Tigren\CustomerGraphQl\Helper\Data;
use Zend_Validate;

/**
 * Class Account
 * @package Tigren\CustomerGraphQL\Model\Customer
 */
class Account implements AccountInterface
{
    /**
     *
     */
    const XML_PATH_FORGOT_EMAIL_TEMPLATE = 'customer/password/forgot_email_template';

    /**
     *
     */
    const XML_PATH_FORGOT_EMAIL_IDENTITY = 'customer/password/forgot_email_identity';

    /**
     * @var Session
     */
    protected $session;

    /**
     * @var Item
     */
    protected $item;

    /**
     * @var Data
     */
    protected $helper;

    /**
     * @var UserContextInterface
     */
    protected $userContext;

    /**
     * @var AccountManagementInterface
     */
    protected $customerAccountManagement;

    /**
     * @var DataObjectProcessor
     */
    protected $dataProcessor;

    /**
     * @var CustomerViewHelper
     */
    protected $customerViewHelper;

    /**
     * @var SecurityManager
     */
    protected $securityManager;

    /**
     * @var array
     */
    protected $securityCheckers;

    /**
     * @var ConfigInterface
     */
    protected $securityConfig;

    /**
     * @var CollectionFactory
     */
    protected $collectionFactory;

    /**
     * @var StoreManager
     */
    private $storeManager;

    /**
     * @var CustomerRepositoryInterface
     */
    private $customerRepository;

    /**
     * @var Random
     */
    private $mathRandom;

    /**
     * @var CustomerRegistry
     */
    private $customerRegistry;

    /**
     * @var DateTimeFactory
     */
    private $dateTimeFactory;

    /**
     * @var SenderResolverInterface
     */
    private $senderResolver;

    /**
     * @var TransportBuilder
     */
    private $transportBuilder;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var RemoteAddress
     */
    private $remoteAddress;

    /**
     * Account constructor.
     * @param Item $item
     * @param Data $helper
     * @param UserContextInterface $userContext
     * @param StoreManager $storeManager
     * @param CustomerRepositoryInterface $customerRepository
     * @param AccountManagementInterface $customerAccountManagement
     * @param Random $random
     * @param CustomerRegistry $customerRegistry
     * @param DateTimeFactory $dateTimeFactory
     * @param DataObjectProcessor $dataObjectProcessor
     * @param CustomerViewHelper $customerViewHelper
     * @param Session $customerSession
     * @param TransportBuilder $transportBuilder
     * @param SenderResolverInterface|null $senderResolver
     * @throws GraphQlInputException
     */
    public function __construct(
        Item $item,
        Data $helper,
        UserContextInterface $userContext,
        StoreManager $storeManager,
        CustomerRepositoryInterface $customerRepository,
        AccountManagementInterface $customerAccountManagement,
        Random $random,
        CustomerRegistry $customerRegistry,
        DateTimeFactory $dateTimeFactory,
        DataObjectProcessor $dataObjectProcessor,
        CustomerViewHelper $customerViewHelper,
        Session $customerSession,
        TransportBuilder $transportBuilder,
        ScopeConfigInterface $scopeConfig,
        SecurityManager $securityManager,
        RemoteAddress $remoteAddress,
        SenderResolverInterface $senderResolver = null,
        ConfigInterface $securityConfig,
        CollectionFactory $collectionFactory,
        $securityCheckers = []
    ) {
        $this->customerAccountManagement = $customerAccountManagement;
        $this->helper = $helper;
        $this->item = $item;
        $this->mathRandom = $random;
        $this->customerRepository = $customerRepository;
        $this->storeManager = $storeManager;
        $this->session = $customerSession;
        $this->userContext = $userContext;
        $this->dateTimeFactory = $dateTimeFactory;
        $this->customerRegistry = $customerRegistry;
        $this->customerViewHelper = $customerViewHelper;
        $this->dataProcessor = $dataObjectProcessor;
        $this->transportBuilder = $transportBuilder;
        $this->scopeConfig = $scopeConfig;
        $this->securityManager = $securityManager;
        $this->remoteAddress = $remoteAddress;
        $this->senderResolver = $senderResolver ?: ObjectManager::getInstance()->get(SenderResolverInterface::class);
        $this->securityCheckers = $securityCheckers;
        $this->securityConfig = $securityConfig;
        $this->collectionFactory = $collectionFactory;
        foreach ($this->securityCheckers as $checker) {
            if (!($checker instanceof SecurityCheckerInterface)) {
                throw new GraphQlInputException(__('Incorrect Security Checker class. It has to implement SecurityCheckerInterface.'));
            }
        }
    }

    /**
     * @return bool|mixed
     */
    public function logout()
    {
        $this->bindCustomerLogout($this->session->getCustomerId());
        $this->session->setCustomerId(null);
        return true;
    }

    /**
     * @param $customerId
     */
    private function bindCustomerLogout($customerId)
    {
        $connection = $this->helper->getConnection();
        $bind = ['visitor_id' => null];
        $where = ['customer_id = ?' => $customerId];
        $connection->update('catalog_compare_item', $bind, $where);
    }

    /**
     * @return bool|mixed
     */
    public function login()
    {
        $customerId = $this->userContext->getUserId() ?: null;
        if ($customerId) {
            $this->session->setCustomerId($customerId);
            $this->bindCustomerLogin($customerId, $this->session->getSessionId());
        }
        return true;
    }

    /**
     * @param $customerId
     * @param $sessionId
     */
    private function bindCustomerLogin($customerId, $sessionId)
    {
        $connection = $this->helper->getConnection();
        $visitorId = $this->helper->getVisitorId($sessionId);

        $select = $connection->select()->from(
            'catalog_compare_item'
        )->where(
            'visitor_id=?',
            $visitorId
        );
        $visitor = $connection->fetchAll($select);
        // collect customer compared items
        $select = $connection->select()->from(
            'catalog_compare_item'
        )->where(
            'customer_id = ?',
            $customerId
        )->where(
            'visitor_id != ?',
            $visitorId
        );
        $customer = $connection->fetchAll($select);

        $products = [];
        $delete = [];
        $update = [];
        foreach ($visitor as $row) {
            $products[$row['product_id']] = [
                'store_id' => $row['store_id'],
                'customer_id' => $customerId,
                'visitor_id' => $visitorId,
                'product_id' => $row['product_id'],
            ];
            $update[$row['catalog_compare_item_id']] = $row['product_id'];
        }

        foreach ($customer as $row) {
            if (isset($products[$row['product_id']])) {
                $delete[] = $row['catalog_compare_item_id'];
            } else {
                $products[$row['product_id']] = [
                    'store_id' => $row['store_id'],
                    'customer_id' => $customerId,
                    'visitor_id' => $visitorId,
                    'product_id' => $row['product_id'],
                ];
            }
        }

        if ($delete) {
            $connection->delete(
                'catalog_compare_item',
                $connection->quoteInto('catalog_compare_item_id IN(?)', $delete)
            );
        }
        if ($update) {
            foreach ($update as $itemId => $productId) {
                $bind = $products[$productId];
                $connection->update(
                    'catalog_compare_item',
                    $bind,
                    $connection->quoteInto('catalog_compare_item_id =?', $itemId)
                );
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function resetPassword($email, $baseUrl)
    {
        if (!$email) {
            throw new GraphQlInputException(__('Specify the "email" value.'));
        }

        if (!Zend_Validate::is($email, EmailAddress::class)) {
            throw new GraphQlInputException(__('The email address is incorrect. Verify the email address and try again.'));
        }

        $result = [];

        $longIp = $this->remoteAddress->getRemoteAddress();

        $isEnabled = $this->securityConfig->getPasswordResetProtectionType() != ResetMethod::OPTION_NONE;
        $allowedAttemptsNumber = $this->securityConfig->getMaxNumberPasswordResetRequests();
        if ($isEnabled && $allowedAttemptsNumber) {
            $collection = $this->prepareCollection(0, $email, $longIp);
            if ($collection->count() >= $allowedAttemptsNumber) {
                return false;
            }
        }
        $websiteId = $this->storeManager->getStore()->getWebsiteId();
        try {
            $customer = $this->customerRepository->get($email, $websiteId);
            $newPasswordToken = $this->mathRandom->getUniqueHash();
            $this->changeResetPasswordLinkToken($customer, $newPasswordToken);
            $this->passwordResetConfirmation($customer, $baseUrl);
        } catch (NoSuchEntityException $e) {

        } catch (MailException $e) {

        }
        return true;
    }

    /**
     * Prepare collection
     *
     * @param int $securityEventType
     * @param string $accountReference
     * @param int $longIp
     * @return Collection
     */
    protected function prepareCollection($securityEventType, $accountReference, $longIp)
    {
        if (null === $longIp) {
            $longIp = $this->remoteAddress->getRemoteAddress();
        }
        $collection = $this->collectionFactory->create($securityEventType, $accountReference, $longIp);
        $periodToCheck = $this->securityConfig->getLimitationTimePeriod();
        $collection->filterByLifetime($periodToCheck);

        return $collection;
    }

    /**
     * @param $customer
     * @param $passwordLinkToken
     * @return bool
     * @throws InputException
     * @throws LocalizedException
     * @throws NoSuchEntityException
     * @throws InputMismatchException
     */
    public function changeResetPasswordLinkToken($customer, $passwordLinkToken)
    {
        if (!is_string($passwordLinkToken) || empty($passwordLinkToken)) {
            throw new InputException(
                __(
                    'Invalid value of "%value" provided for the %fieldName field.',
                    ['value' => $passwordLinkToken, 'fieldName' => 'password reset token']
                )
            );
        }
        if (is_string($passwordLinkToken) && !empty($passwordLinkToken)) {
            $customerSecure = $this->customerRegistry->retrieveSecureData($customer->getId());
            $customerSecure->setRpToken($passwordLinkToken);
            $customerSecure->setRpTokenCreatedAt(
                $this->dateTimeFactory->create()->format(DateTime::DATETIME_PHP_FORMAT)
            );
            $this->customerRepository->save($customer);
        }
        return true;
    }

    /**
     * @param CustomerInterface $customer
     * @param $baseUrl
     * @throws LocalizedException
     * @throws MailException
     * @throws NoSuchEntityException
     */
    public function passwordResetConfirmation(CustomerInterface $customer, $baseUrl)
    {
        $storeId = $this->storeManager->getStore()->getId();

        $customerEmailData = $this->getFullCustomerObject($customer);
        $url = $baseUrl . '/resetpassword.html?token=' . $customerEmailData->getRpToken();
        $this->sendEmailTemplate(
            $customer,
            self::XML_PATH_FORGOT_EMAIL_TEMPLATE,
            self::XML_PATH_FORGOT_EMAIL_IDENTITY,
            [
                'customer' => $customerEmailData,
                'store' => $this->storeManager->getStore($storeId),
                'custom_reset_url' => $url
            ],
            $storeId
        );
    }

    /**
     * @param Customer $customer
     * @return CustomerSecure
     * @throws NoSuchEntityException
     */
    protected function getFullCustomerObject($customer)
    {
        // No need to flatten the custom attributes or nested objects since the only usage is for email templates and
        // object passed for events
        $mergedCustomerData = $this->customerRegistry->retrieveSecureData($customer->getId());
        $customerData = $this->dataProcessor->buildOutputDataArray(
            $customer,
            CustomerInterface::class
        );
        $mergedCustomerData->addData($customerData);
        $mergedCustomerData->setData('name', $this->customerViewHelper->getCustomerName($customer));
        return $mergedCustomerData;
    }

    /**
     * @param Customer $customer
     * @param $template
     * @param $sender
     * @param array $templateParams
     * @param null $storeId
     * @param null $email
     * @throws LocalizedException
     * @throws MailException
     */
    private function sendEmailTemplate(
        $customer,
        $template,
        $sender,
        $templateParams = [],
        $storeId = null,
        $email = null
    ) {
        $templateId = $this->scopeConfig->getValue($template, 'store', $storeId);
        if ($email === null) {
            $email = $customer->getEmail();
        }

        /** @var array $from */
        $from = $this->senderResolver->resolve(
            $this->scopeConfig->getValue($sender, 'store', $storeId),
            $storeId
        );

        $transport = $this->transportBuilder->setTemplateIdentifier($templateId)
            ->setTemplateOptions(['area' => 'frontend', 'store' => $storeId])
            ->setTemplateVars($templateParams)
            ->setFrom($from)
            ->addTo($email, $this->customerViewHelper->getCustomerName($customer))
            ->getTransport();

        $transport->sendMessage();
    }
}
