<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\web\twig\variables;

use Craft;
use CraftCms\Cms\Cp\Rebrand as RebrandService;
use CraftCms\Cms\Edition;
use CraftCms\Cms\Edition\Exceptions\WrongEditionException;
use yii\base\Exception;

/**
 * Rebranding functions.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class Rebrand
{
    /**
     * @var string[]|false[]
     */
    private array $_paths = [];

    /**
     * @var Image[]|false[]
     */
    private array $_imageVariables = [];

    /**
     * @throws WrongEditionException
     */
    public function __construct()
    {
        Edition::require(Edition::Pro);
    }

    /**
     * Returns whether a custom logo has been uploaded.
     *
     * @return bool
     */
    public function isLogoUploaded(): bool
    {
        return app(RebrandService::class)->getImage('logo') !== null;
    }

    /**
     * Returns whether a custom site icon has been uploaded.
     *
     * @return bool
     */
    public function isIconUploaded(): bool
    {
        return app(RebrandService::class)->getImage('icon') !== null;
    }

    /**
     * Return whether the specified type of image has been uploaded for the site.
     *
     * @param string $type 'logo' or 'icon'.
     * @return bool
     */
    public function isImageUploaded(string $type): bool
    {
        return in_array($type, ['logo', 'icon'], true) && ($this->_getImagePath($type) !== false);
    }

    /**
     * Returns the logo variable, or null if a logo hasn't been uploaded.
     *
     * @return Image|null
     */
    public function getLogo(): ?Image
    {
        return $this->getImageVariable('logo');
    }

    /**
     * Returns the icon variable, or null if a site icon hasn't been uploaded.
     *
     * @return Image|null
     */
    public function getIcon(): ?Image
    {
        return $this->getImageVariable('icon');
    }

    /**
     * Get the ImageVariable for type.
     *
     * @param string $type
     * @return Image|null
     */
    public function getImageVariable(string $type): ?Image
    {
        if (!in_array($type, ['logo', 'icon'], true)) {
            return null;
        }

        if (!isset($this->_imageVariables[$type])) {
            $path = $this->_getImagePath($type);

            if ($path !== false) {
                $url = Craft::$app->getAssetManager()->getPublishedUrl($path, true);
                $this->_imageVariables[$type] = new Image($path, $url);
            } else {
                $this->_imageVariables[$type] = false;
            }
        }

        return $this->_imageVariables[$type] ?: null;
    }

    /**
     * Returns the path to a rebrand image by type or false if it hasn't been uploaded.
     *
     * @param string $type logo or image.
     * @return string|false
     * @throws Exception in case of failure
     */
    private function _getImagePath(string $type): string|false
    {
        if (isset($this->_paths[$type])) {
            return $this->_paths[$type];
        }

        $image = app(RebrandService::class)->getImage($type);
        if (isset($image['path'])) {
            $this->_paths[$type] = $image['path'];
        } else {
            $this->_paths[$type] = false;
        }

        return $this->_paths[$type];
    }
}
