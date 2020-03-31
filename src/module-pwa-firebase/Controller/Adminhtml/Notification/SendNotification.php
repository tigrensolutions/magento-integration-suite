<?php
/**
 * @author    Tigren Solutions <info@tigren.com>
 * @copyright Copyright (c) 2019 Tigren Solutions <https://www.tigren.com>. All rights reserved.
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Tigren\ProgressiveWebApp\Controller\Adminhtml\Notification;

use Exception;
use Magento\Backend\App\Action;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use RuntimeException;
use Tigren\ProgressiveWebApp\Helper\Data;
use Tigren\ProgressiveWebApp\Model\NotificationFactory;
use Tigren\ProgressiveWebApp\PhpFirebaseCloudMessaging\Client;
use Tigren\ProgressiveWebApp\PhpFirebaseCloudMessaging\Message;
use Tigren\ProgressiveWebApp\PhpFirebaseCloudMessaging\Notification;
use Tigren\ProgressiveWebApp\PhpFirebaseCloudMessaging\Recipient\Topic;

/**
 * Class SendNotification
 * @package Tigren\ProgressiveWebApp\Controller\Adminhtml\Notification
 */
class SendNotification extends Action
{
    /**
     * @var ScopeConfigInterface
     */
    protected $scope;

    /**
     * @var Client
     */
    protected $_client;

    /**
     * @var Message
     */
    protected $_message;

    /**
     * @var
     */
    protected $_notification;

    /**
     * @var Data
     */
    protected $_pwaHelper;

    /**
     * @var NotificationFactory
     */
    protected $_notificationFactory;

    /**
     * SendNotification constructor.
     * @param Action\Context $context
     * @param ScopeConfigInterface $scope
     * @param Client $client
     * @param Message $message
     * @param Data $helper
     * @param NotificationFactory $notificationFactory
     */
    public function __construct(
        Action\Context $context,
        ScopeConfigInterface $scope,
        Client $client,
        Message $message,
        Data $helper,
        NotificationFactory $notificationFactory
    ) {
        $this->scope = $scope;
        $this->_client = $client;
        $this->_message = $message;
        $this->_pwaHelper = $helper;
        $this->_notificationFactory = $notificationFactory;
        return parent::__construct($context);
    }

    /**
     * @return ResponseInterface|Redirect|ResultInterface
     * @throws NoSuchEntityException
     */
    public function execute()
    {
        $resultRedirect = $this->resultRedirectFactory->create();
        $notificationId = $this->getRequest()->getParam('notification_id');
        $notification = $this->_notificationFactory->create()->load($notificationId);
        $notificationData = $notification->getData();
        if (!empty($notificationData)) {
            $icon = unserialize($notificationData['icon']);
            $server_key = $this->_pwaHelper->getServerKey();
            $topicName = $this->_pwaHelper->getTopicName();
            $this->_client->setApiKey($server_key);
            $this->_notification = new Notification($notificationData['title'], $notificationData['body']);
            $this->_message->addRecipient(new Topic($topicName));
            $this->_message->setNotification($this->_notification);
            $this->_notification->setIcon($icon[0]['url']);
            $this->_notification->setClickAction($notificationData['target_url']);
            try {
                $send = $this->_client->send($this->_message);
                $reponse = $send->getStatusCode();
                if ($reponse == 200) {
                    $this->messageManager->addSuccess(__('Send notification successfully.'));
                    return $resultRedirect->setPath('*/*/');
                } else {
                    $this->messageManager->addError(__('Send notification failed. Please try again.'));
                    return $resultRedirect->setPath('*/*/');
                }
            } catch (LocalizedException $e) {
                $this->messageManager->addError($e->getMessage());
            } catch (RuntimeException $e) {
                $this->messageManager->addError($e->getMessage());
            } catch (Exception $e) {
                $this->messageManager->addException($e, __('Something went wrong while sending notification.'));
            }
        } else {
            $this->messageManager->addError(__('Cannot send the notification. The notification does not exists.'));
        }
        return $resultRedirect->setPath('*/*/');
    }
}