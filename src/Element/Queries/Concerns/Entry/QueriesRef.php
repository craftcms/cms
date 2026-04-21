<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Queries\Concerns\Entry;

use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Element\Queries\EntryQuery;
use Illuminate\Database\Query\Builder;
use Tpetry\QueryExpressions\Language\Alias;

/**
 * @internal
 */
trait QueriesRef
{
    /**
     * @var mixed The reference code(s) used to identify the element(s).
     *
     * This property is set when accessing elements via their reference tags, e.g. `{entry:section/slug}`.
     *
     * @used-by ref()
     */
    public mixed $ref = null;

    protected function initQueriesRef(): void
    {
        $this->beforeQuery(function (EntryQuery $query) {
            if (is_null($query->ref)) {
                return;
            }

            $refs = $query->ref;
            if (! is_array($refs)) {
                $refs = is_string($refs) ? str($refs)->explode(',') : [$refs];
            }

            $joinSections = false;
            $query->where(function (Builder $query) use (&$joinSections, $refs) {
                foreach ($refs as $ref) {
                    $parts = array_filter(explode('/', (string) $ref), static fn (string $part) => $part !== '');

                    if (empty($parts)) {
                        continue;
                    }

                    if (count($parts) === 1) {
                        $query->orWhereParam('elements_sites.slug', $parts[0]);

                        continue;
                    }

                    $query->where(function (Builder $query) use ($parts) {
                        $query->whereParam('sections.handle', $parts[0])
                            ->whereParam('elements_sites.slug', $parts[1]);
                    });

                    $joinSections = true;
                }
            });

            if ($joinSections) {
                $this->join(new Alias(Table::SECTIONS, 'sections'), 'sections.id', '=', 'entries.sectionId');
            }
        });
    }

    /**
     * Narrows the query results based on a reference string.
     */
    public function ref(mixed $value): static
    {
        $this->ref = $value;

        return $this;
    }
}
