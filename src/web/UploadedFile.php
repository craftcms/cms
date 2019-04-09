<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\web;

use Craft;
use craft\helpers\FileHelper;
use yii\base\InvalidConfigException;

/**
 * UploadedFile represents the information for an uploaded file.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class UploadedFile extends \yii\web\UploadedFile
{
    // Public Methods
    // =========================================================================

    /**
     * Returns an instance of the specified uploaded file. The name can be a plain string or a string like an array
     * element (e.g. 'Post[imageFile]', or 'Post[0][imageFile]').
     *
     * @param string $name The name of the file input field
     * @param bool $ensureTempFileExists Whether to only return the instance if its temp files still exists
     * @return static|null The instance of the uploaded file. null is returned if no file is uploaded for the
     * specified name.
     */
    public static function getInstanceByName($name, bool $ensureTempFileExists = true)
    {
        /** @var static $instance */
        $instance = parent::getInstanceByName(self::_normalizeName($name));
        if ($instance === null) {
            return null;
        }
        if ($ensureTempFileExists && !is_uploaded_file($instance->tempName)) {
            return null;
        }
        return $instance;
    }

    /**
     * Returns an array of instances starting with specified array name.
     *
     * If multiple files were uploaded and saved as 'Files[0]', 'Files[1]', 'Files[n]'..., you can have them all by
     * passing 'Files' as array name.
     *
     * @param string $name The name of the array of files
     * @param bool $lookForSingleInstance If set to true, will look for a single instance of the given name.
     * @param bool $ensureTempFilesExist Whether only instances whose temp files still exist should be returned.
     * @return UploadedFile[] The array of UploadedFile objects. Empty array is returned if no adequate upload was
     * found. Please note that this array will contain all files from all subarrays regardless
     * how deeply nested they are.
     */
    public static function getInstancesByName($name, $lookForSingleInstance = true, $ensureTempFilesExist = true): array
    {
        $name = self::_normalizeName($name);
        /** @var static[] $instances */
        $instances = parent::getInstancesByName($name);

        if (empty($instances) && $lookForSingleInstance) {
            $singleInstance = static::getInstanceByName($name);

            if ($singleInstance) {
                $instances[] = $singleInstance;
            }
        }

        if ($ensureTempFilesExist) {
            array_filter($instances, function(UploadedFile $instance): bool {
                return is_uploaded_file($instance->tempName);
            });

            // Reset the keys
            $instances = array_values($instances);
        }

        return $instances;
    }

    /**
     * Saves the uploaded file to a temp location.
     *
     * @param bool $deleteTempFile whether to delete the temporary file after saving.
     * If true, you will not be able to save the uploaded file again in the current request.
     * @return string|false the path to the temp file, or false if the file wasn't saved successfully
     * @see error
     */
    public function saveAsTempFile(bool $deleteTempFile = true)
    {
        if ($this->error != UPLOAD_ERR_OK) {
            return false;
        }

        $tempFilename = uniqid(pathinfo($this->name, PATHINFO_FILENAME), true) . '.' . pathinfo($this->name, PATHINFO_EXTENSION);
        $tempPath = Craft::$app->getPath()->getTempPath() . DIRECTORY_SEPARATOR . $tempFilename;

        if (!$this->saveAs($tempPath, $deleteTempFile)) {
            return false;
        }

        return $tempPath;
    }

    /**
     * Returns the MIME type of the file, based on [[\craft\helpers\FileHelper::getMimeType()]] rather than what the
     * request told us.
     *
     * @param string|null $magicFile name of the optional magic database file (or alias).
     * @param bool $checkExtension whether to use the file extension to determine the MIME type in case
     * `finfo_open()` cannot determine it.
     * @return string|null
     * @throws InvalidConfigException when the `fileinfo` PHP extension is not installed and `$checkExtension` is `false`.
     * @since 3.1.7
     */
    public function getMimeType(string $magicFile = null, bool $checkExtension = true)
    {
        $mimeType = null;

        // Make sure it still exists in the temp location
        if (is_uploaded_file($this->tempName)) {
            // Don't check the extension yet (the temp name doesn't have one)
            try {
                $mimeType = FileHelper::getMimeType($this->tempName, $magicFile, false);
            } catch (InvalidConfigException $e) {
                if (!$checkExtension) {
                    throw $e;
                }
            }
        }

        // Be forgiving of SVG files, etc., that don't have an XML declaration
        if ($checkExtension && ($mimeType === null || !FileHelper::canTrustMimeType($mimeType))) {
            return FileHelper::getMimeTypeByExtension($this->name, $magicFile) ?? $mimeType;
        }

        return $mimeType;
    }

    // Private Methods
    // =========================================================================

    /**
     * Swaps dot notation for the normal format.
     *
     * ex: fields.assetsField => fields[assetsField]
     *
     * @param string $name The name to normalize.
     * @return string
     */
    private static function _normalizeName(string $name): string
    {
        if (($pos = strpos($name, '.')) !== false) {
            // Convert dot notation to the normal format ex: fields.assetsField => fields[assetsField]
            $name = substr($name, 0, $pos) . '[' . str_replace('.', '][', substr($name, $pos + 1)) . ']';
        }

        return $name;
    }
}
