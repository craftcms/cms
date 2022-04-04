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
 * DefineFieldLayoutFieldsEvent event class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.5.0
 */
class DefineFieldLayoutFieldsEvent extends Event
{
    /**
     * @var array The fields that should be available to the field layout designer.
     * @phpstan-var array<BaseField|class-string<BaseField>|array{class:class-string<BaseField>}>
     */
    public array $fields = [];
}
