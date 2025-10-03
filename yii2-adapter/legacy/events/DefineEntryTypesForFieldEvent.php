<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\events;

use craft\base\ElementInterface;
use craft\elements\Entry;

/**
 * Class DefineEntryTypesForFieldEvent
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 5.0.0
 */
class DefineEntryTypesForFieldEvent extends DefineEntryTypesEvent
{
    /**
     * @var ElementInterface|null The element that the field is generating an input for.
     */
    public ?ElementInterface $element = null;

    /**
     * @var Entry[] The current value of the field.
     */
    public array $value;
}
