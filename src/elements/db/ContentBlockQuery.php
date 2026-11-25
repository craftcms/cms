<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\elements\db;

use craft\db\Table;
use craft\elements\ContentBlock;

/**
 * ContentBlockQuery represents a SELECT SQL statement for content blocks in a way that is independent of DBMS.
 *
 * @template TKey of array-key
 * @template TElement of ContentBlock
 * @extends ElementQuery<TKey,TElement>
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 5.8.0
 */
class ContentBlockQuery extends ElementQuery implements NestedElementQueryInterface
{
    use NestedElementQueryTrait;

    /**
     * @inheritdoc
     */
    protected function beforePrepare(): bool
    {
        if (!parent::beforePrepare()) {
            return false;
        }

        $this->joinElementTable(Table::CONTENTBLOCKS);

        $this->query->addSelect([
            'contentblocks.fieldId',
            'contentblocks.primaryOwnerId',
        ]);

        $this->applyNestedElementParams('contentblocks.fieldId', 'contentblocks.primaryOwnerId');

        return true;
    }
}
