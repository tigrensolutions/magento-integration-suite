<?php
/**
 * @author    Tigren Solutions <info@tigren.com>
 * @copyright Copyright (c) 2019 Tigren Solutions <https://www.tigren.com>. All rights reserved.
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Tigren\Core\Block\System\Config;

use Magento\Backend\Block\Context;
use Magento\Backend\Model\Auth\Session;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Config\Block\System\Config\Form\Fieldset;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\Module\ModuleListInterface;
use Magento\Framework\Module\ModuleResource;
use Magento\Framework\View\Helper\Js;

/**
 * Class Information
 * @package Tigren\Core\Block\System\Config
 */
class Information extends Fieldset
{
    /**
     * @var Field
     */
    protected $_fieldRenderer;

    /**
     * @var ModuleListInterface
     */
    protected $_moduleList;

    /**
     * @var ModuleResource
     */
    private $moduleResource;

    /**
     * @param Context $context
     * @param Session $authSession
     * @param Js $jsHelper
     * @param ModuleListInterface $moduleList
     * @param ModuleResource $moduleResource
     * @param array $data
     */
    public function __construct(
        Context $context,
        Session $authSession,
        Js $jsHelper,
        ModuleListInterface $moduleList,
        ModuleResource $moduleResource,
        array $data = []
    ) {
        parent::__construct($context, $authSession, $jsHelper, $data);
        $this->_moduleList = $moduleList;
        $this->moduleResource = $moduleResource;
    }

    /**
     * @param AbstractElement $element
     * @return string
     */
    public function render(AbstractElement $element)
    {
        $html = $this->_getHeaderHtml($element);

        $html .= $this->_getInfo();

        $html .= $this->_getFooterHtml($element);

        return $html;
    }

    /**
     * @return string
     */
    protected function _getInfo()
    {
        return <<<HTML
    <div class="support-info">
        <p>Founded in 2012, Tigren is one of the top Magento agencies who enable e-commerce success for merchants by offering end-to-end Magento solutions, ranging from consulting, development to support - at an affordable price.</p>
        <br/>
        <p>At Tigren, we take pride in the quality of our work. We are committed to delivering transparent process, effective communication, comprehensive requirement analysis, short feedback loop, and fast issue resolution.</p>
        <br/>
        <h3>Our Outstanding Magento Services:</h3>
        <br/>
        <ul style="padding-left: 20px;">
            <li class="item"><a href="https://www.tigren.com/our-services/magento-development/" title="Magento website development">Magento website development</a></li>
            <li class="item"><a href="https://www.tigren.com/our-services/magento-migration-service/" title="Magento 2 migration">Magento 2 migration</a></li>
            <li class="item"><a href="https://www.tigren.com/our-services/magento-mobile-app-development/" title="Magento mobile app development">Magento mobile app development</a></li>
            <li class="item"><a href="https://www.tigren.com/our-services/magento-2-pwa-theme-integration/" title="Magento 2 PWA integration">Magento 2 PWA integration</a></li>
            <li class="item"><a href="https://www.tigren.com/magento-2-extensions/" title="Magento extension development">Magento extension development</a></li>
            <li class="item"><a href="https://www.tigren.com/our-services/magento-support-services/" title="Magento monthly support">Magento monthly support</a></li>
        </ul>
        <br/>
        <h3>Why Us?</h3>
        <br/>
        <ul style="padding-left: 20px;">
            <li class="item">Quality products followed by amazing after-service support</li>
            <li class="item">Fast project delivery, always on-time or before deadlines</li>
            <li class="item">Affordable price, satisfying even tight budgets</li>
        </ul>
        <br/>
        <p>Up to now, we have implemented hundreds of Magento projects for small-to-large businesses, from various industries and markets, with proven successes. We always try to put ourselves in our clients’ shoes, to understand their pain points and suggest the most proper solutions.</p>
        <br/>
        <p><a href="https://www.tigren.com/contact/" title="Contact Us">Contact us</a> now to get free consultation and estimate for your project!</p>
    </div>
HTML;
    }
}
