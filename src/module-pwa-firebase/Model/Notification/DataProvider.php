<?php
/**
 * @author    Tigren Solutions <info@tigren.com>
 * @copyright Copyright (c) 2019 Tigren Solutions <https://www.tigren.com>. All rights reserved.
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Tigren\ProgressiveWebApp\Model\Notification;

use Magento\Ui\DataProvider\AbstractDataProvider;
use Tigren\ProgressiveWebApp\Model\ResourceModel\Notification\CollectionFactory;

/**
 * Class DataProvider
 * @package Tigren\ProgressiveWebApp\Model\Notification
 */
class DataProvider extends AbstractDataProvider
{
    /**
     * @var array
     */
    protected $loadedData;

    /**
     * @param string $name
     * @param string $primaryFieldName
     * @param string $requestFieldName
     * @param CollectionFactory $notificationCollectionFactory
     * @param array $meta
     * @param array $data
     */
    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        CollectionFactory $notificationCollectionFactory,
        array $meta = [],
        array $data = []
    ) {
        $this->collection = $notificationCollectionFactory->create();
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
    }

    /**
     * @return array
     */
    public function getData()
    {
        if (isset($this->loadedData)) {
            return $this->loadedData;
        }
        $items = $this->collection->getItems();
        foreach ($items as $notification) {
            $notificationData = $notification->getData();
            $iconData = unserialize($notification['icon']);
            if (!empty($iconData)) {
                $notificationData['icon'] = [];
                $notificationData['icon'][0]['name'] = $iconData[0]['name'];
                $notificationData['icon'][0]['url'] = $iconData[0]['url'];
            }
            $this->loadedData[$notification->getId()]['notification'] = $notificationData;
        }
        return $this->loadedData;
    }
}
