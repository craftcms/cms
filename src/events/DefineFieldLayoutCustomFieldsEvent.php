<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\events;

use craft\fieldlayoutelements\BaseField;
use yii\base\Event;

/**
 * DefineFieldLayoutCustomFieldsEvent event class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.2.0
 */
class DefineFieldLayoutCustomFieldsEvent extends Event
{
    /**
     * @var BaseField[][] The custom fields that should be available to the field layout designer.
     */
    public array $fields;
}
