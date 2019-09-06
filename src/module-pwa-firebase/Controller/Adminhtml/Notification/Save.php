<?php
/**
 * @author Tigren Solutions <info@tigren.com>
 * @copyright Copyright (c) 2019 Tigren Solutions <https://www.tigren.com>. All rights reserved.
 * @license Open Software License ("OSL") v. 3.0
 */
namespace Tigren\ProgressiveWebApp\Controller\Adminhtml\Notification;

use Magento\Backend\App\Action;

class Save extends \Magento\Backend\App\Action
{
    protected $_date;
    protected $jsHelper;
    protected $_productFactory;
    protected $_file;

    public function __construct(
        Action\Context $context,
        \Magento\Framework\Stdlib\DateTime\DateTime $date,
        \Magento\Backend\Helper\Js $jsHelper,
        \Magento\Framework\Filesystem\Driver\File $file
    ) {
        parent::__construct($context);
        $this->_date = $date;
        $this->jsHelper = $jsHelper;
        $this->_file = $file;
    }

    public function execute()
    {
        $data = $this->getRequest()->getPostValue('notification');
        $notification = $this->_objectManager->create('Tigren\ProgressiveWebApp\Model\Notification');
        $resultRedirect = $this->resultRedirectFactory->create();
        if ($data) {
            if (!empty($data['notification_id'])) {
                $notification->load($data['notification_id']);
                if ($data['notification_id'] != $notification->getId()) {
                    throw new \Magento\Framework\Exception\LocalizedException(__('The wrong notification is specified'));
                }
                $data['modified'] = $this->_date->gmtDate();
            } else {
                $data['created_at'] = $this->_date->gmtDate();
                $data['modified'] = $this->_date->gmtDate();
            }

            if(!empty($data['icon'][0]['name'])){
                $notificationData['icon'] = array();
                $notificationData['icon'][0]['name'] = $data['icon'][0]['name'];
                $notificationData['icon'][0]['url'] = $data['icon'][0]['url'];
                $data['icon'] = serialize($notificationData['icon']);
            } else {
                $data['icon'] = '';
            }
            $notification->setData($data);
            try {
                $notification->save();
                $this->messageManager->addSuccess(__('You saved this Notification'));
                $this->_objectManager->get('Magento\Backend\Model\Session')->setFormData(false);
                if ($this->getRequest()->getParam('back')) {
                    return $resultRedirect->setPath('*/*/edit', ['notification_id' => $notification->getId(), '_current' => true]);
                }
                return $resultRedirect->setPath('*/*/');
            } catch (\Magento\Framework\Exception\LocalizedException $e) {
                $this->messageManager->addError($e->getMessage());
            } catch (\RuntimeException $e) {
                $this->messageManager->addError($e->getMessage());
            } catch (\Exception $e) {
                $this->messageManager->addException($e, __('Something went wrong while saving the notification'));
            }

            if ($data['notification_id']) {
                return $resultRedirect->setPath('*/*/edit', ['notification_id' => $data['notification_id'], '_current' => true]);
            } else {
                return $resultRedirect->setPath('*/*/new', ['_current' => true]);
            }
        }
        return $resultRedirect->setPath('*/*/');
    }

}
