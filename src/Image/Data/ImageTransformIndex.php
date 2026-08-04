<?php

declare(strict_types=1);

namespace CraftCms\Cms\Image\Data;

use CraftCms\Cms\Component\Component;
use CraftCms\Cms\Image\ImageTransformHelper;
use DateTimeInterface;

class ImageTransformIndex extends Component
{
    public ?int $id = null;

    public ?int $assetId = null;

    public ?string $transformer = null;

    public ?string $filename = null;

    public ?string $format = null;

    public ?string $transformString = null;

    public bool $fileExists = false;

    public bool $inProgress = false;

    public bool $error = false;

    public ?DateTimeInterface $dateIndexed = null;

    public ?DateTimeInterface $dateUpdated = null;

    public ?DateTimeInterface $dateCreated = null;

    public ?string $detectedFormat = null;

    private ?ImageTransform $_transform = null;

    public ImageTransform $transform {
        get => $this->getTransform();
        set {
            $this->setTransform($value);
        }
    }

    public function __construct(array|object $config = [])
    {
        parent::__construct($config);

        // Reset inProgress if stale (30+ seconds)
        if (
            $this->inProgress
            && $this->dateUpdated
            && now()->diffInSeconds($this->dateUpdated, true) > 30
        ) {
            $this->inProgress = false;
        }
    }

    public function getTransform(): ImageTransform
    {
        return $this->_transform ??= ImageTransformHelper::normalizeTransform($this->transformString);
    }

    public function setTransform(ImageTransform $transform): void
    {
        $this->_transform = $transform;
    }
}
