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
 * Will validate that the given attribute is a valid URI.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class UriValidator extends Validator
{
    /**
     * @var string
     */
    public string $pattern = '/^\S+$/u';

    /**
     * @inheritdoc
     */
    protected function validateValue($value): ?array
    {
        if (!preg_match($this->pattern, $value)) {
            return [Craft::t('app', '{attribute} is not a valid URI'), []];
        }

        return null;
    }
}
