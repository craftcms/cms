<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.github.io/license/
 */

namespace craft\validators;

use Craft;
use craft\helpers\StringHelper;
use yii\validators\Validator;

/**
 * Class HandleValidator.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class HandleValidator extends Validator
{
    // Static
    // =========================================================================

    /**
     * @var array
     */
    public static $baseReservedWords = [
        'id',
        'dateCreated',
        'dateUpdated',
        'uid',
        'this',
        'true',
        'false',
        'y',
        'n',
        'yes',
        'no',
        'classHandle',
        'handle',
        'name',
        'attributeNames',
        'attributes',
        'attribute',
        'rules',
        'attributeLabels',
        'fields',
        'content',
        'rawContent',
        'section',
        'type',
        'value',
    ];

    // Properties
    // =========================================================================

    /**
     * @var string
     */
    public static $handlePattern = '[a-zA-Z][a-zA-Z0-9_]*';

    /**
     * @var array
     */
    public $reservedWords = [];

    // Protected Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function validateAttribute($model, $attribute)
    {
        $handle = $model->$attribute;

        // Handles are always required, so if it's blank, the required validator will catch this.
        if ($handle) {
            $reservedWords = array_merge($this->reservedWords, static::$baseReservedWords);
            $reservedWords = array_map([StringHelper::class, 'toLowerCase'], $reservedWords);
            $lcHandle = StringHelper::toLowerCase($handle);

            if (in_array($lcHandle, $reservedWords, true)) {
                $message = Craft::t('app', '“{handle}” is a reserved word.',
                    ['handle' => $handle]);
                $this->addError($model, $attribute, $message);
            } else {
                if (!preg_match('/^'.static::$handlePattern.'$/', $handle)) {
                    $altMessage = Craft::t('app', '“{handle}” isn’t a valid handle.', ['handle' => $handle]);
                    $message = $this->message ?? $altMessage;
                    $this->addError($model, $attribute, $message);
                }
            }
        }
    }
}
