<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\fieldlayoutelements\users;

use craft\elements\User;
use craft\fieldlayoutelements\FullNameField as BaseFullNameField;

/**
 * FullNameField represents a Full Name field that can be included in the user field layout.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 5.0.0
 */
class FullNameField extends BaseFullNameField
{
    /**
     * @inheritdoc
     */
    public bool $mandatory = true;
}
