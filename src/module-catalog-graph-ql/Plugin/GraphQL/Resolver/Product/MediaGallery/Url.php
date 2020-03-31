<?php
/**
 * @author    Tigren Solutions <info@tigren.com>
 * @copyright Copyright (c) 2019 Tigren Solutions <https://www.tigren.com>. All rights reserved.
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Tigren\CatalogGraphQl\Plugin\GraphQL\Resolver\Product\MediaGallery;

use Closure;
use Exception;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\ImageFactory;
use Magento\CatalogGraphQl\Model\Resolver\Products\DataProvider\Image\Placeholder as PlaceholderProvider;
use Magento\Framework\App\Area;
use Magento\Framework\Exception\LocalizedException;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Store\Model\App\Emulation;
use Tigren\Core\Model\Config;

/**
 * Class Url
 * @package Tigren\CatalogGraphQl\Plugin\GraphQL\Resolver\Product\MediaGallery
 */
class Url
{
    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * Emulation
     */
    protected $emulation;

    /**
     * @var ImageFactory
     */
    private $productImageFactory;

    /**
     * @var PlaceholderProvider
     */
    private $placeholderProvider;

    /**
     * @var Config
     */
    private $config;

    /**
     * CategoryTree constructor.
     * @param StoreManagerInterface $storeManager
     * @param Emulation $emulation
     * @param ImageFactory $productImageFactory
     * @param PlaceholderProvider $placeholderProvider
     * @param Config $helper
     */
    public function __construct(
        StoreManagerInterface $storeManager,
        Emulation $emulation,
        ImageFactory $productImageFactory,
        PlaceholderProvider $placeholderProvider,
        Config $config
    ) {
        $this->storeManager = $storeManager;
        $this->emulation = $emulation;
        $this->productImageFactory = $productImageFactory;
        $this->placeholderProvider = $placeholderProvider;
        $this->config = $config;
    }

    /**
     * @param \Magento\CatalogGraphQl\Model\Resolver\Product\MediaGallery\Url $subject
     * @param Closure $proceed
     * @param $field
     * @param $context
     * @param $info
     * @param $value
     * @param $args
     * @return array|mixed|string
     * @throws LocalizedException
     */
    public function aroundResolve(
        \Magento\CatalogGraphQl\Model\Resolver\Product\MediaGallery\Url $subject,
        Closure $proceed,
        $field,
        $context,
        $info,
        $value,
        $args
    ) {
        //starting the store emulation with area defined for admin
        $this->emulation->startEnvironmentEmulation($this->storeManager->getDefaultStoreView()->getId(),
            Area::AREA_FRONTEND, true);

        if (!isset($value['image_type']) && !isset($value['file'])) {
            throw new LocalizedException(__('"image_type" value should be specified'));
        }

        if (!isset($value['model'])) {
            throw new LocalizedException(__('"model" value should be specified'));
        }

        /** @var Product $product */
        $product = $value['model'];
        if (isset($value['image_type'])) {
            $imagePath = $product->getData($value['image_type']);
            if ($this->config->isFullPathImageProduct()) {
                return $this->getImageUrl($value['image_type'], $imagePath);
            }
            return $imagePath;
        }

        if (isset($value['file'])) {
            $image = $this->productImageFactory->create();
            $image->setDestinationSubdir('image')->setBaseFile($value['file']);
            $imageUrl = $image->getUrl();
            return $imageUrl;
        }

        return [];
    }

    /**
     * Get image URL
     *
     * @param string $imageType
     * @param string|null $imagePath
     * @return string
     * @throws Exception
     */
    private function getImageUrl(string $imageType, ?string $imagePath): string
    {
        $height = null;
        $width = null;

        if ($imageType == 'small_image') {
            $height = 420;
            $width = 560;
        } elseif ($imageType == 'thumbnail') {
            $height = 100;
            $width = 100;
        } elseif ($imageType == 'image') {
            $height = 1500;
            $width = 2000;
        }

        $image = $this->productImageFactory->create();
        if ($height && $width) {
            $image->setHeight($height)->setWidth($width);
        }

        $image->setDestinationSubdir($imageType)
            ->setBaseFile($imagePath);

        if ($image->isBaseFilePlaceholder()) {
            return $this->placeholderProvider->getPlaceholder($imageType);
        }

        return $image->getUrl();
    }
}
