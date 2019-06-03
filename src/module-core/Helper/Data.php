<?php

namespace Tigren\Core\Helper;

use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Integration\Model\Oauth\Token as TokenModel;
use Magento\Integration\Model\Oauth\TokenFactory as TokenModelFactory;
use Magento\Integration\Model\ResourceModel\Oauth\Token\CollectionFactory as TokenCollectionFactory;

/**
 * Class Data
 * @package Tigren\Core\Helper
 */
class Data extends \Magento\Framework\App\Helper\AbstractHelper
{

    /**
     *
     */
    const XML_PATH_PWA_FEATUERE_PRODUCT_CONDITION = 'pwa_connector/general/feature_product';
    /**

    /**
     * @var TokenCollectionFactory
     */
    protected $tokenModelCollectionFactory;
    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;
    /**
     * @var
     */
    protected $_baseUrl;
    /**
     * @var TokenModelFactory
     */
    protected $tokenModelFactory;
    /**
     * @var TokenModel
     */
    protected $tokenModel;
    /**
     * @var
     */
    protected $_customerSession;
    /**
     * @var \Magento\Catalog\Model\Config
     */
    protected $_catalogConfig;
    /**
     * @var \Magento\Store\Model\App\Emulation
     */
    protected $_appEmulation;
    /**
     * @var \Magento\Framework\View\LayoutFactory
     */
    protected $layoutFactory;
    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $customerSession;
    /**
     * @var \Magento\Directory\Block\Data
     */
    protected $blockDirectory;
    /**
     * @var \Magento\Directory\Helper\Data
     */
    protected $helperDirectory;
    /**
     * @var \Magento\Catalog\Helper\ImageFactory
     */
    protected $imageHelperFactory;
    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $checkoutSession;
    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;
    /**
     *  * @var \Magento\Eav\Model\ResourceModel\Entity\Attribute
     *  */
    protected $_eavAttribute;
    /**
     * @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface
     */
    protected $_localeDate;
    /**
     * @var \Magento\Quote\Model\QuoteFactory
     */
    protected $_quoteFactory;
    /**
     * @var \Magento\Framework\App\ResourceConnection
     */
    protected $_resource;
    /**
     * @var \Magento\Framework\DB\Adapter\AdapterInterface
     */
    protected $_connection;
    /**
     * @var \Magento\Customer\Model\CustomerFactory
     */
    protected $_customerFactory;
    /**
     * @var CustomerRepositoryInterface
     */
    private $customerRepository;

