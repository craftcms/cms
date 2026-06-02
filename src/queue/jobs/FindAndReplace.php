<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\queue\jobs;

use Craft;
use craft\base\Batchable;
use craft\db\Query;
use craft\db\QueryBatcher;
use craft\db\Table;
use craft\helpers\Db;
use craft\helpers\Json;
use craft\i18n\Translation;
use craft\queue\BaseBatchedJob;

/**
 * FindAndReplace job
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class FindAndReplace extends BaseBatchedJob
{
    /**
     * @var string|null The search text
     */
    public ?string $find = null;

    /**
     * @var string|null The replacement text
     */
    public ?string $replace = null;

    protected function loadData(): Batchable
    {
        $where = [
            'or',
            ['like', 'title', $this->find],
        ];

        if (Craft::$app->getDb()->getIsPgsql()) {
            $where[] = ['like', "CAST(\"content\" AS TEXT)", $this->find];
        } else {
            $where[] = ['like', "content", $this->find];
        }

        return new QueryBatcher(
            (new Query())
                ->select(['id', 'title', 'content'])
                ->from(Table::ELEMENTS_SITES)
                ->orderBy(['id' => SORT_ASC])
                ->where($where),
        );
    }

    /**
     * @inheritdoc
     */
    public function execute($queue): void
    {
        // for this job, we need to ensure the offset is zero for each batch;
        // that's because the items from previous batch will no longer match the query when we start the second one,
        // so setting offset at e.g. 100 after the first batch, would mean doing find and replace on the first 100 records,
        // skipping next 100, running it on the 3rd set of records and so on
        $this->itemOffset = 0;

        parent::execute($queue);
    }

    protected function processItem(mixed $item): void
    {
        if (is_string($item['content'])) {
            $item['content'] = Json::decode($item['content']);
        }

        $this->replaceRecursive($item['title']);
        $this->replaceRecursive($item['content']);

        Db::update(Table::ELEMENTS_SITES, [
            'title' => $item['title'],
            'content' => $item['content'],
        ], [
            'id' => $item['id'],
        ], updateTimestamp: false);
    }

    private function replaceRecursive(string|array|null &$value): void
    {
        if ($value === null) {
            return;
        }

        if (is_string($value)) {
            $value = str_replace($this->find, $this->replace, $value);
            return;
        }

        foreach ($value as &$v) {
            if (is_string($v) || is_array($v)) {
                $this->replaceRecursive($v);
            }
        }
    }

    /**
     * @inheritdoc
     */
    protected function defaultDescription(): ?string
    {
        return Translation::prep('app', 'Replacing “{find}” with “{replace}”', [
            'find' => $this->find,
            'replace' => $this->replace,
        ]);
    }
}
