<?php
/**
 * @author    Tigren Solutions <info@tigren.com>
 * @copyright Copyright (c) 2019 Tigren Solutions <https://www.tigren.com>. All rights reserved.
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Tigren\CatalogGraphQl\Override\Magento\Catalog\Model\CustomOptions;

use Magento\Catalog\Model\CustomOptions\CustomOptionFactory;
use Magento\Catalog\Model\Product\Option;
use Magento\Framework\DataObject\Factory;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Quote\Api\Data\CartItemInterface;
use Magento\Quote\Api\Data\ProductOptionExtensionFactory;
use Magento\Quote\Model\Quote\ProductOptionFactory;

/**
 * Class CustomOptionProcessor
 * @package Tigren\CatalogGraphQl\Override\Magento\Catalog\Model\CustomOptions
 */
class CustomOptionProcessor extends \Magento\Catalog\Model\CustomOptions\CustomOptionProcessor
{

    /**
     * @var Option
     */
    protected $option;

    /**
     * CustomOptionProcessor constructor.
     * @param Factory $objectFactory
     * @param ProductOptionFactory $productOptionFactory
     * @param ProductOptionExtensionFactory $extensionFactory
     * @param CustomOptionFactory $customOptionFactory
     * @param Option $option
     * @param Json|null $serializer
     */
    public function __construct(
        Factory $objectFactory,
        ProductOptionFactory $productOptionFactory,
        ProductOptionExtensionFactory $extensionFactory,
        CustomOptionFactory $customOptionFactory,
        Option $option,
        Json $serializer = null
    ) {
        parent::__construct($objectFactory, $productOptionFactory, $extensionFactory, $customOptionFactory,
            $serializer);
        $this->option = $option;
    }

    /**
     * @param CartItemInterface $cartItem
     * @return null
     */
    public function convertToBuyRequest(CartItemInterface $cartItem)
    {
        if ($cartItem->getProductOption()
            && $cartItem->getProductOption()->getExtensionAttributes()
            && $cartItem->getProductOption()->getExtensionAttributes()->getCustomOptions()) {
            $customOptions = $cartItem->getProductOption()->getExtensionAttributes()->getCustomOptions();
            if (!empty($customOptions) && is_array($customOptions)) {
                $requestData = [];
                foreach ($customOptions as $option) {
                    if (isset($requestData['options'][$option->getOptionId()])) {
                        array_push($requestData['options'][$option->getOptionId()], $option->getOptionValue());
                    } else {
                        if ($this->_isMultiSelect($option->getOptionId())) {
                            $requestData['options'][$option->getOptionId()] = [$option->getOptionValue()];
                        } else {
                            $requestData['options'][$option->getOptionId()] = $option->getOptionValue();
                        }

                    }

                }
                return $this->objectFactory->create($requestData);
            }
        }
        return null;
    }

    /**
     * @param $optionId
     * @return bool
     */
    protected function _isMultiSelect($optionId)
    {
        $option = $this->option->load($optionId);
        $multiSelects = [
            'multiple',
            'checkbox'
        ];
        return in_array($option->getType(), $multiSelects, true);
    }

}
