<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\gql\resolvers\mutations;

use craft\elements\db\EntryQuery;

/**
 * Class SaveDraft
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.5.0
 */
class SaveDraft extends SaveEntry
{
    /**
     * @inheritdoc
     */
    protected function identifyEntry(EntryQuery $entryQuery, array $arguments): EntryQuery
    {
        return $entryQuery->draftId($arguments['draftId']);
    }
}
