<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\validators;

use CraftCms\Cms\Asset\Elements\Asset;
use CraftCms\Cms\Asset\Validation\Rules\AssetLocationRule;
use CraftCms\Cms\Cms;
use yii\base\Model;
use yii\validators\Validator;
use function CraftCms\Cms\t;

/**
 * Class AssetLocationValidator.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 * @deprecated 6.0.0 use {@see \CraftCms\Cms\Asset\Validation\AssetLocationRule} instead.
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
     * @var bool Whether the asset should avoid filename conflicts when saved.
     */
    public bool $avoidFilenameConflicts;

    /**
     * @inheritdoc
     */
    public function init(): void
    {
        parent::init();

        if (!isset($this->allowedExtensions)) {
            $this->allowedExtensions = Cms::config()->allowedFileExtensions;
        }

        if (!isset($this->disallowedExtension)) {
            $this->disallowedExtension = t('“{extension}” is not an allowed file extension.');
        }

        if (!isset($this->filenameConflict)) {
            $this->filenameConflict = t('A file with the name “{filename}” already exists.');
        }
    }

    /**
     * @inheritdoc
     */
    public function validateAttribute($model, $attribute): void
    {
        /** @var Asset $model */
        $rule = new AssetLocationRule(
            asset: $model,
            folderIdAttribute: $this->folderIdAttribute,
            filenameAttribute: $this->filenameAttribute,
            suggestedFilenameAttribute: $this->suggestedFilenameAttribute,
            conflictingFilenameAttribute: $this->conflictingFilenameAttribute,
            errorCodeAttribute: $this->errorCodeAttribute,
            allowedExtensions: $this->allowedExtensions,
            disallowedExtension: $this->disallowedExtension,
            filenameConflict: $this->filenameConflict,
        );

        $validator = \Illuminate\Support\Facades\Validator::make([
            $attribute => $model->$attribute,
        ], [
            $attribute => $rule,
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->get($attribute) as $messages) {
                foreach ($messages as $message) {
                    $model->addError($attribute, $message[0]);
                }
            }
        }
    }
}
