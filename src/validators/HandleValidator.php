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
    public static string $handlePattern = '[a-zA-Z][a-zA-Z0-9_]*';

    /**
     * @var array
     */
    public static array $baseReservedWords = [
        'attribute',
        'attributeLabels',
        'attributeNames',
        'attributes',
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
        'rules',
        'this',
        'true',
        'uid',
        'y',
        'yes',
    ];

    /**
     * @var array
     */
    public array $reservedWords = [];

    /**
     * @inheritdoc
     */
    protected function validateValue($value): ?array
    {
        $message = null;

        if (!preg_match(sprintf('/^%s$/', static::$handlePattern), $value)) {
            $message = $this->message ?? Craft::t('app', '“{handle}” isn’t a valid handle.');
        } else {
            $reservedWords = array_merge($this->reservedWords, static::$baseReservedWords);
            $reservedWords = array_map('strtolower', $reservedWords);
            if (in_array(strtolower($value), $reservedWords, true)) {
                $message = Craft::t('app', '“{handle}” is a reserved word.');
            }
        }

        return $message ? [$message, ['handle' => $value]] : null;
    }
}
