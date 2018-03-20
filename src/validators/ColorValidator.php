<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\validators;

use Craft;
use yii\validators\RegularExpressionValidator;

/**
 * Color hex validator
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class ColorValidator extends RegularExpressionValidator
{
    // Properties
    // =========================================================================

    /**
     * @inheritdoc
     */
    public $pattern = '/^#[0-9a-f]{6}$/';

    /**
     * @inheritdoc
     */
    public function init()
    {
        if ($this->message === null) {
            $this->message = Craft::t('app', '{attribute} isn’t a valid hex color value.');
        }
        parent::init();
    }
}
