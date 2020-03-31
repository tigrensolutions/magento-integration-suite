<?php
/**
 * @author    Tigren Solutions <info@tigren.com>
 * @copyright Copyright (c) 2019 Tigren Solutions <https://www.tigren.com>. All rights reserved.
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Tigren\Core\Plugin\Magento\Email\Model;

use Magento\Email\Model\Template\Config;
use Magento\Email\Model\Template\FilterFactory;
use Magento\Email\Model\TemplateFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Filesystem;
use Magento\Framework\Filter\FilterManager;
use Magento\Framework\Model\Context;
use Magento\Framework\Registry;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Asset\Repository;
use Magento\Framework\View\DesignInterface;
use Magento\Store\Model\App\Emulation;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Class Template
 * @package Tigren\Core\Plugin\Magento\Email\Model
 */
class Template extends \Magento\Email\Model\Template
{
    protected $request;

    /**
     * Template constructor.
     * @param Context $context
     * @param DesignInterface $design
     * @param Registry $registry
     * @param Emulation $appEmulation
     * @param StoreManagerInterface $storeManager
     * @param Repository $assetRepo
     * @param Filesystem $filesystem
     * @param ScopeConfigInterface $scopeConfig
     * @param Config $emailConfig
     * @param TemplateFactory $templateFactory
     * @param FilterManager $filterManager
     * @param UrlInterface $urlModel
     * @param FilterFactory $filterFactory
     * @param RequestInterface $request
     * @param array $data
     * @param Json|null $serializer
     */
    public function __construct(
        Context $context,
        DesignInterface $design,
        Registry $registry,
        Emulation $appEmulation,
        StoreManagerInterface $storeManager,
        Repository $assetRepo,
        Filesystem $filesystem,
        ScopeConfigInterface $scopeConfig,
        Config $emailConfig,
        TemplateFactory $templateFactory,
        FilterManager $filterManager,
        UrlInterface $urlModel,
        FilterFactory $filterFactory,
        RequestInterface $request,
        array $data = [],
        Json $serializer = null
    ) {
        $this->request = $request;
        parent::__construct(
            $context,
            $design,
            $registry,
            $appEmulation,
            $storeManager,
            $assetRepo,
            $filesystem,
            $scopeConfig,
            $emailConfig,
            $templateFactory,
            $filterManager,
            $urlModel,
            $filterFactory,
            $data,
            $serializer
        );
    }

    /**
     * @param \Magento\Email\Model\Template $subject
     * @param $result
     * @return string|string[]
     * @throws NoSuchEntityException
     */
    public function afterProcessTemplate(\Magento\Email\Model\Template $subject, $result)
    {
        $headers = $this->request->getHeaders()->toArray();
        $baseAppUrl = $this->scopeConfig->getValue('web/unsecure/base_app_url');
        if ($headers && isset($headers['Origin']) && trim($headers['Origin'], '/') == trim($baseAppUrl, '/')) {
            $baseWebUrl = $this->storeManager->getStore()->getBaseUrl();
            $search = '<a href="' . $baseWebUrl;
            if (strpos($result, $search)) {
                $replace = '<a href="' . $baseAppUrl;
                $result = str_replace($search, $replace, $result);
                if (strpos($result, 'customer/account/createPassword/')) {
                    $result = str_replace('customer/account/createPassword/', 'resetpassword.html', $result);
                }
                if (strpos($result, 'customer/account/')) {
                    $result = str_replace('customer/account/', 'customer.html', $result);
                }
            }
        }
        return $result;
    }
}
