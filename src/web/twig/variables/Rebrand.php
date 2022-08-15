<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\web\twig\variables;

use Craft;
use craft\errors\WrongEditionException;
use craft\helpers\Image as ImageHelper;
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
        Craft::$app->requireEdition(Craft::Pro);
    }

    /**
     * Returns whether a custom logo has been uploaded.
     *
     * @return bool
     */
    public function isLogoUploaded(): bool
    {
        return $this->isImageUploaded('logo');
    }

    /**
     * Returns whether a custom site icon has been uploaded.
     *
     * @return bool
     */
    public function isIconUploaded(): bool
    {
        return $this->isImageUploaded('icon');
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
     * Returns the logo'sw Image variable, or null if a logo hasn't been uploaded.
     *
     * @return Image|null
     */
    public function getLogo(): ?Image
    {
        return $this->getImageVariable('logo');
    }

    /**
     * Returns the icons variable, or null if a site icon hasn't been uploaded.
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
     * Returns the path to a rebrand image by type or false if it hasn't ben uploaded.
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

        $dir = Craft::$app->getPath()->getRebrandPath() . DIRECTORY_SEPARATOR . $type;

        if (!is_dir($dir)) {
            $this->_paths[$type] = false;
            return false;
        }

        $handle = opendir($dir);
        if ($handle === false) {
            throw new Exception("Unable to open directory: $dir");
        }
        while (($subDir = readdir($handle)) !== false) {
            if ($subDir === '.' || $subDir === '..') {
                continue;
            }
            $path = $dir . DIRECTORY_SEPARATOR . $subDir;
            if (is_dir($path) || !ImageHelper::canManipulateAsImage(pathinfo($path, PATHINFO_EXTENSION))) {
                continue;
            }

            // Found a file - cache and return.
            $this->_paths[$type] = $path;

            return $path;
        }
        closedir($handle);

        // Couldn't find a file
        $this->_paths[$type] = false;

        return false;
    }
}
