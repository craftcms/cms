<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\queue\jobs;

use Craft;
use craft\base\Element;
use craft\base\Field;
use craft\behaviors\DraftBehavior;
use craft\behaviors\RevisionBehavior;
use craft\db\Connection;
use craft\db\Query;
use craft\db\Table;
use craft\elements\Entry;
use craft\elements\User;
use craft\helpers\ArrayHelper;
use craft\helpers\DateTimeHelper;
use craft\helpers\Json;
use craft\queue\BaseJob;
use craft\services\Drafts;
use craft\services\Elements;
use craft\services\Entries;
use craft\services\Fields;
use craft\services\Revisions;
use yii\base\Exception;
use yii\db\Expression;

/**
 * ConvertEntryRevisions job
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.2
 */
class ConvertEntryRevisions extends BaseJob
{
    private $queue;
    /** @var Connection */
    private $db;
    /** @var Elements */
    private $elementsService;
    /** @var Entries */
    private $entriesService;
    /** @var Fields */
    private $fieldsService;
    /** @var Drafts */
    private $draftsService;
    /** @var Revisions */
    private $revisionsService;
    /** @var User */
    private $defaultCreator;
    /** @var Entry[] */
    private $entries;

    /**
     * @inheritdoc
     */
    public function execute($queue)
    {
        $this->queue = $queue;
        $this->db = Craft::$app->getDb();
        $this->elementsService = Craft::$app->getElements();
        $this->entriesService = Craft::$app->getEntries();
        $this->fieldsService = Craft::$app->getFields();
        $this->draftsService = Craft::$app->getDrafts();
        $this->revisionsService = Craft::$app->getRevisions();

        $this->defaultCreator = User::find()
            ->admin()
            ->orderBy(['elements.id' => SORT_ASC])
            ->one();

        $this->convertDrafts();
        $this->convertVersions();
    }

    /**
     * @inheritdoc
     */
    protected function defaultDescription(): string
    {
        return Craft::t('app', 'Updating entry drafts and revisions');
    }

    /**
     * @param int $id
     * @param int $siteId
     * @return Entry|null
     */
    private function entry(int $id, int $siteId)
    {
        $key = "{$id}-{$siteId}";
        if (!isset($this->entries[$key])) {
            $this->entries[$key] = $this->entriesService->getEntryById($id, $siteId) ?: false;
        }
        return $this->entries[$key] ?: null;
    }

    private function setFieldValues(Entry $entry, $fieldValues)
    {
        if (!$fieldValues) {
            return;
        }

        foreach ($fieldValues as $id => $value) {
            /** @var Field $field */
            $field = $this->fieldsService->getFieldById($id);
            if ($field) {
                $entry->setFieldValue($field->handle, $value);
            }
        }
    }

    private function convertDrafts()
    {
        if (!$this->db->tableExists(Table::ENTRYDRAFTS)) {
            return;
        }

        $query = (new Query())
            ->select(['id', 'entryId', 'siteId', 'creatorId', 'name', 'notes', 'data', 'dateCreated'])
            ->from(['d' => Table::ENTRYDRAFTS])
            ->where(['not exists', (new Query())->from(['{{%entrydrafterrors}}'])->where('[[draftId]] = [[d.id]]')])
            ->orderBy(['id' => SORT_DESC]);

        $total = $query->count();

        foreach ($query->each() as $i => $result) {
            $this->setProgress($this->queue, $i / $total, 'Draft ' . $i . ' of ' . $total);
            try {
                $this->convertDraft($result);
            } catch (\Throwable $e) {
                $this->db->createCommand()
                    ->insert('{{%entrydrafterrors}}', [
                        'draftId' => $result['id'],
                        'error' => $e->getMessage(),
                    ], false)
                    ->execute();
                continue;
            }
            $this->db->createCommand()
                ->delete(Table::ENTRYDRAFTS, ['id' => $result['id']])
                ->execute();
        }
    }

    private function convertDraft(array $result)
    {
        // Get the current entry
        $entry = $this->entry($result['entryId'], $result['siteId']);

        if (!$entry) {
            return;
        }

        // Create the draft
        /** @var Entry|DraftBehavior $draft */
        $draft = $this->draftsService->createDraft(
            $entry,
            $result['creatorId'] ?: $this->defaultCreator->id,
            $result['name'] ?: null,
            $result['notes'] ?: null,
            [
                'dateCreated' => DateTimeHelper::toDateTime($result['dateCreated']),
            ]
        );

        // Set the attributes
        $attributes = Json::decode(ArrayHelper::remove($result, 'data'));
        $fieldValues = ArrayHelper::remove($attributes, 'fields');

        $attributes['postDate'] = $attributes['postDate'] ? DateTimeHelper::toDateTime($attributes['postDate']) : null;
        $attributes['expiryDate'] = $attributes['expiryDate'] ? DateTimeHelper::toDateTime($attributes['expiryDate']) : null;
        $draft->setAttributes($attributes, false);
        $this->setFieldValues($draft, $fieldValues);

        // Try to save it
        $draft->setScenario(Element::SCENARIO_ESSENTIALS);
        if (!$this->elementsService->saveElement($draft)) {
            $this->elementsService->deleteElement($draft);
            throw new Exception($draft->getErrorSummary(true));
        }
    }

