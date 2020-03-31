<?php /** @noinspection PhpLanguageLevelInspection */
/**
 * @author    Tigren Solutions <info@tigren.com>
 * @copyright Copyright (c) 2019 Tigren Solutions <https://www.tigren.com>. All rights reserved.
 * @license   Open Software License ("OSL") v. 3.0
 */
declare(strict_types=1);

namespace Tigren\StoreGraphQl\Controller;

/**
 * Use this interface to implement a processor for each entry of a header in an HTTP GraphQL request.
 */
interface HttpHeaderProcessorInterface
{
    /**
     * Perform processing on a list of headers, iteratively.
     *
     * This method should be called even if a header entry is not present on a request
     * to enforce required headers like "application/json"
     *
     * @param string $headerValue
     * @return void
     * @noinspection PhpLanguageLevelInspection
     */
    public function processHeaderValue(string $headerValue): void;
}
