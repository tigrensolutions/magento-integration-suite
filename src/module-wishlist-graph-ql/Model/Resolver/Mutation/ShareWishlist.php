<?php
/**
 * @author    Tigren Solutions <info@tigren.com>
 * @copyright Copyright (c) 2019 Tigren Solutions <https://www.tigren.com>. All rights reserved.
 * @license   Open Software License ("OSL") v. 3.0
 */
declare(strict_types=1);

namespace Tigren\WishlistGraphQl\Model\Resolver\Mutation;

use Exception;
use Magento\Customer\Helper\View;
use Magento\Framework\App\Area;
use Magento\Framework\App\ObjectManager;
use Magento\Authorization\Model\UserContextInterface;
use Magento\CustomerGraphQl\Model\Customer\GetCustomer;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlAuthorizationException;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Framework\Mail\Template\TransportBuilder;
use Magento\Framework\Translate\Inline\StateInterface;
use Magento\Framework\UrlInterface;
use Magento\Framework\Validator\EmailAddress;
use Magento\Framework\View\LayoutInterface;
use Magento\GraphQl\Model\Query\ContextInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Wishlist\Controller\WishlistProviderInterface;
use Magento\Wishlist\Model\Config;
use Magento\Wishlist\Model\ResourceModel\Item\Collection;
use Magento\Wishlist\Model\WishlistFactory;
use Magento\Framework\Escaper;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\StoreManagerInterface;
use Zend_Validate;

/**
 * Class ShareWishlist
 * @package Tigren\WishlistGraphQl\Model\Resolver\Mutation
 */
class ShareWishlist implements ResolverInterface
{
    /**
     * @var WishlistFactory
     */
    protected $wishlistFactory;
    /**
     * @var View
     */
    protected $_customerHelperView;
    /**
     * @var StateInterface
     */
    protected $inlineTranslation;
    /**
     * @var TransportBuilder
     */
    protected $_transportBuilder;
    /**
     * @var Config
     */
    protected $_wishlistConfig;
    /**
     * @var WishlistProviderInterface
     */
    protected $wishlistProvider;
    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;
    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;
    protected $_wishlistCollectionFactory;
    /**
     * Layout
     *
     * @var LayoutInterface
     */
    protected $_layout;
    /**
     * @var UrlInterface
     */
    protected $_url;
    /**
     * @var UserContextInterface
     */
    private $userContext;
    /**
     * @var GetCustomer
     */
    private $getCustomer;
    /**
     * @var Escaper
     */
    private $escaper;

    /**
     * ShareWishlist constructor.
     * @param WishlistFactory $wishlistFactory
     * @param UserContextInterface $userContext
     * @param GetCustomer $getCustomer
     * @param WishlistProviderInterface $wishlistProvider
     * @param Config $wishlistConfig
     * @param TransportBuilder $transportBuilder
     * @param StateInterface $inlineTranslation
     * @param View $customerHelperView
     * @param ScopeConfigInterface $scopeConfig
     * @param StoreManagerInterface $storeManager
     * @param Collection $collection
     * @param LayoutInterface $layout
     * @param UrlInterface $url
     * @param Escaper|null $escaper
     */
    public function __construct(
        WishlistFactory $wishlistFactory,
        UserContextInterface $userContext,
        GetCustomer $getCustomer,
        WishlistProviderInterface $wishlistProvider,
        Config $wishlistConfig,
        TransportBuilder $transportBuilder,
        StateInterface $inlineTranslation,
        View $customerHelperView,
        ScopeConfigInterface $scopeConfig,
        StoreManagerInterface $storeManager,
        Collection $collection,
        LayoutInterface $layout,
        UrlInterface $url,
        Escaper $escaper = null
    ) {
        $this->wishlistFactory = $wishlistFactory;
        $this->userContext = $userContext;
        $this->getCustomer = $getCustomer;
        $this->wishlistProvider = $wishlistProvider;
        $this->_wishlistConfig = $wishlistConfig;
        $this->_transportBuilder = $transportBuilder;
        $this->inlineTranslation = $inlineTranslation;
        $this->_customerHelperView = $customerHelperView;
        $this->scopeConfig = $scopeConfig;
        $this->storeManager = $storeManager;
        $this->escaper = $escaper ?? ObjectManager::getInstance()->get(
                Escaper::class
            );
        $this->_wishlistCollectionFactory = $collection;
        $this->_layout = $layout;
        $this->_url = $url;
    }

