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
 * Class UsernameValidator.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class UsernameValidator extends Validator
{
    /**
     * @inheritdoc
     */
    public function validateValue($value): ?array
    {
        // Don't allow whitespace in the username
        if ($value !== null && preg_match('/\s+/', $value)) {
            return [t('{attribute} cannot contain spaces.'), []];
        }

        return null;
    }
}
