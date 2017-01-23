<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\validators;

use Craft;
use craft\elements\Asset;
use craft\helpers\Assets as AssetsHelper;
use craft\helpers\Db;
use yii\validators\Validator;

/**
 * Class AssetFilenameValidator.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class AssetFilenameValidator extends Validator
{
    // Properties
    // =========================================================================

    /**
     * @var string[]|null Allowed file extensions
     */
    public $allowedExtensions;

    /**
     * @var string|null User-defined error message used when the extension is disallowed.
     */
    public $badExtension;

    /**
     * @var string|null User-defined error message used when a file already exists with the same name.
     */
    public $alreadyExists;

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();

        if ($this->allowedExtensions === null) {
            $this->allowedExtensions = Craft::$app->getConfig()->getAllowedFileExtensions();
        }

        if ($this->badExtension === null) {
            $this->badExtension = Craft::t('app', '“{extension}” is not an allowed file extension.');
        }

        if ($this->alreadyExists === null) {
            $this->alreadyExists = Craft::t('app', 'A file with the name “{value}” already exists.');
        }
    }

    /**
     * @inheritdoc
     */
    public function validateAttribute($model, $attribute)
    {
        /** @var Asset $model */
        $value = $model->$attribute;

        // Make sure the new filename has a valid extension
        $extension = strtolower(pathinfo($value, PATHINFO_EXTENSION));
        if (!in_array($extension, $this->allowedExtensions, true)) {
            $this->addError($model, $attribute, $this->badExtension, ['extension' => $extension]);
        }

        // Prepare the filename
        $value = $model->$attribute = AssetsHelper::prepareAssetName($value);

        // Ensure a file doesn't already exist in the target folder
        if ($model->folderId) {
            $existingAssetId = Asset::find()
                ->select(['elements.id'])
                ->folderId($model->folderId)
                ->filename(Db::escapeParam($value))
                ->scalar();

            if ($existingAssetId && $existingAssetId != $model->id) {
                $this->addError($model, $attribute, $this->alreadyExists);
            }
        }
    }
}
