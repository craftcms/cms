<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\events;

use craft\elements\db\ElementQueryInterface;
use yii\base\Event;

/**
 * Table attribute Element Query preparation event
 *
 * Triggered while preparing an element query for an element index, for each attribute present in the table.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.7.14
 */
class ElementIndexTableAttributeEvent extends Event
{
    /**
     * @var ElementQueryInterface The Element query being built
     */
    public ElementQueryInterface $query;

    /**
     * @var string The table attribute name, as registered by the Element, and not implicitly a native field or attribute name.
     */
    public string $attribute;
}
