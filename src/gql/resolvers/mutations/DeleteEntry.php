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
use craft\helpers\Gql;
use GraphQL\Error\Error;
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
            throw new Error('Unable to perform the action.');
        }

        $entryTypeUid = Db::uidById(Table::ENTRYTYPES, $entry->typeId);
        $sectionUid = Db::uidById(Table::SECTIONS, $entry->sectionId);

        if (!(Gql::canSchema('entrytypes.' . $entryTypeUid, 'write') && Gql::canSchema('sections.' . $sectionUid, 'write'))) {
            throw new Error('Unable to perform the action.');
        }

        Craft::$app->getElements()->deleteElementById($entryId);

        return true;
    }
}
