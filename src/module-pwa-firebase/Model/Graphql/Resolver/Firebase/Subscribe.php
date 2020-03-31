<?php
/**
 * @author    Tigren Solutions <info@tigren.com>
 * @copyright Copyright (c) 2019 Tigren Solutions <https://www.tigren.com>. All rights reserved.
 * @license   Open Software License ("OSL") v. 3.0
 */

declare(strict_types=1);

namespace Tigren\ProgressiveWebApp\Model\Graphql\Resolver\Firebase;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Framework\HTTP\Client\Curl;
use Tigren\ProgressiveWebApp\Helper\Data;

/**
 * Class Subscribe
 * @package Tigren\ProgressiveWebApp\Model\Graphql\Resolver\Firebase
 */
class Subscribe implements ResolverInterface
{
    /**
     * @var JsonFactory
     */
    protected $resultJsonFactory;

    /**
     * @var ScopeConfigInterface
     */
    protected $scope;

    /**
     * @var Curl
     */
    protected $_curl;

    /**
     * @var Data
     */
    protected $_pwaHelper;

    /**
     * Subscribe constructor.
     * @param JsonFactory $resultJsonFactory
     * @param ScopeConfigInterface $scope
     * @param Curl $curl
     * @param Data $pwaHelper
     */
    public function __construct(
        JsonFactory $resultJsonFactory,
        ScopeConfigInterface $scope,
        Curl $curl,
        Data $pwaHelper
    ) {
        $this->resultJsonFactory = $resultJsonFactory;
        $this->scope = $scope;
        $this->_curl = $curl;
        $this->_pwaHelper = $pwaHelper;
    }

    /**
     * @inheritdoc
     */
    public function resolve(
        Field $field,
        $context,
        ResolveInfo $info,
        array $value = null,
        array $args = null
    ) {
        if (!isset($args['token'])) {
            throw new GraphQlInputException(__('Specify the "token" value.'));
        }
        $topicName = $this->_pwaHelper->getTopicName();
        $serverKey = $this->_pwaHelper->getServerKey();
        $subscribeUrl = 'https://iid.googleapis.com/iid/v1/' . $args['token'] . '/rel/topics/' . $topicName;
        $this->_curl->addHeader('Content-Type', 'application/json');
        $this->_curl->addHeader('Authorization', 'key=' . $serverKey);
        $this->_curl->post($subscribeUrl, []);
        return true;
    }
}