    private function convertVersions()
    {
        // Find all the versions from entries that don't have revisions yet
        $query = (new Query())
            ->select(['id', 'entryId', 'creatorId', 'siteId', 'num', 'notes', 'data', 'dateCreated', 'uid'])
            ->from(['v' => Table::ENTRYVERSIONS])
            ->where(['not exists', (new Query())->from(['{{%entryversionerrors}}'])->where('[[versionId]] = [[v.id]]')])
            ->orderBy(['id' => SORT_DESC]);

        // If maxRevisions is set, filter out versions that would have been deleted by now
        $maxRevisions = Craft::$app->getConfig()->getGeneral()->maxRevisions;
        if ($maxRevisions > 0) {
            $numSql = $this->db->getIsMysql() ? 'cast([[num]] as signed)' : '[[num]]';
            $query->andWhere(['>', 'num', (new Query())
                ->select(new Expression("max({$numSql})" . ($maxRevisions ? " - {$maxRevisions}" : '')))
                ->from([Table::ENTRYVERSIONS])
                ->where('[[entryId]] = [[v.entryId]]')
            ]);
        }

        $total = $query->count();

        foreach ($query->each() as $i => $result) {
            $this->setProgress($this->queue, $i / $total, 'Revision ' . $i . ' of ' . $total);
            try {
                $this->convertVersion($result);
            } catch (\Throwable $e) {
                $this->db->createCommand()
                    ->insert('{{%entryversionerrors}}', [
                        'versionId' => $result['id'],
                        'error' => $e->getMessage(),
                    ], false)
                    ->execute();
                continue;
            }
            $this->db->createCommand()
                ->delete(Table::ENTRYVERSIONS, ['id' => $result['id']])
                ->execute();
        }
    }

    private function convertVersion(array $result)
    {
        // Get the current entry
        $entry = $this->entry($result['entryId'], $result['siteId']);

        if (!$entry) {
            return;
        }

        // If the entry already has any revisions that are more recent than this one, adjust their numbers
        $lowestNum = (new Query())
            ->select(['min([[num]])'])
            ->from(['r' => Table::REVISIONS])
            ->innerJoin(Table::ELEMENTS . ' e', '[[e.revisionId]] = [[r.id]]')
            ->where(['sourceId' => $entry->id])
            ->andWhere(['>=', 'e.dateCreated', $result['dateCreated']])
            ->scalar();

        if ($lowestNum) {
            $diff = ($result['num'] - $lowestNum) + 1;
            if ($diff) {
                $this->db->createCommand()->update(Table::REVISIONS, [
                    'num' => new Expression('[[num]]' . ($diff > 0 ? '+' : '-') . abs($diff))
                ], ['and',
                    ['sourceId' => $entry->id],
                    ['>=', 'num', $lowestNum],
                ], [], false)->execute();
            }
        }

        // Create the revision
        /** @var Entry|RevisionBehavior $revision */
        $revision = $this->revisionsService->createRevision(
            $entry,
            $result['creatorId'] ?: $this->defaultCreator->id,
            $result['notes'] ?: null,
            [
                'revisionNum' => $result['num'],
                'dateCreated' => DateTimeHelper::toDateTime($result['dateCreated']),
            ],
            true
        );

        // Set the attributes
        $attributes = Json::decode(ArrayHelper::remove($result, 'data'));
        $fieldValues = ArrayHelper::remove($attributes, 'fields');

        $attributes['postDate'] = $attributes['postDate'] ? DateTimeHelper::toDateTime($attributes['postDate']) : null;
        $attributes['expiryDate'] = $attributes['expiryDate'] ? DateTimeHelper::toDateTime($attributes['expiryDate']) : null;
        $revision->setAttributes($attributes, false);
        $this->setFieldValues($revision, $fieldValues);

        // Try to save it
        $revision->setScenario(Element::SCENARIO_ESSENTIALS);
        if (!$this->elementsService->saveElement($revision)) {
            $this->elementsService->deleteElement($revision);
            throw new Exception($revision->getErrorSummary(true));
        }
    }
}
