<?php
/**
 * @author Tigren Solutions <info@tigren.com>
 * @copyright Copyright (c) 2019 Tigren Solutions <https://www.tigren.com>. All rights reserved.
 * @license Open Software License ("OSL") v. 3.0
 */

namespace Tigren\PaypalExpress\Model;

use Magento\Framework\Exception\LocalizedException;
use Tigren\PaypalExpress\Api\GetTokenDataInterface;
use Psr\Log\LoggerInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\GuestCartRepositoryInterface;
use Magento\Customer\Model\ResourceModel\CustomerRepository;

/**
 * Interface ReviewManagement
 * @api
 */
class GetTokenData implements GetTokenDataInterface
{

    /**
     * @var \Magento\Paypal\Model\Express\Checkout
     */
    protected $_checkout;

    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $_customerSession;

    /**
     * @var \Psr\Log\LoggerInterface
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
     * @var \Tigren\PaypalExpress\Helper\Data
     */
    protected $_helper;

    /**
     * @var CustomerRepository
     */
    protected $customerRepository;

    /**
     * @var \Magento\Framework\UrlInterface
     */
    protected $_url;

    /**
     * GetTokenData constructor.
     * @param CartRepositoryInterface $cartRepository
     * @param GuestCartRepositoryInterface $guestCartRepository
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Tigren\PaypalExpress\Helper\Data $helper
     * @param CustomerRepository $customerRepository
     * @param \Magento\Framework\UrlInterface $url
     * @param LoggerInterface $logger
     */
    public function __construct(
        CartRepositoryInterface $cartRepository,
        GuestCartRepositoryInterface $guestCartRepository,
        \Magento\Customer\Model\Session $customerSession,
        \Tigren\PaypalExpress\Helper\Data $helper,
        CustomerRepository $customerRepository,
        \Magento\Framework\UrlInterface $url,
        LoggerInterface $logger
    ) {
        $this->_url = $url;
        $this->_helper = $helper;
        $this->cartRepository = $cartRepository;
        $this->guestCartRepository = $guestCartRepository;
        $this->_customerSession = $customerSession;
        $this->customerRepository = $customerRepository;
        $this->logger = $logger;
    }

    /**
     * @param \Tigren\PaypalExpress\Api\Data\TokenDataInterface $tokenData
     * @return mixed|string
     */
    public function getTokenData(\Tigren\PaypalExpress\Api\Data\TokenDataInterface $tokenData)
    {
        $quoteId = $tokenData->getQuoteId();
        $customerId = $tokenData->getCustomerId();
        $responseContent = [
            'success' => true,
            'error_message' => '',
        ];

        try {
            $token = $this->getToken($quoteId,$customerId);
            if ($token === null) {
                $token = false;
            }

            $responseContent['token'] = $token;
        } catch (LocalizedException $exception) {
            $this->logger->critical($exception);

            $responseContent['success'] = false;
            $responseContent['error_message'] = $exception->getMessage();
        } catch (\Exception $exception) {
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
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function getToken($quoteId,$customerId): ?string
    {
        $customerId = $customerId ?: $this->_customerSession->getId();
        $hasButton = true;
        /** @var \Magento\Quote\Model\Quote $quote */
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
