<?php
/**
 * @author    Tigren Solutions <info@tigren.com>
 * @copyright Copyright (c) 2019 Tigren Solutions <https://www.tigren.com>. All rights reserved.
 * @license   Open Software License ("OSL") v. 3.0
 */
declare(strict_types=1);

namespace Tigren\StoreGraphQl\Model\Resolver;

use Magento\Directory\Helper\Data;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Data\Helper\PostHelper;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Framework\Url\Helper\Data as UrlHelper;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 * {@inheritdoc}
 */
class Store implements ResolverInterface
{
    /**
     * @var bool
     */
    protected $_storeInUrl;

    /**
     * @var PostHelper
     */
    protected $_postDataHelper;
    /**
     * Store manager
     *
     * @var StoreManagerInterface
     */
    protected $_storeManager;
    /**
     * @var ScopeConfigInterface
     */
    protected $_scopeConfig;
    /**
     * @var UrlHelper
     */
    private $urlHelper;

    /**
     * @param PostHelper $postDataHelper
     * @param UrlHelper $urlHelper
     * @param StoreManagerInterface $storeManager
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        PostHelper $postDataHelper,
        UrlHelper $urlHelper = null,
        StoreManagerInterface $storeManager,
        ScopeConfigInterface $scopeConfig
    ) {
        $this->_postDataHelper = $postDataHelper;
        $this->urlHelper = $urlHelper ?: ObjectManager::getInstance()->get(UrlHelper::class);
        $this->_storeManager = $storeManager;
        $this->_scopeConfig = $scopeConfig;
    }

    /**
     * @inheritdoc
     */
    public function resolve(Field $field, $context, ResolveInfo $info, array $value = null, array $args = null)
    {
        $storeData = [];
        $default = $this->_storeManager->getDefaultStoreView();
        $storeData = [
            'default' => $default->getCode()
        ];
        $rawStores = $this->getRawStores();
        $groupId = $this->getCurrentGroupId();
        if (!isset($rawStores[$groupId])) {
            $stores = [];
        } else {
            $stores = $rawStores[$groupId];
        }
        $storeData['available_stores'] = $stores;
        return $storeData;
    }

    /**
     * Get raw stores.
     *
     * @return array
     * @throws LocalizedException
     */
    public function getRawStores()
    {
        $websiteStores = $this->_storeManager->getWebsite()->getStores();
        $stores = [];
        foreach ($websiteStores as $store) {
            /* @var $store \Magento\Store\Model\Store */
            if (!$store->isActive()) {
                continue;
            }
            $localeCode = $this->_scopeConfig->getValue(
                Data::XML_PATH_DEFAULT_LOCALE,
                ScopeInterface::SCOPE_STORE,
                $store
            );
            $store->setLocaleCode($localeCode);
            $stores[$store->getGroupId()][$store->getId()] = $store;
        }
        return $stores;
    }

    /**
     * Get current group Id.
     *
     * @return int|null|string
     * @throws NoSuchEntityException
     */
    public function getCurrentGroupId()
    {
        return $this->_storeManager->getStore()->getGroupId();
    }

    /**
     * Get current store code.
     *
     * @return string
     * @throws NoSuchEntityException
     */
    public function getCurrentStoreCode()
    {
        return $this->_storeManager->getStore()->getCode();
    }

    /**
     * Get store code.
     *
     * @return string
     * @throws NoSuchEntityException
     */
    public function getStoreCode()
    {
        return $this->_storeManager->getStore()->getCode();
    }

    /**
     * Get store name.
     *
     * @return null|string
     * @throws NoSuchEntityException
     */
    public function getStoreName()
    {
        return $this->_storeManager->getStore()->getName();
    }
}
