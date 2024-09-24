<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\queue\jobs;

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
        return new QueryBatcher(
            (new Query())
                ->select(['id', 'title', 'content'])
                ->from(Table::ELEMENTS_SITES)
                ->orderBy(['id' => SORT_ASC])
                ->where([
                    'or',
                    ['like', 'title', $this->find],
                    ['like', 'content', $this->find],
                ]),
        );
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
