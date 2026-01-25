<?php

declare(strict_types=1);

namespace CraftCms\Cms\Search\Jobs;

use Craft;
use craft\base\Batchable;
use craft\db\Query;
use craft\db\QueryBatcher;
use craft\db\Table;
use CraftCms\Cms\Queue\BatchedJob;
use CraftCms\Cms\Support\Facades\I18N;
use CraftCms\Cms\Support\Json;
use Illuminate\Support\Facades\DB;

/**
 * Finds and replaces text in element content.
 *
 * @since 6.0.0
 */
final class FindAndReplace extends BatchedJob
{
    /**
     * Creates a new FindAndReplace job.
     *
     * @param  string  $find  The search text.
     * @param  string  $replace  The replacement text.
     */
    public function __construct(
        public string $find,
        public string $replace,
    ) {}

    protected function loadData(): Batchable
    {
        $where = [
            'or',
            ['like', 'title', $this->find],
        ];

        if (Craft::$app->getDb()->getIsPgsql()) {
            $where[] = ['like', 'CAST("content" AS TEXT)', $this->find];
        } else {
            $where[] = ['like', 'content', $this->find];
        }

        return new QueryBatcher(
            (new Query)
                ->select(['id', 'title', 'content'])
                ->from(Table::ELEMENTS_SITES)
                ->orderBy(['id' => SORT_ASC])
                ->where($where),
        );
    }

    #[\Override]
    public function handle(): void
    {
        // Reset offset for each batch since items from previous batch
        // will no longer match the query after being processed
        $this->itemOffset = 0;

        parent::handle();
    }

    protected function processItem(mixed $item): void
    {
        if (is_string($item['content'])) {
            $item['content'] = Json::decode($item['content']);
        }

        $this->replaceRecursive($item['title']);
        $this->replaceRecursive($item['content']);

        DB::table(\CraftCms\Cms\Database\Table::ELEMENTS_SITES)
            ->where('id', $item['id'])
            ->update([
                'title' => $item['title'],
                'content' => $item['content'],
            ]);
    }

    /**
     * Recursively replaces text in a value.
     */
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

    #[\Override]
    protected function defaultDescription(): string
    {
        return I18N::prep('Replacing "{find}" with "{replace}"', [
            'find' => $this->find,
            'replace' => $this->replace,
        ]);
    }
}
