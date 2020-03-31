<?php
/**
 * @author    Tigren Solutions <info@tigren.com>
 * @copyright Copyright (c) 2019 Tigren Solutions <https://www.tigren.com>. All rights reserved.
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Tigren\PaypalExpress\Helper;

use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\DB\Helper;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Json\EncoderInterface;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\Registry;
use Magento\Paypal\Model\Express\Checkout\Factory;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Paypal\Model\Config as PayPalConfig;
use Magento\Paypal\Model\Express\Checkout as PayPalCheckout;
use Magento\Quote\Model\Quote;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Class Data
 * @package Tigren\PaypalExpress\Helper
 */
class Data extends AbstractHelper
{
    /**
     * @var ObjectManagerInterface
     */
    protected $_objectManager;

    /**
     * @inheritdoc
     */
    protected $_configMethod = PayPalConfig::METHOD_WPP_EXPRESS;

    /**
     * @inheritdoc
     */
    protected $_configType = PayPalConfig::class;

    /**
     * @inheritdoc
     */
    protected $_checkoutType = PayPalCheckout::class;

    /**
     * @var array
     */
    protected $_checkoutTypes = [];

    /**
     * @var Factory
     */
    protected $_checkoutFactory;


    /**
     * @param Context $context
     * @param StoreManagerInterface $storeManager
     * @param EncoderInterface $jsonEncoder
     * @param Registry $coreRegistry
     * @param CustomerSession $customerSession
     * @param Helper $resourceHelper
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        Context $context,
        ObjectManagerInterface $objectManager,
        Factory $factory
    ) {
        $this->_checkoutFactory = $factory;
        $this->_objectManager = $objectManager;
        parent::__construct($context);
    }

    /**
     * @param CartInterface|null $quoteObject
     * @return mixed
     * @throws LocalizedException
     */
    public function _initCheckout(CartInterface $quoteObject = null)
    {
        $parameters = ['params' => [$this->_configMethod]];
        $config = $this->_objectManager->create($this->_configType, $parameters);
        /** @var Quote $quote */
        $quote = $quoteObject;
        if (!$quote->hasItems() || $quote->getHasError()) {
            throw new LocalizedException(__('We can\'t initialize Express Checkout.'));
        }
        if (!(float)$quote->getGrandTotal()) {
            throw new LocalizedException(
                __(
                    'PayPal can\'t process orders with a zero balance due. '
                    . 'To finish your purchase, please go through the standard checkout process.'
                )
            );
        }
        if (!isset($this->_checkoutTypes[$this->_checkoutType])) {
            $parameters = [
                'params' => [
                    'quote' => $quote,
                    'config' => $config,
                ],
            ];
            $this->_checkoutTypes[$this->_checkoutType] = $this->_checkoutFactory
                ->create($this->_checkoutType, $parameters);
        }
        return $this->_checkoutTypes[$this->_checkoutType];
    }
}
