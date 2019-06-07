<?php
/**
 * Copyright © 2013-2017 Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Tigren\CatalogGraphQl\Model;

use Magento\Authorization\Model\UserContextInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Review\Model\Review as ReviewModel;

/**
 * Interface IndexManagement
 * @api
 */
class IndexManagement implements \Tigren\CatalogGraphQl\Api\IndexManagementInterface
{
    /**
     * @var \Magento\Review\Block\Customer\ListCustomer
     */
    protected $blockReviewList;

    /**
     * @var \Magento\Catalog\Api\ProductRepositoryInterface
     */
    protected $productRepository;

    /**
     * @var \Magento\Review\Block\Form
     */
    protected $blockFormReview;

    /**
     * @var \Magento\Framework\Event\ManagerInterface
     */
    protected $_eventManager;

    /**
     * Review model
     *
     * @var \Magento\Review\Model\ReviewFactory
     */
    protected $reviewFactory;

    /**
     * Customer session model
     *
     * @var \Magento\Customer\Model\Session
     */
    protected $customerSession;

    /**
     * Rating model
     *
     * @var \Magento\Review\Model\RatingFactory
     */
    protected $ratingFactory;

    /**
     * @var \Tigren\CatalogGraphQl\Helper\Data
     */
    protected $helper;

    /**
     * @var UserContextInterface
     */
    protected $userContext;

    public function __construct(
        \Magento\Review\Block\Customer\ListCustomer $blockReviewList,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
        \Magento\Review\Block\Form $blockFormReview,
        \Magento\Framework\Event\ManagerInterface $eventManager,
        \Magento\Review\Model\ReviewFactory $reviewFactory,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Review\Model\RatingFactory $ratingFactory,
        \Tigren\CatalogGraphQl\Helper\Data $helper,
        UserContextInterface $userContext
    ) {
        $this->blockReviewList = $blockReviewList;
        $this->productRepository = $productRepository;
        $this->blockFormReview = $blockFormReview;
        $this->_eventManager = $eventManager;
        $this->reviewFactory = $reviewFactory;
        $this->customerSession = $customerSession;
        $this->ratingFactory = $ratingFactory;
        $this->helper = $helper;
        $this->userContext = $userContext;
    }
    /**
     * {@inheritdoc}
     */
    public function submitReview($sku, $data)
    {
        try {
            $product = $this->productRepository->get($sku);
        } catch (NoSuchEntityException $e) {
            throw new NoSuchEntityException(__($e->getMessage()));
        }
        /** @var \Magento\Review\Model\Review $review */
        $review = $this->reviewFactory->create()->setData($data);
        $review->unsetData('review_id');
        $customerId = $this->userContext->getUserId() ?: null;
        try {
            $review->setEntityId($review->getEntityIdByCode(ReviewModel::ENTITY_PRODUCT_CODE))
                ->setEntityPkValue($product->getId())
                ->setStatusId(ReviewModel::STATUS_PENDING)
                ->setCustomerId($customerId)
                ->setStoreId($this->helper->getStoreId())
                ->setStores([$this->helper->getStoreId()])
                ->save();
            $ratings = $data['ratings'] ?? [];
            foreach ($ratings as $key => $optionId) {
                if ($optionId) {
                    $this->ratingFactory->create()
                        ->setRatingId($key)
                        ->setReviewId($review->getId())
                        ->setCustomerId($customerId)
                        ->addOptionVote($optionId, $product->getId());
                }
            }
            $review->aggregate();
        } catch (\Exception $e) {
            throw new \Exception(__($e->getMessage()));
        }
        return $review->getId();
    }

}
