<?php
/**
 * @author Tigren Solutions <info@tigren.com>
 * @copyright Copyright (c) 2019 Tigren Solutions <https://www.tigren.com>. All rights reserved.
 * @license Open Software License ("OSL") v. 3.0
 */

namespace Tigren\PaypalExpress\Model;

use Magento\Paypal\Model\Config as PayPalConfig;
use Magento\Paypal\Model\Express\Checkout as PayPalCheckout;
use Magento\Paypal\Model\Api\ProcessableException as ApiProcessableException;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Sales\Model\OrderFactory;
use Magento\Paypal\Model\Express\Checkout\Factory as CheckoutFactory;
use Magento\Framework\Session\Generic as PayPalSession;
use Magento\Framework\Url\Helper\Data as UrlHelper;
use Magento\Customer\Model\Url as CustomerUrl;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Framework\UrlInterface;
use Magento\Quote\Api\GuestCartRepositoryInterface;
use Tigren\PaypalExpress\Api\OnAuthorizationInterface;
use Magento\Quote\Api\Data\CartInterface;

/**
 * Interface ReviewManagement
 * @api
 */
class OnAuthorization implements OnAuthorizationInterface
{

    /**
     * @var \Magento\Paypal\Model\Express\Checkout
     */
    protected $_checkout;

    /**
     * @var \Magento\Paypal\Model\Config
     */
    protected $_config;

    /**
     * @var \Magento\Quote\Model\Quote
     */
    protected $_quote = false;

    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $_customerSession;

    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $_checkoutSession;

    /**
     * @var \Magento\Sales\Model\OrderFactory
     */
    protected $_orderFactory;

    /**
     * @var \Magento\Paypal\Model\Express\Checkout\Factory
     */
    protected $_checkoutFactory;

    /**
     * @var \Magento\Framework\Session\Generic
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
     * @var \Magento\Framework\Event\ManagerInterface
     */
    protected $_eventManager;

    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    protected $_objectManager;

    protected $_helper;


    public function __construct(
        CustomerSession $customerSession,
        CheckoutSession $checkoutSession,
        OrderFactory $orderFactory,
        CheckoutFactory $checkoutFactory,
        PayPalSession $paypalSession,
        CartRepositoryInterface $cartRepository,
        UrlInterface $urlBuilder,
        \Magento\Framework\Event\ManagerInterface $manager,
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Tigren\PaypalExpress\Helper\Data $helper,
        GuestCartRepositoryInterface $guestCartRepository
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
    }

    public function authorization(\Tigren\PaypalExpress\Api\Data\PaymentDataInterface $paymentData)
    {
        $paymentToken = $paymentData->getPaymentToken();
        $payerId = $paymentData->getPayerId();
        $quoteId = $paymentData->getQuoteId();
        $customerId = $paymentData->getCustomerId();
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
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            $response['success'] = false;
            $response['error_message'] = $e->getMessage();
        } catch (\Exception $e) {
            $response['success'] = false;
            $response['error_message'] = __('We can\'t process Express Checkout approval.');
        }

        return json_encode($response);
    }

    /**
     * Return checkout session object
     *
     * @return \Magento\Checkout\Model\Session
     */
    protected function _getCheckoutSession()
    {
        return $this->_checkoutSession;
    }

}
