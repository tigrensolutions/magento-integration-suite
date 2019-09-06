<?php
/**
 * @author Tigren Solutions <info@tigren.com>
 * @copyright Copyright (c) 2019 Tigren Solutions <https://www.tigren.com>. All rights reserved.
 * @license Open Software License ("OSL") v. 3.0
 */

namespace Tigren\ProgressiveWebApp\Helper;

class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    protected $_storeManager;

    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Store\Model\StoreManagerInterface $storeManager
    ) {
        $this->_storeManager = $storeManager;
        parent::__construct($context);
    }

    public function getStore()
    {
        return $this->_storeManager->getStore();
    }

    public function isEnabled()
    {
        return $this->scopeConfig->getValue(
            'progressivewebapp/general/enable',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $this->getStore()->getId()
        );
    }

    public function getManifestShortName()
    {
        return $this->scopeConfig->getValue(
            'progressivewebapp/manifest/short_name',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $this->getStore()->getId()
        );
    }

    public function getManifestName()
    {
        return $this->scopeConfig->getValue(
            'progressivewebapp/manifest/name',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $this->getStore()->getId()
        );
    }

    public function getManifestDescription()
    {
        return $this->scopeConfig->getValue(
            'progressivewebapp/manifest/description',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $this->getStore()->getId()
        );
    }

    public function getManifestStartUrl()
    {
        return $this->scopeConfig->getValue(
            'progressivewebapp/manifest/start_url',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $this->getStore()->getId()
        );
    }

    public function getManifestThemeColor()
    {
        return $this->scopeConfig->getValue(
            'progressivewebapp/manifest/theme_color',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $this->getStore()->getId()
        );
    }

    public function getManifestBgColor()
    {
        return $this->scopeConfig->getValue(
            'progressivewebapp/manifest/background_color',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $this->getStore()->getId()
        );
    }

    public function getManifestDisplayType()
    {
        return $this->scopeConfig->getValue(
            'progressivewebapp/manifest/display_type',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $this->getStore()->getId()
        );
    }

    public function getManifestOrientation()
    {
        return $this->scopeConfig->getValue(
            'progressivewebapp/manifest/orientation',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $this->getStore()->getId()
        );
    }

    public function getManifestIcon()
    {
        return $this->scopeConfig->getValue(
            'progressivewebapp/manifest/icon',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $this->getStore()->getId()
        );
    }

    public function getManifestIconSizes()
    {
        return $this->scopeConfig->getValue(
            'progressivewebapp/manifest/icon_sizes',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $this->getStore()->getId()
        );
    }

    public function getFirebaseScript()
    {
        return $this->scopeConfig->getValue(
            'progressivewebapp/notification/fcm_config',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $this->getStore()->getId()
        );
    }

    public function getServerKey()
    {
        return $this->scopeConfig->getValue(
            'progressivewebapp/notification/fcm_server_key',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $this->getStore()->getId()
        );
    }

    public function getTopicName()
    {
        return $this->scopeConfig->getValue(
            'progressivewebapp/notification/fcm_topic_name',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $this->getStore()->getId()
        );
    }

    public function getFcmVersion()
    {
        return $this->scopeConfig->getValue(
            'progressivewebapp/notification/fcm_version',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $this->getStore()->getId()
        );
    }

    public function getMessageSenderId()
    {
        return $this->scopeConfig->getValue(
            'progressivewebapp/notification/fcm_messaging_sender_id',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $this->getStore()->getId()
        );
    }

    public function logger()
    {
        return $this->_logger;
    }

}