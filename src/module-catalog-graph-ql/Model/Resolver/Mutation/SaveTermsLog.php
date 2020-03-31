<?php
/**
 * @author    Tigren Solutions <info@tigren.com>
 * @copyright Copyright (c) 2019 Tigren Solutions <https://www.tigren.com>. All rights reserved.
 * @license   Open Software License ("OSL") v. 3.0
 */

declare(strict_types=1);

namespace Tigren\CatalogGraphQl\Model\Resolver\Mutation;

use Magento\CatalogSearch\Helper\Data as HelperData;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Search\Model\Query;
use Magento\Search\Model\QueryFactory;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Save terms log
 */
class SaveTermsLog implements ResolverInterface
{
    /**
     * @var RequestInterface
     */
    protected $_request;
    /**
     * @var StoreManagerInterface
     */
    private $storeManager;
    /**
     * Catalog search helper
     *
     * @var HelperData
     */
    private $catalogSearchHelper;
    /**
     * @var QueryFactory
     */
    private $queryFactory;

    /**
     * @param HelperData $catalogSearchHelper
     * @param StoreManagerInterface $storeManager
     * @param QueryFactory $queryFactory
     * @param RequestInterface $request
     */
    public function __construct(
        HelperData $catalogSearchHelper,
        StoreManagerInterface $storeManager,
        QueryFactory $queryFactory,
        RequestInterface $request
    ) {
        $this->storeManager = $storeManager;
        $this->catalogSearchHelper = $catalogSearchHelper;
        $this->queryFactory = $queryFactory;
        $this->_request = $request;
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
        if (empty($args['query'])) {
            throw new GraphQlInputException(__('Specify the "query" value.'));
        }
        $this->_request->setParam('q', $args['query']);
        /* @var $query Query */
        $query = $this->queryFactory->get();

        $query->setStoreId($this->storeManager->getStore()->getId());

        if ($query->getQueryText() != '') {
            try {
                if ($this->catalogSearchHelper->isMinQueryLength()) {
                    $query->setId(0)->setIsActive(1)->setIsProcessed(1);
                } else {
                    $query->saveIncrementalPopularity();
                }
                return true;
            } catch (LocalizedException $e) {
                return false;
            }
        }
        return false;
    }
}
