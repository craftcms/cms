<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\fields;

use Craft;
use craft\elements\db\EntryQuery;
use craft\elements\Entry;

/**
 * Entries represents an Entries field.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class Entries extends BaseRelationField
{
    // Static
    // =========================================================================

    /**
     * @inheritdoc
     */
    public static function displayName(): string
    {
        return Craft::t('app', 'Entries');
    }

    /**
     * @inheritdoc
     */
    protected static function elementType(): string
    {
        return Entry::class;
    }

    /**
     * @inheritdoc
     */
    public static function defaultSelectionLabel(): string
    {
        return Craft::t('app', 'Add an entry');
    }

    /**
     * @inheritdoc
     */
    public static function valueType(): string
    {
        return EntryQuery::class;
    }
}
