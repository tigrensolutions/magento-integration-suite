<?php
/**
 * @author    Tigren Solutions <info@tigren.com>
 * @copyright Copyright (c) 2019 Tigren Solutions <https://www.tigren.com>. All rights reserved.
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Tigren\ProgressiveWebApp\Model\System\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

/**
 * Class Orientation
 * @package Tigren\ProgressiveWebApp\Model\System\Config\Source
 */
class Orientation implements OptionSourceInterface
{
    /**
     * @inheritdoc
     */
    public function toOptionArray()
    {
        $options = [];
        foreach ($this->toArray() as $value => $label) {
            $options[] = ["value" => $value, "label" => $label];
        }

        return $options;
    }

    /**
     * Get options in "key=>value" format.
     *
     * @return array
     */
    public function toArray()
    {
        return [
            "any" => __("Any"),
            "natural" => __("Natural"),
            "portrait" => __("Portrait"),
            "landscape" => __("Landscape"),
        ];
    }
}
