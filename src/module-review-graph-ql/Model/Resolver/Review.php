<?php
/**
 * @author    Tigren Solutions <info@tigren.com>
 * @copyright Copyright (c) 2019 Tigren Solutions <https://www.tigren.com>. All rights reserved.
 * @license   Open Software License ("OSL") v. 3.0
 */
declare(strict_types=1);

namespace Tigren\ReviewGraphQl\Model\Resolver;

use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\Exception\LocalizedException;
use Magento\Review\Model\ResourceModel\Review\CollectionFactory;
use Magento\Review\Model\Review\SummaryFactory;
use Tigren\Core\Helper\Data;

/**
 * @inheritdoc
 */
class Review implements ResolverInterface
{
    /**
     * @var CollectionFactory
     */
    protected $_reviewsColFactory;

    /**
     * @var Data
     */
    protected $_helper;

    /**
     * @var SummaryFactory
     */
    protected $_summaryModFactory;

    /**
     * Review constructor.
     * @param CollectionFactory $reviewsColFactory
     * @param Data $helper
     * @param SummaryFactory $summaryModFactory
     */
    public function __construct(
        CollectionFactory $reviewsColFactory,
        Data $helper,
        SummaryFactory $summaryModFactory
    ) {
        $this->_reviewsColFactory = $reviewsColFactory;
        $this->_helper = $helper;
        $this->_summaryModFactory = $summaryModFactory;
    }

    /**
     * @inheritdoc
     */
    public function resolve(Field $field, $context, ResolveInfo $info, array $value = null, array $args = null)
    {
        if (!isset($value['model'])) {
            throw new LocalizedException(__('"model" value should be specified'));
        }
        $product = $value['model'];
        $result = [
            'rating_summary' => 0,
            'reviews_count' => 0,
            'items' => []
        ];

        $storeId = $this->_helper->getStoreId();

        $reviewsCollection = $this->_reviewsColFactory->create()->addStoreFilter(
            $storeId
        )->addStatusFilter(
            \Magento\Review\Model\Review::STATUS_APPROVED
        )->addEntityFilter(
            'product',
            $product->getId()
        )->setDateOrder();

        $summaryData = $this->_summaryModFactory->create()->setStoreId($storeId)->load($product->getId());
        $result['rating_summary'] = $summaryData->getRatingSummary();
        $result['reviews_count'] = $summaryData->getReviewsCount();

        $reviewsCollection->addRateVotes();
        foreach ($reviewsCollection->getItems() as $_review) {
            $reviewData = [
                'title' => $_review->getTitle(),
                'nickname' => $_review->getNickname(),
                'detail' => $_review->getDetail(),
                'created_at' => $_review->getCreatedAt(),
                'ratings' => [],
            ];

            $sum = 0;
            $reviewData['vote'] = 0;
            if ($count = count($_review->getRatingVotes())) {
                foreach ($_review->getRatingVotes() as $_vote) {
                    $reviewData['ratings'][] = [
                        'code' => $_vote->getRatingCode(),
                        'value' => $_vote->getPercent(),
                        'vote' => $_vote->getPercent() / 20
                    ];
                    $sum += $_vote->getPercent() / 20;
                }
                $reviewData['vote'] = $sum / $count;
            }
            $result['items'][] = $reviewData;
        }
        return $result;
    }
}
