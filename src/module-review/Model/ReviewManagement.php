<?php
/**
 * @author Tigren Solutions <info@tigren.com>
 * @copyright Copyright (c) 2019 Tigren Solutions <https://www.tigren.com>. All rights reserved.
 * @license Open Software License ("OSL") v. 3.0
 */

namespace Tigren\Review\Model;

use Exception;
use Magento\Authorization\Model\UserContextInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Review\Block\Customer\ListCustomer;
use Magento\Review\Block\Form;
use Magento\Review\Model\RatingFactory;
use Magento\Review\Model\Review as ReviewModel;
use Magento\Review\Model\ReviewFactory;
use Tigren\Core\Helper\Data;
use Tigren\Review\Api\ReviewManagementInterface;

/**
 * Interface ReviewManagement
 * @api
 */
class ReviewManagement implements ReviewManagementInterface
{
    /**
     * @var ProductRepositoryInterface
     */
    protected $productRepository;

    /**
     * Review model
     *
     * @var ReviewFactory
     */
    protected $reviewFactory;

    /**
     * Rating model
     *
     * @var RatingFactory
     */
    protected $ratingFactory;

    /**
     * @var Data
     */
    protected $helper;

    /**
     * @var UserContextInterface
     */
    protected $userContext;

    public function __construct(
        ProductRepositoryInterface $productRepository,
        ManagerInterface $eventManager,
        ReviewFactory $reviewFactory,
        RatingFactory $ratingFactory,
        Data $helper,
        UserContextInterface $userContext
    ) {
        $this->productRepository = $productRepository;
        $this->reviewFactory = $reviewFactory;
        $this->ratingFactory = $ratingFactory;
        $this->helper = $helper;
        $this->userContext = $userContext;
    }

    /**
     * {@inheritdoc}
     */
    public function submit($sku, $data)
    {
        try {
            $product = $this->productRepository->get($sku);
        } catch (NoSuchEntityException $e) {
            throw new NoSuchEntityException(__($e->getMessage()));
        }
        /** @var ReviewModel $review */
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
        } catch (Exception $e) {
            throw new Exception(__($e->getMessage()));
        }
        return $review->getId();
    }

}
