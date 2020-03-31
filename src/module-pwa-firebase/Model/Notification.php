<?php
/**
 * @author    Tigren Solutions <info@tigren.com>
 * @copyright Copyright (c) 2019 Tigren Solutions <https://www.tigren.com>. All rights reserved.
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Tigren\ProgressiveWebApp\Model;

use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Registry;

/**
 * Class Notification
 * @package Tigren\ProgressiveWebApp\Model
 */
class Notification extends AbstractModel
{
    /**
     * CMS page cache tag
     */
    const CACHE_TAG = 'pwa_notification';

    /**
     * @var string
     */
    protected $_cacheTag = 'pwa_notification';

    /**
     * Prefix of model name
     *
     * @var string
     */
    protected $_notificationPrefix = 'pwa_notification';

    /**
     * Notification constructor.
     * @param Context $context
     * @param Registry $registry
     * @param AbstractResource|null $resource
     * @param AbstractDb|null $resourceCollection
     * @param array $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        AbstractResource $resource = null,
        AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
    }

    /**
     *
     */
    protected function _construct()
    {
        $this->_init('Tigren\ProgressiveWebApp\Model\ResourceModel\Notification');
    }
}
