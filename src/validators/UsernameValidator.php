<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\validators;

use yii\validators\Validator;

/**
 * Class UsernameValidator.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class UsernameValidator extends Validator
{
    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function validateValue($value)
    {
        // Don't allow whitespace in the username
        if (preg_match('/\s+/', $value)) {
            return ['{attribute} cannot contain spaces.', []];
        }

        return null;
    }
}
