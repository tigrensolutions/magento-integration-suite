<?php
/**
 * @author    Tigren Solutions <info@tigren.com>
 * @copyright Copyright (c) 2019 Tigren Solutions <https://www.tigren.com>. All rights reserved.
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Tigren\ProgressiveWebApp\PhpFirebaseCloudMessaging;

use GuzzleHttp;
use GuzzleHttp\Exception\RequestException;
use Psr\Http\Message\ResponseInterface;

/**
 * @author sngrl
 */
class Client implements ClientInterface
{
    /**
     *
     */
    const DEFAULT_API_URL = 'https://fcm.googleapis.com/fcm/send';

    /**
     *
     */
    const DEFAULT_TOPIC_ADD_SUBSCRIPTION_API_URL = 'https://iid.googleapis.com/iid/v1:batchAdd';

    /**
     *
     */
    const DEFAULT_TOPIC_REMOVE_SUBSCRIPTION_API_URL = 'https://iid.googleapis.com/iid/v1:batchRemove';

    /**
     * @var
     */
    private $apiKey;

    /**
     * @var
     */
    private $proxyApiUrl;

    /**
     * @var GuzzleHttp\Client
     */
    private $guzzleClient;

    /**
     * Client constructor.
     */
    public function __construct()
    {

        $this->guzzleClient = new GuzzleHttp\Client();
    }

    /**
     * add your server api key here
     * read how to obtain an api key here: https://firebase.google.com/docs/server/setup#prerequisites
     *
     * @param string $apiKey
     *
     * @return \sngrl\PhpFirebaseCloudMessaging\Client
     */
    public function setApiKey($apiKey)
    {
        $this->apiKey = $apiKey;
        return $this;
    }

    /**
     * people can overwrite the api url with a proxy server url of their own
     *
     * @param string $url
     *
     * @return \sngrl\PhpFirebaseCloudMessaging\Client
     */
    public function setProxyApiUrl($url)
    {
        $this->proxyApiUrl = $url;
        return $this;
    }

    /**
     * sends your notification to the google servers and returns a guzzle repsonse object
     * containing their answer.
     *
     * @param Message $message
     *
     * @return ResponseInterface
     * @throws RequestException
     */
    public function send(Message $message)
    {
        return $this->guzzleClient->post(
            $this->getApiUrl(),
            [
                'headers' => [
                    'Authorization' => sprintf('key=%s', $this->apiKey),
                    'Content-Type' => 'application/json'
                ],
                'body' => json_encode($message)
            ]
        );
    }

    /**
     * @return string
     */
    private function getApiUrl()
    {
        return isset($this->proxyApiUrl) ? $this->proxyApiUrl : self::DEFAULT_API_URL;
    }

    /**
     * @param integer $topic_id
     * @param array|string $recipients_tokens
     *
     * @return ResponseInterface
     */
    public function addTopicSubscription($topic_id, $recipients_tokens)
    {
        return $this->processTopicSubscription($topic_id, $recipients_tokens,
            self::DEFAULT_TOPIC_ADD_SUBSCRIPTION_API_URL);
    }

    /**
     * @param integer $topic_id
     * @param array|string $recipients_tokens
     * @param string $url
     *
     * @return ResponseInterface
     */
    protected function processTopicSubscription($topic_id, $recipients_tokens, $url)
    {
        if (!is_array($recipients_tokens)) {
            $recipients_tokens = [$recipients_tokens];
        }

        return $this->guzzleClient->post(
            $url,
            [
                'headers' => [
                    'Authorization' => sprintf('key=%s', $this->apiKey),
                    'Content-Type' => 'application/json'
                ],
                'body' => json_encode([
                    'to' => '/topics/' . $topic_id,
                    'registration_tokens' => $recipients_tokens,
                ])
            ]
        );
    }

    /**
     * @param integer $topic_id
     * @param array|string $recipients_tokens
     *
     * @return ResponseInterface
     */
    public function removeTopicSubscription($topic_id, $recipients_tokens)
    {
        return $this->processTopicSubscription($topic_id, $recipients_tokens,
            self::DEFAULT_TOPIC_REMOVE_SUBSCRIPTION_API_URL);
    }
}
