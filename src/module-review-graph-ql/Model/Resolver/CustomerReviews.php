<?php
/**
 * @author    Tigren Solutions <info@tigren.com>
 * @copyright Copyright (c) 2019 Tigren Solutions <https://www.tigren.com>. All rights reserved.
 * @license   Open Software License ("OSL") v. 3.0
 */
declare(strict_types=1);

namespace Tigren\ReviewGraphQl\Model\Resolver;

use Magento\Framework\GraphQl\Exception\GraphQlAuthorizationException;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\Exception\LocalizedException;


class CustomerReviews implements ResolverInterface
{
    /**
     * Review resource model
     *
     * @var \Magento\Review\Model\ResourceModel\Review\Product\CollectionFactory
     */
    protected $_collectionFactory;

    /**
     * Store manager
     *
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface
     */
    protected $_localeDate;

    public function __construct(
        \Magento\Review\Model\ResourceModel\Review\Product\CollectionFactory $collectionFactory,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone,
        \Magento\Store\Model\StoreManagerInterface $storeManager
    ) {
        $this->_localeDate = $timezone;
        $this->_collectionFactory = $collectionFactory;
        $this->_storeManager = $storeManager;
    }

    /**
     * @inheritdoc
     */
    public function resolve(Field $field, $context, ResolveInfo $info, array $value = null, array $args = null)
    {
        /** @var \Magento\GraphQl\Model\Query\ContextInterface $context */
        if (false === $context->getExtensionAttributes()->getIsCustomer()) {
            throw new GraphQlAuthorizationException(__('The current customer isn\'t authorized.'));
        }
        if ($args['currentPage'] < 1) {
            throw new GraphQlInputException(__('currentPage value must be greater than 0.'));
        }
        if ($args['pageSize'] < 1) {
            throw new GraphQlInputException(__('pageSize value must be greater than 0.'));
        }
        $reviewData = [];
        $customerId = $context->getUserId();
        $collection = $this->_collectionFactory->create()
            ->addStoreFilter($this->_storeManager->getStore()->getId())
            ->addCustomerFilter($customerId)
            ->setDateOrder();
        $totalCount = $collection->getSize();
        $pageSize = $args['pageSize'];
        $curPage = $args['currentPage'];
        $pageInfo = [
            'page_size' => $pageSize,
            'current_page' => $curPage,
            'total_pages' => ceil($totalCount / $pageSize)
        ];
        $collection->setPageSize($pageSize)->setCurPage($curPage);
        $collection->load()->addReviewSummary();
        foreach ($collection as $review) {
            $reviewData[] = $this->getItemData($review);
        }
        $data = [
            'total_count' => $totalCount,
            'items' => $reviewData ?: null,
            'page_info' => $pageInfo
        ];
        return $data;
    }
    /**
     *@param \Magento\Review\Model\Review $review
     */
    private function getItemData($review)
    {
        $writer = new \Zend\Log\Writer\Stream(BP . '/var/log/review.log');
        $logger = new \Zend\Log\Logger();
        $logger->addWriter($writer);
        $logger->info(print_r($review->getData(), true));
        return [
            'id' => $review->getReviewId(),
            'created_at' => $this->formatDate($review->getReviewCreatedAt()),
            'product_id' => $review->getEntityPkValue(),
            'product_name' => $review->getName(),
            'rating' => $review->getSum()?((int)$review->getSum() / (int)$review->getCount())*5/100:null,
            'detail' => $review->getDetail(),
            'product_url' => $review->getProductUrl($review->getEntityPkValue())
        ];
    }

    private function formatDate($date){
        $date = $date instanceof \DateTimeInterface ? $date : new \DateTime($date);
        return $this->_localeDate->formatDateTime(
            $date,
            3,
            -1,
            null,
            null
        );
    }
}
