<?php
/**
 * @author    Tigren Solutions <info@tigren.com>
 * @copyright Copyright (c) 2019 Tigren Solutions <https://www.tigren.com>. All rights reserved.
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Tigren\ProgressiveWebApp\Controller\Adminhtml\Notification;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Catalog\Controller\Adminhtml\Product\Builder;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Registry;
use Magento\Framework\View\Result\Page;
use Magento\Framework\View\Result\PageFactory;

/**
 * Class Edit
 * @package Tigren\ProgressiveWebApp\Controller\Adminhtml\Notification
 */
class Edit extends Action
{
    /**
     * @var PageFactory
     */
    protected $resultPageFactory;

    /**
     * @var Registry
     */
    protected $_coreRegistry;

    /**
     * @param Context $context
     * @param Builder $productBuilder
     * @param PageFactory $resultPageFactory
     */
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory,
        Registry $registry
    ) {
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
        $this->_coreRegistry = $registry;
    }

    /**
     * Product edit form
     *
     * @return ResultInterface
     */
    public function execute()
    {
        $id = $this->getRequest()->getParam('notification_id');
        $notification = $this->_objectManager->create('Tigren\ProgressiveWebApp\Model\Notification');
        if ($id) {
            $this->_coreRegistry->register('current_notification_id', $id);
            $notification->load($id);
            if (!$notification->getId()) {
                $this->messageManager->addError(__('This notification no longer exists.'));
                $resultRedirect = $this->resultRedirectFactory->create();
                return $resultRedirect->setPath('*/*/');
            }
        }
        $resultPage = $this->_initAction();
        $resultPage->addBreadcrumb(
            $id ? __('Edit Notification') : __('New Notification'),
            $id ? __('Edit Notification') : __('New Notification')
        );
        $resultPage->getConfig()->getTitle()->prepend(__('Notifications'));
        $resultPage->getConfig()->getTitle()
            ->prepend($notification->getId() ? __('Edit ') . $notification->getTitle() : __('New Notification'));
        return $resultPage;
    }

    /**
     * @return Page
     */
    protected function _initAction()
    {
        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu('Tigren_Dailydeal::pwa')
            ->addBreadcrumb(__('Notifications'), __('Notifications'))
            ->addBreadcrumb(__('Manage Notifications'), __('Manage Notifications'));
        return $resultPage;
    }
}