    /**
     * @inheritdoc
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
        if (!isset($args['email'])) {
            throw new GraphQlInputException(__('Specify the "items" value.'));
        }
        $customer = $this->getCustomer->execute($context);
        $wishlist = $this->wishlistFactory->create()->loadByCustomerId($customer->getId(), true);
        if (!$wishlist) {
            throw new Exception(__('We can\'t specify wishlist'));
        }
        $sharingLimit = $this->_wishlistConfig->getSharingEmailLimit();
        $textLimit = $this->_wishlistConfig->getSharingTextLimit();
        $emailsLeft = $sharingLimit - $wishlist->getShared();

        $emails = $args['email'];
        $emails = empty($emails) ? $emails : explode(',', $emails);

        $error = false;
        $emailMessage = '';
        if (isset($args['message'])) {
            $emailMessage = (string)$args['message'];
        }

        if (strlen($emailMessage) > $textLimit) {
            $error = true;
            $message = [
                'type' => 'error',
                'text' => __('Message length must not exceed %1 symbols', $textLimit)
            ];
        } else {
            $emailMessage = nl2br($this->escaper->escapeHtml($emailMessage));
            if (empty($emails)) {
                $error = true;
                $message = [
                    'type' => 'error',
                    'text' => __('Please enter an email address.')
                ];
            } else {
                if (count($emails) > $emailsLeft) {
                    $error = true;
                    $message = [
                        'type' => 'error',
                        'text' => __('This wish list can be shared %1 more times.', $emailsLeft)
                    ];
                } else {
                    foreach ($emails as $index => $email) {
                        $email = trim($email);
                        if (!Zend_Validate::is($email, EmailAddress::class)) {
                            $error = true;
                            $message = [
                                'type' => 'error',
                                'text' => __('Please enter a valid email address.')
                            ];
                            break;
                        }
                        $emails[$index] = $email;
                    }
                }
            }
        }

        if ($error) {
            return $message;
        }
        $this->inlineTranslation->suspend();

        $sent = 0;

        try {
            $customerName = $this->_customerHelperView->getCustomerName($customer);

            $emails = array_unique($emails);
            $sharingCode = $wishlist->getSharingCode();
            $items = $this->_wishlistCollectionFactory->addWishlistFilter($wishlist);
            $itemHtml = $this->_layout->createBlock('Tigren\WishlistGraphQl\Block\Share\Email\Items')
                ->setData('items', $items)
                ->toHtml();
            $baseAppUrl = $this->scopeConfig->getValue('web/unsecure/base_app_url');
            try {
                foreach ($emails as $email) {
                    $transport = $this->_transportBuilder->setTemplateIdentifier(
                        $this->scopeConfig->getValue(
                            'wishlist/email/email_template',
                            ScopeInterface::SCOPE_STORE
                        )
                    )->setTemplateOptions(
                        [
                            'area' => Area::AREA_FRONTEND,
                            'store' => $this->storeManager->getStore()->getStoreId(),
                        ]
                    )->setTemplateVars(
                        [
                            'customer' => $customer,
                            'customerName' => $customerName,
                            'salable' => $wishlist->isSalable() ? 'yes' : '',
                            'items' => $itemHtml,
                            'viewOnSiteLink' => $baseAppUrl . 'shared_wishlist?code=' . $sharingCode,
                            'message' => $emailMessage,
                            'store' => $this->storeManager->getStore(),
                        ]
                    )->setFrom(
                        $this->scopeConfig->getValue(
                            'wishlist/email/email_identity',
                            ScopeInterface::SCOPE_STORE
                        )
                    )->addTo(
                        $email
                    )->getTransport();

                    $transport->sendMessage();

                    $sent++;
                }
            } catch (Exception $e) {
                $wishlist->setShared($wishlist->getShared() + $sent);
                $wishlist->save();
                throw $e;
            }
            $wishlist->setShared($wishlist->getShared() + $sent);
            $wishlist->save();

            $this->inlineTranslation->resume();
            $message = [
                'type' => 'success',
                'text' => 'Your wish list has been shared.'
            ];
            return $message;

        } catch (Exception $e) {
            $message = [
                'type' => 'error',
                'text' => __('This wish list can be shared %1 more times.', $e->getMessage())
            ];
            return $message;
        }
    }
}
