<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\gql\resolvers\mutations;

use Craft;
use craft\db\Table;
use craft\elements\Entry;
use craft\gql\base\MutationResolver;
use craft\helpers\Db;
use GraphQL\Type\Definition\ResolveInfo;

/**
 * Class DeleteEntry
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.5.0
 */
class DeleteEntry extends MutationResolver
{
    /**
     * @inheritdoc
     */
    public function resolve($source, array $arguments, $context, ResolveInfo $resolveInfo)
    {
        $entryId = $arguments['id'];

        $entry = Entry::findOne($entryId);

        if (!$entry) {
            return true;
        }

        $entryTypeUid = Db::uidById(Table::ENTRYTYPES, $entry->typeId);
        $this->requireSchemaAction('entrytypes.' . $entryTypeUid, 'delete');
        
        Craft::$app->getElements()->deleteElementById($entryId);

        return true;
    }
}
