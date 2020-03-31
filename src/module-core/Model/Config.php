<?php
/**
 * @author    Tigren Solutions <info@tigren.com>
 * @copyright Copyright (c) 2019 Tigren Solutions <https://www.tigren.com>. All rights reserved.
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Tigren\Core\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

/**
 * Class Config
 * @package Tigren\Core\Model
 */
class Config
{
    /**
     *
     */
    const XML_CONFIG_PWA_SHOW_NEW = 'pwa_connector/general/show_new';

    /**
     *
     */
    const XML_CONFIG_PWA_SHOW_FEATURE = 'pwa_connector/general/show_feature';

    /**
     *
     */
    const XML_CONFIG_PWA_SHOW_BESTSELLER = 'pwa_connector/general/show_bestseller';

    /**
     *
     */
    const XML_CONFIG_PWA_GET_FULL_URL = 'pwa_connector/general/full_url';

    /**
     * @var ScopeConfigInterface
     */
    private $config;

    /**
     * Config constructor.
     * @param ScopeConfigInterface $config
     */
    public function __construct(ScopeConfigInterface $config)
    {
        $this->config = $config;
    }

    /**
     * @return array
     */
    public function showOnHomePage()
    {
        return [
            'new' => $this->getConfig(self::XML_CONFIG_PWA_SHOW_NEW),
            'feature' => $this->getConfig(self::XML_CONFIG_PWA_SHOW_FEATURE),
            'bestseller' => $this->getConfig(self::XML_CONFIG_PWA_SHOW_BESTSELLER)
        ];
    }

    /**
     * @param $path
     * @param null $store
     * @param null $scope
     * @return mixed
     */
    public function getConfig($path, $store = null, $scope = null)
    {
        if ($scope === null) {
            $scope = ScopeInterface::SCOPE_STORE;
        }
        return $this->config->getValue($path, $scope, $store);
    }

    /**
     * @return int
     */
    public function isFullPathImageProduct()
    {
        return (int)$this->getConfig(self::XML_CONFIG_PWA_GET_FULL_URL);
    }
}
