<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\validators;

use Craft;
use yii\validators\Validator;

/**
 * Class HandleValidator.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class HandleValidator extends Validator
{
    /**
     * @var string
     */
    public static $handlePattern = '[a-zA-Z][a-zA-Z0-9_]*';

    /**
     * @var array
     */
    public static $baseReservedWords = [
        'attribute',
        'attributeLabels',
        'attributeNames',
        'attributes',
        'classHandle',
        'content',
        'dateCreated',
        'dateUpdated',
        'errors',
        'false',
        'fields',
        'handle',
        'id',
        'n',
        'name',
        'no',
        'rawContent',
        'rules',
        'searchKeywords',
        'section',
        'this',
        'true',
        'type',
        'uid',
        'value',
        'y',
        'yes',
    ];

    /**
     * @var array
     */
    public $reservedWords = [];

    /**
     * @inheritdoc
     */
    public function validateAttribute($model, $attribute)
    {
        $handle = $model->$attribute;

        // Handles are always required, so if it's blank, the required validator will catch this.
        if ($handle) {
            $reservedWords = array_merge($this->reservedWords, static::$baseReservedWords);
            $reservedWords = array_map('strtolower', $reservedWords);
            $lcHandle = strtolower($handle);

            if (in_array($lcHandle, $reservedWords, true)) {
                $message = Craft::t('app', '“{handle}” is a reserved word.',
                    ['handle' => $handle]);
                $this->addError($model, $attribute, $message);
            } else {
                if (!preg_match('/^' . static::$handlePattern . '$/', $handle)) {
                    $altMessage = Craft::t('app', '“{handle}” isn’t a valid handle.', ['handle' => $handle]);
                    $message = $this->message ?? $altMessage;
                    $this->addError($model, $attribute, $message);
                }
            }
        }
    }
}
