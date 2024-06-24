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
 * Element Index View attribute Element Query preparation event
 *
 * Triggered while preparing an element query for an element index, for each attribute present in the view (e.g. table, card, structure).
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 5.3.0
 */
class ElementIndexViewAttributeEvent extends Event
{
    /**
     * @var ElementQueryInterface The Element query being built
     */
    public ElementQueryInterface $query;

    /**
     * @var string The attribute name, as registered by the Element, and not implicitly a native field or attribute name.
     */
    public string $attribute;
}
