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
use craft\base\imagetransforms\ImageTransformerInterface;
use craft\models\ImageTransform as LegacyImageTransform;
use CraftCms\Cms\Asset\AssetTransformers;
use CraftCms\Cms\Asset\Data\AssetTransformResult;
use CraftCms\Cms\Asset\Exceptions\AssetTransformException;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Component\Exceptions\UnknownPropertyException;
use CraftCms\Cms\Element\Queries\AssetQuery;
use CraftCms\Cms\Image\Data\ImageTransform;
use CraftCms\Cms\Image\ImageTransformHelper;
use CraftCms\Cms\Image\ImageTransforms;
use CraftCms\Cms\Shared\Exceptions\NotSupportedException;
use CraftCms\Cms\Twig\Attributes\AllowedInSandbox;
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
    public function transform(#[\SensitiveParameter] mixed $definition, ?bool $immediately = null): AssetTransformResult
    {
        return app(AssetTransformers::class)->transform($this, $definition, $immediately);
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
    protected function _tryTransform(#[\SensitiveParameter] mixed $definition, ?bool $immediately = null): ?AssetTransformResult
    {
        $immediately ??= $this->_immediately ?? Craft::$app->getConfig()->getGeneral()->generateTransformsBeforePageLoad;
        $candidateTransformer = null;

        try {
            if (is_array($definition) && array_key_exists('transformer', $definition)) {
                $candidate = $definition['transformer'];

                if (is_string($candidate) && is_subclass_of($candidate, ImageTransformerInterface::class)) {
                    $candidateTransformer = $candidate === LegacyImageTransform::DEFAULT_TRANSFORMER
                        ? null
                        : Craft::$app->getImageTransforms()->getAssetTransformerHandle($candidate);
                    unset($definition['transformer']);
                }
            } elseif ($definition instanceof LegacyImageTransform) {
                $candidate = $definition->getTransformer();
                $candidateTransformer = $candidate === LegacyImageTransform::DEFAULT_TRANSFORMER
                    ? null
                    : Craft::$app->getImageTransforms()->getAssetTransformerHandle($candidate);
            }

            return app(AssetTransformers::class)->transformWithCandidate(
                $this,
                $definition,
                $candidateTransformer,
                $immediately,
            );
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
