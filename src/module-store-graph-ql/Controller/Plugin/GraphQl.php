<?php
/**
 * @author    Tigren Solutions <info@tigren.com>
 * @copyright Copyright (c) 2019 Tigren Solutions <https://www.tigren.com>. All rights reserved.
 * @license   Open Software License ("OSL") v. 3.0
 */
declare(strict_types=1);

namespace Tigren\StoreGraphQl\Controller\Plugin;

use Magento\Framework\App\FrontControllerInterface;
use Magento\Framework\App\Request\Http;
use Magento\Framework\App\RequestInterface;
use Tigren\StoreGraphQl\Controller\HttpRequestProcessor;

/**
 * Plugin for handling controller after controller tags and pre-controller validation.
 */
class GraphQl
{
    /**
     * @var HttpRequestProcessor
     */
    private $requestProcessor;

    /**
     * @param HttpRequestProcessor $requestProcessor
     */
    public function __construct(
        HttpRequestProcessor $requestProcessor
    ) {
        $this->requestProcessor = $requestProcessor;
    }

    /**
     * Process graphql headers
     *
     * @param FrontControllerInterface $subject
     * @param RequestInterface $request
     * @return void
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function beforeDispatch(
        FrontControllerInterface $subject,
        RequestInterface $request
    ) {
        /** @var Http $request */
        $this->requestProcessor->processHeaders($request);
    }
}
