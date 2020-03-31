<?php
/**
 * @author    Tigren Solutions <info@tigren.com>
 * @copyright Copyright (c) 2019 Tigren Solutions <https://www.tigren.com>. All rights reserved.
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Tigren\ProgressiveWebApp\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Class Data
 * @package Tigren\ProgressiveWebApp\Helper
 */
class Data extends AbstractHelper
{
    /**
     * @var StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * Data constructor.
     * @param Context $context
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        Context $context,
        StoreManagerInterface $storeManager
    ) {
        $this->_storeManager = $storeManager;
        parent::__construct($context);
    }

    /**
     * @return mixed
     * @throws NoSuchEntityException
     */
    public function isEnabled()
    {
        return $this->scopeConfig->getValue(
            'progressivewebapp/general/enable',
            ScopeInterface::SCOPE_STORE,
            $this->getStore()->getId()
        );
    }

    /**
     * @return StoreInterface
     * @throws NoSuchEntityException
     */
    public function getStore()
    {
        return $this->_storeManager->getStore();
    }

    /**
     * @return mixed
     * @throws NoSuchEntityException
     */
    public function getManifestShortName()
    {
        return $this->scopeConfig->getValue(
            'progressivewebapp/manifest/short_name',
            ScopeInterface::SCOPE_STORE,
            $this->getStore()->getId()
        );
    }

    /**
     * @return mixed
     * @throws NoSuchEntityException
     */
    public function getManifestName()
    {
        return $this->scopeConfig->getValue(
            'progressivewebapp/manifest/name',
            ScopeInterface::SCOPE_STORE,
            $this->getStore()->getId()
        );
    }

    /**
     * @return mixed
     * @throws NoSuchEntityException
     */
    public function getManifestDescription()
    {
        return $this->scopeConfig->getValue(
            'progressivewebapp/manifest/description',
            ScopeInterface::SCOPE_STORE,
            $this->getStore()->getId()
        );
    }

    /**
     * @return mixed
     * @throws NoSuchEntityException
     */
    public function getManifestStartUrl()
    {
        return $this->scopeConfig->getValue(
            'progressivewebapp/manifest/start_url',
            ScopeInterface::SCOPE_STORE,
            $this->getStore()->getId()
        );
    }

    /**
     * @return mixed
     * @throws NoSuchEntityException
     */
    public function getManifestThemeColor()
    {
        return $this->scopeConfig->getValue(
            'progressivewebapp/manifest/theme_color',
            ScopeInterface::SCOPE_STORE,
            $this->getStore()->getId()
        );
    }

    /**
     * @return mixed
     * @throws NoSuchEntityException
     */
    public function getManifestBgColor()
    {
        return $this->scopeConfig->getValue(
            'progressivewebapp/manifest/background_color',
            ScopeInterface::SCOPE_STORE,
            $this->getStore()->getId()
        );
    }

    /**
     * @return mixed
     * @throws NoSuchEntityException
     */
    public function getManifestDisplayType()
    {
        return $this->scopeConfig->getValue(
            'progressivewebapp/manifest/display_type',
            ScopeInterface::SCOPE_STORE,
            $this->getStore()->getId()
        );
    }

    /**
     * @return mixed
     * @throws NoSuchEntityException
     */
    public function getManifestOrientation()
    {
        return $this->scopeConfig->getValue(
            'progressivewebapp/manifest/orientation',
            ScopeInterface::SCOPE_STORE,
            $this->getStore()->getId()
        );
    }

    /**
     * @return mixed
     * @throws NoSuchEntityException
     */
    public function getManifestIcon()
    {
        return $this->scopeConfig->getValue(
            'progressivewebapp/manifest/icon',
            ScopeInterface::SCOPE_STORE,
            $this->getStore()->getId()
        );
    }

    /**
     * @return mixed
     * @throws NoSuchEntityException
     */
    public function getManifestIconSizes()
    {
        return $this->scopeConfig->getValue(
            'progressivewebapp/manifest/icon_sizes',
            ScopeInterface::SCOPE_STORE,
            $this->getStore()->getId()
        );
    }

    /**
     * @return mixed
     * @throws NoSuchEntityException
     */
    public function getFirebaseScript()
    {
        return $this->scopeConfig->getValue(
            'progressivewebapp/notification/fcm_config',
            ScopeInterface::SCOPE_STORE,
            $this->getStore()->getId()
        );
    }

    /**
     * @return mixed
     * @throws NoSuchEntityException
     */
    public function getServerKey()
    {
        return $this->scopeConfig->getValue(
            'progressivewebapp/notification/fcm_server_key',
            ScopeInterface::SCOPE_STORE,
            $this->getStore()->getId()
        );
    }

    /**
     * @return mixed
     * @throws NoSuchEntityException
     */
    public function getTopicName()
    {
        return $this->scopeConfig->getValue(
            'progressivewebapp/notification/fcm_topic_name',
            ScopeInterface::SCOPE_STORE,
            $this->getStore()->getId()
        );
    }

    /**
     * @return mixed
     * @throws NoSuchEntityException
     */
    public function getFcmVersion()
    {
        return $this->scopeConfig->getValue(
            'progressivewebapp/notification/fcm_version',
            ScopeInterface::SCOPE_STORE,
            $this->getStore()->getId()
        );
    }

    /**
     * @return mixed
     * @throws NoSuchEntityException
     */
    public function getMessageSenderId()
    {
        return $this->scopeConfig->getValue(
            'progressivewebapp/notification/fcm_messaging_sender_id',
            ScopeInterface::SCOPE_STORE,
            $this->getStore()->getId()
        );
    }

    /**
     * @return LoggerInterface
     */
    public function logger()
    {
        return $this->_logger;
    }
}