    /**
     * Data constructor.
     * @param \Magento\Framework\App\Helper\Context $context
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param TokenCollectionFactory $tokenModelCollectionFactory
     * @param TokenModelFactory $tokenModelFactory
     * @param TokenModel $tokenModel
     * @param \Magento\Catalog\Model\Config $catalogConfig
     * @param \Magento\Store\Model\App\Emulation $appEmulation
     * @param \Magento\Framework\View\LayoutFactory $layoutFactory
     * @param \Magento\Catalog\Helper\ImageFactory $imageHelperFactory
     * @param \Magento\Customer\Model\Session $customerSession
     * @param CustomerRepositoryInterface $customerRepository
     * @param \Magento\Directory\Block\Data $blockDirectory
     * @param \Magento\Directory\Helper\Data $helperDirectory
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Quote\Model\QuoteFactory $quoteFactory
     * @param \Magento\Eav\Model\ResourceModel\Entity\Attribute $eavAttribute
     * @param \Magento\Framework\App\ResourceConnection $resource
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate
     * @param \Magento\Customer\Model\CustomerFactory $customerFactory
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        TokenCollectionFactory $tokenModelCollectionFactory,
        TokenModelFactory $tokenModelFactory,
        TokenModel $tokenModel,
        \Magento\Catalog\Model\Config $catalogConfig,
        \Magento\Store\Model\App\Emulation $appEmulation,
        \Magento\Framework\View\LayoutFactory $layoutFactory,
        \Magento\Catalog\Helper\ImageFactory $imageHelperFactory,
        \Magento\Customer\Model\Session $customerSession,
        CustomerRepositoryInterface $customerRepository,
        \Magento\Directory\Block\Data $blockDirectory,
        \Magento\Directory\Helper\Data $helperDirectory,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Quote\Model\QuoteFactory $quoteFactory,
        \Magento\Eav\Model\ResourceModel\Entity\Attribute $eavAttribute,
        \Magento\Framework\App\ResourceConnection $resource,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate,
        \Magento\Customer\Model\CustomerFactory $customerFactory
    ) {
        $this->tokenModelCollectionFactory = $tokenModelCollectionFactory;
        $this->_storeManager = $storeManager;
        $this->_baseUrl = $this->_storeManager->getStore()->getBaseUrl();
        $this->tokenModelFactory = $tokenModelFactory;
        $this->tokenModel = $tokenModel;
        $this->_catalogConfig = $catalogConfig;
        $this->_appEmulation = $appEmulation;
        $this->layoutFactory = $layoutFactory;
        $this->imageHelperFactory = $imageHelperFactory;
        $this->customerSession = $customerSession;
        $this->customerRepository = $customerRepository;
        $this->blockDirectory = $blockDirectory;
        $this->helperDirectory = $helperDirectory;
        $this->checkoutSession = $checkoutSession;
        $this->scopeConfig = $scopeConfig;
        $this->_quoteFactory = $quoteFactory;
        $this->_eavAttribute = $eavAttribute;
        $this->_resource = $resource;
        $this->_connection = $this->_resource->getConnection('core_write');
        $this->_localeDate = $localeDate;
        $this->_customerFactory = $customerFactory;
        parent::__construct($context);
    }

    /**
     * @return int
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getStoreId()
    {
        return $this->getStore()->getId();
    }

    /**
     * @return \Magento\Store\Api\Data\StoreInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getStore()
    {
        return $this->_storeManager->getStore();
    }

    /**
     * @param $id
     * @return string|null
     */
    public function getCustomerTokenById($id)
    {
        $token = $this->tokenModel->loadByCustomerId($id);
        if ($token->getId()) {
            return $token->getToken();
        } else {
            return $this->tokenModel->createCustomerToken($id)->getToken();
        }
        return null;
    }

    /**
     * @return mixed
     */
    public function getBaseUrl()
    {
        return $this->_baseUrl;
    }

    /**
     * @return \Magento\Store\Model\App\Emulation
     */
    public function getAppEmulation()
    {
        return $this->_appEmulation;
    }

    /**
     * @return \Magento\Customer\Api\Data\CustomerInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getCustomer()
    {
        return $this->customerRepository->getById($this->getCustomerId());
    }

    /**
     * @return int|null
     */
    public function getCustomerId()
    {
        return $this->customerSession->getCustomerId();
    }

    /**
     * @return array
     */
    public function getCountryOptions()
    {
        $options = $this->blockDirectory->getCountryCollection()
            ->setForegroundCountries($this->blockDirectory->getTopDestinations())
            ->toOptionArray();
        return $options;
    }

    /**
     * @return mixed
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getMediaUrl()
    {
        return $this->_storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA);
    }

    /**
     * Receive magento config value
     *
     * @param  string $path
     * @param  string | int $store
     * @param  \Magento\Store\Model\ScopeInterface | null $scope
     * @return mixed
     */
    public function getConfig($path, $store = null, $scope = null)
    {
        if ($scope === null) {
            $scope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
        }
        return $this->scopeConfig->getValue($path, $scope, $store);
    }

    /**
     * @param null $date
     * @param int $format
     * @param bool $showTime
     * @param null $timezone
     * @return string
     * @throws \Exception
     */
    public function formatDate(
        $date = null,
        $format = \IntlDateFormatter::SHORT,
        $showTime = false,
        $timezone = null
    ) {
        $date = $date instanceof \DateTimeInterface ? $date : new \DateTime($date);
        return $this->_localeDate->formatDateTime(
            $date,
            $format,
            $showTime ? $format : \IntlDateFormatter::NONE,
            null,
            $timezone
        );
    }

    /**
     * @return int
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getWebsiteId()
    {
        return $this->getStore()->getWebsiteId();
    }

    public function getConditionFeature()
    {
        return $this->getConfig(self::XML_PATH_PWA_FEATUERE_PRODUCT_CONDITION);
    }

}
