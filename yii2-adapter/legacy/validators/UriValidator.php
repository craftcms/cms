<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\validators;

use yii\validators\Validator;
use function CraftCms\Cms\t;

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
        if ($value === null || !preg_match($this->pattern, $value)) {
            return [t('{attribute} is not a valid URI'), []];
        }

        return null;
    }
}
