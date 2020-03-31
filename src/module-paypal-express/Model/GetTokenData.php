<?php
/**
 * @author    Tigren Solutions <info@tigren.com>
 * @copyright Copyright (c) 2019 Tigren Solutions <https://www.tigren.com>. All rights reserved.
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Tigren\PaypalExpress\Model;

use Exception;
use Magento\Customer\Model\Session;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\UrlInterface;
use Magento\Paypal\Model\Express\Checkout;
use Magento\Quote\Model\Quote;
use Tigren\PaypalExpress\Api\Data\TokenDataInterface;
use Tigren\PaypalExpress\Api\GetTokenDataInterface;
use Psr\Log\LoggerInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\GuestCartRepositoryInterface;
use Magento\Customer\Model\ResourceModel\CustomerRepository;
use Magento\Authorization\Model\UserContextInterface;
use Tigren\PaypalExpress\Helper\Data;

/**
 * Interface ReviewManagement
 * @api
 */
class GetTokenData implements GetTokenDataInterface
{

    /**
     * @var Checkout
     */
    protected $_checkout;

    /**
     * @var Session
     */
    protected $_customerSession;
    /**
     * @var Data
     */
    protected $_helper;
    /**
     * @var CustomerRepository
     */
    protected $customerRepository;
    /**
     * @var UrlInterface
     */
    protected $_url;
    /**
     * @var UserContextInterface
     */
    protected $userContext;
    /**
     * @var LoggerInterface
     */
    private $logger;
    /**
     * @var CartRepositoryInterface
     */
    private $cartRepository;
    /**
     * @var GuestCartRepositoryInterface
     */
    private $guestCartRepository;

    /**
     * GetTokenData constructor.
     * @param CartRepositoryInterface $cartRepository
     * @param GuestCartRepositoryInterface $guestCartRepository
     * @param Session $customerSession
     * @param Data $helper
     * @param CustomerRepository $customerRepository
     * @param UrlInterface $url
     * @param LoggerInterface $logger
     */
    public function __construct(
        CartRepositoryInterface $cartRepository,
        GuestCartRepositoryInterface $guestCartRepository,
        Session $customerSession,
        Data $helper,
        CustomerRepository $customerRepository,
        UrlInterface $url,
        LoggerInterface $logger,
        UserContextInterface $userContext
    ) {
        $this->_url = $url;
        $this->_helper = $helper;
        $this->cartRepository = $cartRepository;
        $this->guestCartRepository = $guestCartRepository;
        $this->_customerSession = $customerSession;
        $this->customerRepository = $customerRepository;
        $this->logger = $logger;
        $this->userContext = $userContext;
    }

    /**
     * @param TokenDataInterface $tokenData
     * @return mixed|string
     */
    public function getTokenData(TokenDataInterface $tokenData)
    {
        $quoteId = $tokenData->getQuoteId();
        $customerId = $this->userContext->getUserId() ?: null;
        $responseContent = [
            'success' => true,
            'error_message' => '',
        ];

        try {
            $token = $this->getToken($quoteId, $customerId);
            if ($token === null) {
                $token = false;
            }

            $responseContent['token'] = $token;
        } catch (LocalizedException $exception) {
            $this->logger->critical($exception);

            $responseContent['success'] = false;
            $responseContent['error_message'] = $exception->getMessage();
        } catch (Exception $exception) {
            $this->logger->critical($exception);

            $responseContent['success'] = false;
            $responseContent['error_message'] = __('Sorry, but something went wrong');
        }
        return json_encode($responseContent);
    }

    /**
     * @param $quoteId
     * @param $customerId
     * @return null|string
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    private function getToken($quoteId, $customerId): ?string
    {
        $customerId = $customerId ?: $this->_customerSession->getId();
        $hasButton = false;
        /** @var Quote $quote */
        $quote = $customerId ? $this->cartRepository->get($quoteId) : $this->guestCartRepository->get($quoteId);

        $this->_checkout = $this->_helper->_initCheckout($quote);

        if ($quote->getIsMultiShipping()) {
            $quote->setIsMultiShipping(0);
            $quote->removeAllAddresses();
        }

        if ($customerId) {
            $customerData = $this->customerRepository->getById((int)$customerId);

            $this->_checkout->setCustomerWithAddressChange(
                $customerData,
                $quote->getBillingAddress(),
                $quote->getShippingAddress()
            );
        }

        // giropay urls
        $this->_checkout->prepareGiropayUrls(
            $this->_url->getUrl('checkout/onepage/success'),
            $this->_url->getUrl('paypal/express/cancel'),
            $this->_url->getUrl('checkout/onepage/success')
        );

        return $this->_checkout->start(
            $this->_url->getUrl('*/*/return'),
            $this->_url->getUrl('*/*/cancel'),
            $hasButton
        );
    }
}
