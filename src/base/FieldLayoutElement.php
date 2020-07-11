<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\base;

use yii\base\Arrayable;
use yii\base\ArrayableTrait;
use yii\base\BaseObject;

/**
 * FieldLayoutElementInterface defines the common interface to be implemented by field layout element  classes.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.5.0
 */
abstract class FieldLayoutElement extends BaseObject implements FieldLayoutElementInterface
{
    use ArrayableTrait;

    /**
     * @inheritdoc
     */
    public function settingsHtml()
    {
        return null;
    }
}
