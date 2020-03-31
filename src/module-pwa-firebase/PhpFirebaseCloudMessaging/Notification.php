<?php
/**
 * @author    Tigren Solutions <info@tigren.com>
 * @copyright Copyright (c) 2019 Tigren Solutions <https://www.tigren.com>. All rights reserved.
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Tigren\ProgressiveWebApp\PhpFirebaseCloudMessaging;

/**
 * @link https://firebase.google.com/docs/cloud-messaging/http-server-ref#notification-payload-support
 */
class Notification extends Message
{
    /**
     * @var string
     */
    private $title;

    /**
     * @var string
     */
    private $body;

    /**
     * @var
     */
    private $badge;

    /**
     * @var
     */
    private $icon;

    /**
     * @var
     */
    private $color;

    /**
     * @var
     */
    private $sound;

    /**
     * @var
     */
    private $clickAction;

    /**
     * @var
     */
    private $tag;

    /**
     * Notification constructor.
     * @param string $title
     * @param string $body
     */
    public function __construct($title = '', $body = '')
    {
        if ($title) {
            $this->title = $title;
        }
        if ($body) {
            $this->body = $body;
        }
        parent::__construct();
    }

    /**
     * @param $title
     * @return $this
     */
    public function setTitle($title)
    {
        $this->title = $title;
        return $this;
    }

    /**
     * @param $body
     * @return $this
     */
    public function setBody($body)
    {
        $this->body = $body;
        return $this;
    }

    /**
     * iOS only, will add smal red bubbles indicating the number of notifications to your apps icon
     *
     * @param integer $badge
     * @return $this
     */
    public function setBadge($badge)
    {
        $this->badge = $badge;
        return $this;
    }

    /**
     * android only, set the name of your drawable resource as string
     *
     * @param string $icon
     * @return $this
     */
    public function setIcon($icon)
    {
        $this->icon = $icon;
        return $this;
    }

    /**
     * android only, set the color background resource as string
     *
     * @param string $color
     * @return $this
     */
    public function setColor($color)
    {
        $this->color = $color;
        return $this;
    }

    /**
     * @param $actionName
     * @return $this
     */
    public function setClickAction($actionName)
    {
        $this->clickAction = $actionName;
        return $this;
    }

    /**
     * @param $sound
     * @return $this
     */
    public function setSound($sound)
    {
        $this->sound = $sound;
        return $this;
    }

    /**
     * @param $tag
     * @return $this
     */
    public function setTag($tag)
    {
        $this->tag = $tag;
        return $this;
    }

    /**
     * @return bool
     */
    public function hasNotificationData()
    {
        return $this->title || $this->body || $this->badge || $this->icon || $this->clickAction || $this->sound || $this->tag;
    }

    /**
     * @return array|mixed
     */
    public function jsonSerialize()
    {
        $jsonData = [];
        if ($this->title) {
            $jsonData['title'] = $this->title;
        }
        if ($this->body) {
            $jsonData['body'] = $this->body;
        }
        if ($this->badge) {
            $jsonData['badge'] = $this->badge;
        }
        if ($this->icon) {
            $jsonData['icon'] = $this->icon;
        }
        if ($this->color) {
            $jsonData['color'] = $this->color;
        }
        if ($this->clickAction) {
            $jsonData['click_action'] = $this->clickAction;
        }
        if ($this->sound) {
            $jsonData['sound'] = $this->sound;
        }
        if ($this->tag) {
            $jsonData['tag'] = $this->tag;
        }
        return $jsonData;
    }
}
