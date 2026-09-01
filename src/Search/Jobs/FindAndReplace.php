<?php

declare(strict_types=1);

namespace CraftCms\Cms\Search\Jobs;

use CraftCms\Cms\Database\Expressions\Cast;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Queue\BatchedJob;
use CraftCms\Cms\Support\Facades\I18N;
use CraftCms\Cms\Support\Json;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Override;

/**
 * Finds and replaces text in element content.
 */
class FindAndReplace extends BatchedJob
{
    public function __construct(
        public string $find,
        public string $replace,
        protected ?string $description = null,
    ) {
        parent::__construct();
    }

    protected function getQuery(): Builder
    {
        return DB::table(Table::ELEMENTS_SITES)
            ->select(['id', 'title', 'content'])
            ->orderBy('id')
            ->where(fn (Builder $query) => $query
                ->orWhere('title', 'like', "%$this->find%")
                ->when(
                    DB::getDriverName() === 'pgsql',
                    fn (Builder $query) => $query->orWhereLike(new Cast('content', 'TEXT'), "%$this->find%"),
                    fn (Builder $query) => $query->orWhereLike('content', "%$this->find%"),
                )
            );
    }

    #[Override]
    public function handle(): void
    {
        // Reset offset for each batch since items from previous batch
        // will no longer match the query after being processed
        $this->itemOffset = 0;

        parent::handle();
    }

    protected function processItem(mixed $item): void
    {
        if (is_string($item->content)) {
            $item->content = Json::decode($item->content);
        }

        $this->replaceRecursive($item->title);
        $this->replaceRecursive($item->content);

        DB::table(Table::ELEMENTS_SITES)
            ->where('id', $item->id)
            ->update([
                'title' => $item->title,
                'content' => $item->content,
            ]);
    }

    /**
     * Recursively replaces text in a value.
     */
    private function replaceRecursive(mixed &$value): void
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

    #[Override]
    protected function defaultDescription(): string
    {
        return I18N::prep('Replacing "{find}" with "{replace}"', [
            'find' => $this->find,
            'replace' => $this->replace,
        ]);
    }
}
