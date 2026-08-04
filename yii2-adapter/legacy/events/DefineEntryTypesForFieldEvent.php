<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\events;

use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Entry\Elements\Entry;

/**
 * Class DefineEntryTypesForFieldEvent
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 5.0.0
 * @deprecated 6.0.0 use {@see \CraftCms\Cms\Field\Events\EntryTypesForFieldResolving} instead.
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
