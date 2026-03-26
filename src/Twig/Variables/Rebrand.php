<?php

declare(strict_types=1);

namespace CraftCms\Cms\Twig\Variables;

use Craft;
use CraftCms\Cms\Cp\Rebrand as RebrandService;
use CraftCms\Cms\Edition;

class Rebrand
{
    /**
     * @var string[]|false[]
     */
    private array $paths = [];

    /**
     * @var Image[]|false[]
     */
    private array $imageVariables = [];

    public function __construct(
        private readonly RebrandService $rebrandService,
    ) {
        Edition::require(Edition::Pro);
    }

    public function isLogoUploaded(): bool
    {
        return $this->rebrandService->getImage('logo') !== null;
    }

    public function isIconUploaded(): bool
    {
        return $this->rebrandService->getImage('icon') !== null;
    }

    public function isImageUploaded(string $type): bool
    {
        return in_array($type, ['logo', 'icon'], true) && ($this->getImagePath($type) !== false);
    }

    public function getLogo(): ?Image
    {
        return $this->getImageVariable('logo');
    }

    public function getIcon(): ?Image
    {
        return $this->getImageVariable('icon');
    }

    public function getImageVariable(string $type): ?Image
    {
        if (! in_array($type, ['logo', 'icon'], true)) {
            return null;
        }

        if (! isset($this->imageVariables[$type])) {
            $path = $this->getImagePath($type);

            if ($path !== false) {
                $url = Craft::$app->getAssetManager()->getPublishedUrl($path, true);
                $this->imageVariables[$type] = new Image($path, $url);
            } else {
                $this->imageVariables[$type] = false;
            }
        }

        return $this->imageVariables[$type] ?: null;
    }

    private function getImagePath(string $type): string|false
    {
        if (isset($this->paths[$type])) {
            return $this->paths[$type];
        }

        $image = $this->rebrandService->getImage($type);

        if (isset($image['path'])) {
            $this->paths[$type] = $image['path'];
        } else {
            $this->paths[$type] = false;
        }

        return $this->paths[$type];
    }
}
