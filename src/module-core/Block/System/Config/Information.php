<?php
/**
 * @author Tigren Solutions <info@tigren.com>
 * @copyright Copyright (c) 2019 Tigren Solutions <https://www.tigren.com>. All rights reserved.
 * @license Open Software License ("OSL") v. 3.0
 */

namespace Tigren\Core\Block\System\Config;

class Information extends \Magento\Config\Block\System\Config\Form\Fieldset
{
    /**
     * @var \Magento\Config\Block\System\Config\Form\Field
     */
    protected $_fieldRenderer;

    /**
     * @var \Magento\Framework\Module\ModuleListInterface
     */
    protected $_moduleList;

    /**
     * @var \Magento\Framework\Module\ModuleResource
     */
    private $moduleResource;

    /**
     * @param \Magento\Backend\Block\Context $context
     * @param \Magento\Backend\Model\Auth\Session $authSession
     * @param \Magento\Framework\View\Helper\Js $jsHelper
     * @param \Magento\Framework\Module\ModuleListInterface $moduleList
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Context $context,
        \Magento\Backend\Model\Auth\Session $authSession,
        \Magento\Framework\View\Helper\Js $jsHelper,
        \Magento\Framework\Module\ModuleListInterface $moduleList,
        \Magento\Framework\Module\ModuleResource $moduleResource,
        array $data = []
    ) {
        parent::__construct($context, $authSession, $jsHelper, $data);
        $this->_moduleList = $moduleList;
        $this->moduleResource = $moduleResource;
    }

    /**
     * @param \Magento\Framework\Data\Form\Element\AbstractElement $element
     * @return string
     */
    public function render(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        $html = $this->_getHeaderHtml($element);

        $html .= $this->_getInfo();

        $html .= $this->_getFooterHtml($element);

        return $html;
    }

    protected function _getInfo()
    {
        $html = '<div class="support-info">';
        $html .= '  <h3>Support Policy</h3>';
        $html .= '  <p>Tigren provides 3-month free support for all of our extensions. We are not responsible for any bug or issue caused by your changes to our products. To report a bug, please send your email to: <a href="mailto:support@tigren.com" title="Tigren Support" target="_top">support@tigren.com</a></p>';
        $html .= '  <h3>Tigren\'s Blog</h3>';
        $html .= '  <p>We will be updating this blog on a regular basis to include you in new thinking and ideas emerging at Tigren, as well as to keep you updated with what’s going on in the e-commerce world. The blog is full with industry news, tutorials, hot releases, updates, promotions and so on. Let’s visit our blog to be kept updated!</p>';
        $html .= '  <h3>Follow Us</h3>';
        $html .= '  <div class="tigren-follow"><ul><li class="facebook"><a href="https://www.facebook.com/TigrenSolutions/" title="Facebook" target="_blank"><img src="' . $this->getViewFileUrl('Tigren_Core::images/facebook.png') . '" alt="Facebook"/></a></li><li class="twitter"><a href="https://twitter.com/Tigren5" title="Twitter" target="_blank"><img src="' . $this->getViewFileUrl('Tigren_Core::images/twitter.png') . '" alt="Twitter"/></a></li></ul></div>';
        $html .= '</div>';

        return $html;
    }
}
