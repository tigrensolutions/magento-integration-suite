<?php
/**
 * @author Tigren Solutions <info@tigren.com>
 * @copyright Copyright (c) 2019 Tigren Solutions <https://www.tigren.com>. All rights reserved.
 * @license Open Software License ("OSL") v. 3.0
 */

namespace Tigren\Core\Block\System\Config;

use Magento\Backend\Block\Context;
use Magento\Backend\Model\Auth\Session;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Config\Block\System\Config\Form\Fieldset;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\DataObject;
use Magento\Framework\Module\ModuleListInterface;
use Magento\Framework\Module\ModuleResource;
use Magento\Framework\View\Helper\Js;

/**
 * Class Extensions
 * @package Tigren\Core\Block\System\Config
 */
class Extensions extends Fieldset
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

        $modules = $this->_moduleList->getNames();

        $dispatchResult = new DataObject($modules);

        $modules = $dispatchResult->toArray();

        sort($modules);

        foreach ($modules as $moduleName) {
            if (strstr($moduleName, 'Tigren_') === false) {
                continue;
            }
            if ($moduleName === 'Tigren_Core') {
                continue;
            }
            $html .= $this->_getFieldHtml($element, $moduleName);
        }
        $html .= $this->_getFooterHtml($element);

        return $html;
    }

    /**
     * @param $fieldset
     * @param $moduleCode
     * @return string
     */
    protected function _getFieldHtml($fieldset, $moduleCode)
    {
        $currentVer = $this->moduleResource->getDataVersion($moduleCode);

        if (!$currentVer) {
            return '';
        }

        $moduleName = substr($moduleCode, strpos($moduleCode, '_') + 1);

        $status = '<a  target="_blank"><img src="' . $this->getViewFileUrl('Tigren_Core::images/ok.gif') . '" title="' . __("Installed") . '"/></a>';

        $moduleName = '<span class="extension-name">' . $moduleName . '</span>';

        $moduleName = $status . ' ' . $moduleName;

        $field = $fieldset->addField($moduleCode, 'label', [
            'name' => 'dummy',
            'label' => $moduleName,
            'value' => $currentVer,
        ])->setRenderer($this->_getFieldRenderer());

        return $field->toHtml();
    }

    /**
     * @return Field
     */
    protected function _getFieldRenderer()
    {
        if (empty($this->_fieldRenderer)) {
            $this->_fieldRenderer = $this->_layout->getBlockSingleton(
                'Magento\Config\Block\System\Config\Form\Field'
            );
        }
        return $this->_fieldRenderer;
    }
}
