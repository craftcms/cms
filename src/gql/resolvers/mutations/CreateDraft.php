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
 * Class CreateDraft
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.5.0
 */
class CreateDraft extends MutationResolver
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

        $this->performSchemaCheck($entry);

        /** @var Entry $draft */
        $draft = Craft::$app->getDrafts()->createDraft($entry, $entry->authorId);

        return $draft->draftId;
    }

    /**
     * @param $entry
     * @return void
     * @throws \Throwable if cannot perform draft operation
     */
    protected function performSchemaCheck($entry)
    {
        $entryTypeUid = Db::uidById(Table::ENTRYTYPES, $entry->typeId);
        $sectionUid = Db::uidById(Table::SECTIONS, $entry->sectionId);

        if (!(Gql::canSchema('entrytypes.' . $entryTypeUid, 'write') && Gql::canSchema('sections.' . $sectionUid, 'write'))) {
            throw new Error('Unable to perform the action.');
        }
    }
}
