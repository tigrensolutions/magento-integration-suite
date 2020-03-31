<?php
/**
 * @author    Tigren Solutions <info@tigren.com>
 * @copyright Copyright (c) 2019 Tigren Solutions <https://www.tigren.com>. All rights reserved.
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Tigren\PaypalExpress\Model;

use Exception;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\ObjectManagerInterface;
use Magento\Paypal\Model\Config as PayPalConfig;
use Magento\Paypal\Model\Express\Checkout as PayPalCheckout;
use Magento\Paypal\Model\Api\ProcessableException as ApiProcessableException;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Quote\Model\Quote;
use Magento\Sales\Model\OrderFactory;
use Magento\Paypal\Model\Express\Checkout\Factory as CheckoutFactory;
use Magento\Framework\Session\Generic as PayPalSession;
use Magento\Framework\Url\Helper\Data as UrlHelper;
use Magento\Customer\Model\Url as CustomerUrl;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Framework\UrlInterface;
use Magento\Quote\Api\GuestCartRepositoryInterface;
use Tigren\PaypalExpress\Api\Data\PaymentDataInterface;
use Tigren\PaypalExpress\Api\OnAuthorizationInterface;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Authorization\Model\UserContextInterface;
use Tigren\PaypalExpress\Helper\Data;

/**
 * Interface ReviewManagement
 * @api
 */
class OnAuthorization implements OnAuthorizationInterface
{
    /**
     * @var PayPalCheckout
     */
    protected $_checkout;

    /**
     * @var PayPalConfig
     */
    protected $_config;

    /**
     * @var Quote
     */
    protected $_quote = false;

    /**
     * @var CustomerSession
     */
    protected $_customerSession;

    /**
     * @var CheckoutSession
     */
    protected $_checkoutSession;

    /**
     * @var OrderFactory
     */
    protected $_orderFactory;

    /**
     * @var CheckoutFactory
     */
    protected $_checkoutFactory;

    /**
     * @var PayPalSession
     */
    protected $_paypalSession;

    /**
     * @inheritdoc
     */
    protected $_configType = PayPalConfig::class;

    /**
     * Internal cache of checkout models
     *
     * @var array
     */
    protected $_checkoutTypes = [];

    /**
     * @inheritdoc
     */
    protected $_configMethod = PayPalConfig::METHOD_WPP_EXPRESS;

    /**
     * @inheritdoc
     */
    protected $_checkoutType = PayPalCheckout::class;
    /**
     * @var ManagerInterface
     */
    protected $_eventManager;
    /**
     * @var ObjectManagerInterface
     */
    protected $_objectManager;
    /**
     * @var Data
     */
    protected $_helper;
    /**
     * @var UserContextInterface
     */
    protected $userContext;
    /**
     * @var CartRepositoryInterface
     */
    private $cartRepository;
    /**
     * Url Builder
     *
     * @var UrlInterface
     */
    private $urlBuilder;
    /**
     * @var GuestCartRepositoryInterface
     */
    private $guestCartRepository;

    /**
     * OnAuthorization constructor.
     * @param CustomerSession $customerSession
     * @param CheckoutSession $checkoutSession
     * @param OrderFactory $orderFactory
     * @param CheckoutFactory $checkoutFactory
     * @param PayPalSession $paypalSession
     * @param CartRepositoryInterface $cartRepository
     * @param UrlInterface $urlBuilder
     * @param ManagerInterface $manager
     * @param ObjectManagerInterface $objectManager
     * @param Data $helper
     * @param GuestCartRepositoryInterface $guestCartRepository
     * @param UserContextInterface $userContext
     */
    public function __construct(
        CustomerSession $customerSession,
        CheckoutSession $checkoutSession,
        OrderFactory $orderFactory,
        CheckoutFactory $checkoutFactory,
        PayPalSession $paypalSession,
        CartRepositoryInterface $cartRepository,
        UrlInterface $urlBuilder,
        ManagerInterface $manager,
        ObjectManagerInterface $objectManager,
        Data $helper,
        GuestCartRepositoryInterface $guestCartRepository,
        UserContextInterface $userContext
    ) {
        $this->_customerSession = $customerSession;
        $this->_checkoutSession = $checkoutSession;
        $this->_orderFactory = $orderFactory;
        $this->_checkoutFactory = $checkoutFactory;
        $this->_paypalSession = $paypalSession;
        $this->cartRepository = $cartRepository;
        $this->urlBuilder = $urlBuilder;
        $this->guestCartRepository = $guestCartRepository;
        $this->_eventManager = $manager;
        $this->_objectManager = $objectManager;
        $this->_helper = $helper;
        $this->userContext = $userContext;
    }

    /**
     * @param PaymentDataInterface $paymentData
     * @return false|mixed|string
     */
    public function authorization(PaymentDataInterface $paymentData)
    {
        $paymentToken = $paymentData->getPaymentToken();
        $payerId = $paymentData->getPayerId();
        $quoteId = $paymentData->getQuoteId();
        $customerId = $this->userContext->getUserId() ?: null;
        try {
            $quote = $customerId ? $this->cartRepository->get($quoteId) : $this->guestCartRepository->get($quoteId);
            $response = [
                'success' => true,
                'order_id' => '',
                'error_message' => ''
            ];

            /** Populate checkout object with new data */
            $this->_checkout = $this->_helper->_initCheckout($quote);
            /**  Populate quote  with information about billing and shipping addresses*/
            $this->_checkout->returnFromPaypal($paymentToken, $payerId);
            $this->_checkout->place($paymentToken);
            $order = $this->_checkout->getOrder();
            /** "last successful quote" */
            $this->_getCheckoutSession()->setLastQuoteId($quote->getId())->setLastSuccessQuoteId($quote->getId());

            $this->_getCheckoutSession()->setLastOrderId($order->getId())
                ->setLastRealOrderId($order->getIncrementId())
                ->setLastOrderStatus($order->getStatus());

            $this->_eventManager->dispatch(
                'paypal_express_place_order_success',
                [
                    'order' => $order,
                    'quote' => $quote
                ]
            );
            $response['redirectUrl'] = $this->urlBuilder->getUrl('checkout/onepage/success/');
            $response['order_id'] = $order->getId();
        } catch (ApiProcessableException $e) {
            $response['success'] = false;
            $response['error_message'] = $e->getUserMessage();
        } catch (LocalizedException $e) {
            $response['success'] = false;
            $response['error_message'] = $e->getMessage();
        } catch (Exception $e) {
            $response['success'] = false;
            $response['error_message'] = __('We can\'t process Express Checkout approval.');
        }

        return json_encode($response);
    }

    /**
     * Return checkout session object
     *
     * @return CheckoutSession
     */
    protected function _getCheckoutSession()
    {
        return $this->_checkoutSession;
    }
}
