<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\validators;

use Craft;
use craft\elements\Asset;
use craft\helpers\Assets;
use craft\helpers\Assets as AssetsHelper;
use yii\base\InvalidConfigException;
use yii\base\Model;
use yii\validators\Validator;

/**
 * Class AssetLocationValidator.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class AssetLocationValidator extends Validator
{
    /**
     * @var string The folder ID attribute on the model
     */
    public string $folderIdAttribute = 'folderId';

    /**
     * @var string The filename attribute on the model
     */
    public string $filenameAttribute = 'filename';

    /**
     * @var string The suggested filename attribute on the model
     */
    public string $suggestedFilenameAttribute = 'suggestedFilename';

    /**
     * @var string The conflicting filename attribute on the model
     */
    public string $conflictingFilenameAttribute = 'conflictingFilename';

    /**
     * @var string The error code attribute on the model
     */
    public string $errorCodeAttribute = 'locationError';

    /**
     * @var string[]|string|null Allowed file extensions. Set to `'*'` to allow all extensions.
     */
    public string|array|null $allowedExtensions = null;

    /**
     * @var string|null User-defined error message used when the extension is disallowed.
     */
    public ?string $disallowedExtension = null;

    /**
     * @var string|null User-defined error message used when a file already exists with the same name.
     */
    public ?string $filenameConflict = null;

    /**
     * @var bool Whether Asset should avoid filename conflicts when saved.
     */
    public bool $avoidFilenameConflicts;

    /**
     * @inheritdoc
     */
    public function init(): void
    {
        parent::init();

        if (!isset($this->allowedExtensions)) {
            $this->allowedExtensions = Craft::$app->getConfig()->getGeneral()->allowedFileExtensions;
        }

        if (!isset($this->disallowedExtension)) {
            $this->disallowedExtension = Craft::t('app', '“{extension}” is not an allowed file extension.');
        }

        if (!isset($this->filenameConflict)) {
            $this->filenameConflict = Craft::t('app', 'A file with the name “{filename}” already exists.');
        }
    }

    /**
     * @inheritdoc
     */
    public function validateAttribute($model, $attribute): void
    {
        /** @var Asset $model */
        [$folderId, $filename] = Assets::parseFileLocation($model->$attribute);

        // Figure out which of them has changed
        $hasNewFolderId = $folderId != $model->{$this->folderIdAttribute};
        $hasNewFilename = $filename != $model->{$this->filenameAttribute};

        // If nothing has changed, just null-out the newLocation attribute
        if (!$hasNewFolderId && !$hasNewFilename) {
            $model->$attribute = null;

            return;
        }

        // Get the folder
        if (Craft::$app->getAssets()->getFolderById($folderId) === null) {
            throw new InvalidConfigException('Invalid folder ID: ' . $folderId);
        }

        // Make sure the new filename has a valid extension
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        if (is_array($this->allowedExtensions) && !in_array($extension, $this->allowedExtensions, true)) {
            $this->addLocationError($model, $attribute, Asset::ERROR_DISALLOWED_EXTENSION, $this->disallowedExtension, ['extension' => $extension]);
            return;
        }

        // Prepare the filename
        $filename = AssetsHelper::prepareAssetName($filename);
        $suggestedFilename = Craft::$app->getAssets()->getNameReplacementInFolder($filename, $folderId);

        if ($suggestedFilename !== $filename) {
            $model->{$this->conflictingFilenameAttribute} = $filename;
            $model->{$this->suggestedFilenameAttribute} = $suggestedFilename;

            if (!$this->avoidFilenameConflicts) {
                $this->addLocationError($model, $attribute, Asset::ERROR_FILENAME_CONFLICT, $this->filenameConflict, ['filename' => $filename]);

                return;
            }
        }

        // Update the newLocation attribute in case the filename changed
        $model->$attribute = "{folder:$folderId}$suggestedFilename";
    }

    /**
     * Adds a location error to the model.
     *
     * @param Model $model
     * @param string $attribute
     * @param string $errorCode
     * @param string $message
     * @param array $params
     */
    public function addLocationError(Model $model, string $attribute, string $errorCode, string $message, array $params = []): void
    {
        $this->addError($model, $attribute, $message, $params);

        if (isset($this->errorCodeAttribute)) {
            $model->{$this->errorCodeAttribute} = $errorCode;
        }
    }
}
