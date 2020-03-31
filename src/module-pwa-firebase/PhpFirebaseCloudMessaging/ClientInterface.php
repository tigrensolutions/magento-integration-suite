<?php
/**
 * @author    Tigren Solutions <info@tigren.com>
 * @copyright Copyright (c) 2019 Tigren Solutions <https://www.tigren.com>. All rights reserved.
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Tigren\ProgressiveWebApp\PhpFirebaseCloudMessaging;

use GuzzleHttp\Exception\RequestException;
use Psr\Http\Message\ResponseInterface;

/**
 *
 * @author sngrl
 *
 */
interface ClientInterface
{
    /**
     * add your server api key here
     * read how to obtain an api key here: https://firebase.google.com/docs/server/setup#prerequisites
     *
     * @param string $apiKey
     *
     * @return \sngrl\PhpFirebaseCloudMessaging\Client
     */
    public function setApiKey($apiKey);


    /**
     * people can overwrite the api url with a proxy server url of their own
     *
     * @param string $url
     *
     * @return \sngrl\PhpFirebaseCloudMessaging\Client
     */
    public function setProxyApiUrl($url);

    /**
     * sends your notification to the google servers and returns a guzzle repsonse object
     * containing their answer.
     *
     * @param Message $message
     *
     * @return ResponseInterface
     * @throws RequestException
     */
    public function send(Message $message);
}
