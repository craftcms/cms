<?php

declare(strict_types=1);

/**
 * @link https://craftcms.com/
 *
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\elements;

use Craft;
use CraftCms\Cms\Asset\AssetTransforms;
use CraftCms\Cms\Asset\Data\AssetTransformResult;
use CraftCms\Cms\Asset\Events\TransformGenerating;
use CraftCms\Cms\Asset\Exceptions\AssetTransformException;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Component\Exceptions\UnknownPropertyException;
use CraftCms\Cms\Element\Queries\AssetQuery;
use CraftCms\Cms\Image\Data\ImageTransform;
use CraftCms\Cms\Image\ImageTransformHelper;
use CraftCms\Cms\Image\ImageTransforms;
use CraftCms\Cms\Shared\Exceptions\NotSupportedException;
use CraftCms\Cms\Support\Html;
use CraftCms\Cms\Twig\Attributes\AllowedInSandbox;
use CraftCms\Yii2Adapter\Asset\LegacyImageTransformerDriver;
use Override;
use Twig\Markup;

/**
 * @since 3.0.0
 * @deprecated 6.0.0 use {@see \CraftCms\Cms\Asset\Elements\Asset} instead.
 */
class Asset extends \CraftCms\Cms\Asset\Elements\Asset
{
    private ?ImageTransform $_transform = null;

    private ?bool $_immediately = null;

    #[Override]
    public static function find(): AssetQuery
    {
        return new AssetQuery(elementClass: self::class);
    }

    #[Override]
    public function __toString(): string
    {
        if ($this->_transform !== null && $url = $this->getUrl()) {
            return $url;
        }

        return parent::__toString();
    }

    #[Override]
    public function __isset($name): bool
    {
        if (parent::__isset($name) || str_starts_with($name, 'transform:')) {
            return true;
        }

        return (bool) app(ImageTransforms::class)->getTransformByHandle($name);
    }

    #[Override]
    public function __get(string $name): mixed
    {
        if (str_starts_with($name, 'transform:')) {
            return $this->copyWithTransform(substr($name, 10));
        }

        try {
            return parent::__get($name);
        } catch (UnknownPropertyException $exception) {
            $transform = app(ImageTransforms::class)->getTransformByHandle($name);

            if ($transform !== null) {
                return $this->copyWithTransform($transform);
            }

            throw $exception;
        }
    }

    public function setTransform(mixed $transform): self
    {
        if ($this->allowTransforms()) {
            $this->_transform = ImageTransformHelper::normalizeTransform($transform);
        }

        return $this;
    }

    public function copyWithTransform(mixed $transform): self
    {
        $asset = clone $this;
        $asset->setFieldValues($this->getFieldValues());
        $asset->setTransform($transform);

        return $asset;
    }

    #[Override]
    #[AllowedInSandbox]
    public function getImg(mixed $transform = null, ?array $sizes = null): ?Markup
    {
        return parent::getImg($transform ?? $this->_transform, $sizes);
    }

    #[Override]
    #[AllowedInSandbox]
    public function getUrlsBySize(array $sizes, mixed $transform = null): array
    {
        return parent::getUrlsBySize($sizes, $transform ?? $this->_transform);
    }

    #[Override]
    #[AllowedInSandbox]
    public function transform(#[\SensitiveParameter] mixed $definition): AssetTransformResult
    {
        return app(AssetTransforms::class)->transform($this, $definition);
    }

    #[Override]
    public function getUrl(mixed $transform = null, ?bool $immediately = null): ?string
    {
        $previous = $this->_immediately;
        $this->_immediately = $immediately;

        try {
            return parent::getUrl($transform ?? $this->_transform, $immediately);
        } finally {
            $this->_immediately = $previous;
        }
    }

    #[Override]
    protected function _tryTransform(#[\SensitiveParameter] mixed $definition): ?AssetTransformResult
    {
        $immediately = $this->_immediately ?? Craft::$app->getConfig()->getGeneral()->generateTransformsBeforePageLoad;
        $settings = $immediately === null ? [] : ['generateBeforePageLoad' => $immediately];
        $candidateDriver = null;

        try {
            if (!is_array($definition) || !array_key_exists('driver', $definition)) {
                $transform = ImageTransformHelper::normalizeTransform($definition);

                if ($transform?->getTransformer() !== ImageTransform::DEFAULT_TRANSFORMER) {
                    $definition = $transform;
                    $candidateDriver = $transform->getTransformer();
                    $filesystemTransform = $this->getVolume()->getFs()->getAssetTransform();
                    $legacySelected = $transform->driver === null
                        && (!is_array($filesystemTransform) || !array_key_exists('driver', $filesystemTransform));

                    if ($legacySelected) {
                        event($event = new TransformGenerating($this, $transform));

                        if ($event->url !== null) {
                            return LegacyImageTransformerDriver::result($this, $transform, Html::encodeSpaces($event->url));
                        }

                        $settings['legacyBeforeGenerate'] = true;
                    }
                }
            }

            return app(AssetTransforms::class)->transform($this, $definition, $settings, $candidateDriver);
        } catch (AssetTransformException|NotSupportedException $exception) {
            report($exception);

            return null;
        }
    }

    #[Override]
    #[AllowedInSandbox]
    public function getMimeType(mixed $transform = null): ?string
    {
        return parent::getMimeType($transform ?? $this->_transform);
    }

    #[Override]
    #[AllowedInSandbox]
    public function getFormat(mixed $transform = null): string
    {
        return parent::getFormat($transform ?? $this->_transform);
    }

    #[Override]
    #[AllowedInSandbox]
    public function getHeight(mixed $transform = null): ?int
    {
        return parent::getHeight($transform ?? $this->_transform);
    }

    #[Override]
    #[AllowedInSandbox]
    public function getWidth(array|string|ImageTransform|null $transform = null): ?int
    {
        return parent::getWidth($transform ?? $this->_transform);
    }

    private function allowTransforms(): bool
    {
        return match ($this->getMimeType()) {
            'image/gif' => Cms::config()->transformGifs,
            'image/svg+xml' => Cms::config()->transformSvgs,
            default => true,
        };
    }
}
