<?php

declare(strict_types=1);

namespace CraftCms\Cms\Entry\Import;

use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\Import\ElementImporter;
use CraftCms\Cms\Element\Import\ElementTransformer;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\FieldLayout\FieldLayout;
use CraftCms\Cms\Import\Importers\BaseImporter;
use CraftCms\Cms\Import\ImportLookups;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Facades\EntryTypes;
use CraftCms\Cms\Support\Facades\ImportLog;
use CraftCms\Cms\Support\Facades\Sections;
use CraftCms\Cms\Support\Query;
use CraftCms\Cms\User\Elements\User;
use Override;

/**
 * @since 6.0.0
 */
class EntryTransformer extends ElementTransformer
{
    #[Override]
    protected function fieldLayoutFor(ElementInterface $element, array $properties): ?FieldLayout
    {
        $fieldLayout = parent::fieldLayoutFor($element, $properties);

        // a new entry may not have a field layout yet, so fall back to the incoming entry type's
        if ($fieldLayout === null && isset($properties['typeId'])) {
            $fieldLayout = EntryTypes::getEntryTypeById((int) $properties['typeId'])?->getFieldLayout();
        }

        return $fieldLayout;
    }

    /**
     * Normalizes incoming authors to user IDs, looking them up by the username or email the step says they hold. A
     * user that can't be found is left out and logged. An integer is already an ID, as a default's are.
     */
    protected function normalizeAuthorIds(mixed $value, ElementInterface $element, BaseImporter $importer): mixed
    {
        $matchBy = $importer instanceof ElementImporter ? ($importer->fieldSettings['authorIds']['matchBy'] ?? 'id') : 'id';

        if (! in_array($matchBy, ['username', 'email'], true)) {
            return $value;
        }

        $values = array_values(Arr::wrap($value));
        $wanted = array_values(array_unique(array_filter(
            array_map(fn (mixed $item) => is_string($item) ? trim($item) : '', $values),
            fn (string $item) => $item !== '',
        )));
        // most items name the same few authors
        $ids = ImportLookups::remember($importer, "authorIds|$matchBy", $wanted, function (array $wanted) use ($matchBy): array {
            $ids = $this->userIds($wanted, $matchBy, live: true);
            $missing = array_values(array_filter($wanted, fn (string $item) => ! isset($ids[mb_strtolower($item)])));

            // as the author select offers them, users that aren't active come after those that are
            if ($missing !== []) {
                $ids += $this->userIds($missing, $matchBy, live: false);
            }

            return $ids;
        });

        $authorIds = [];

        foreach ($values as $item) {
            if (is_int($item)) {
                $authorIds[] = $item;
            } elseif (is_string($item) && trim($item) !== '') {
                $id = $ids[mb_strtolower(trim($item))] ?? null;

                if ($id === null) {
                    ImportLog::warning("No user with the $matchBy “{$item}” was found to be an author, so it was skipped.");
                } else {
                    $authorIds[] = $id;
                }
            }
        }

        return array_values(array_unique($authorIds));
    }

    /**
     * The lowest ID of the users whose attribute matches each value, keyed by lowercased value.
     *
     * @param  list<string>  $values
     * @return array<string, int>
     */
    private function userIds(array $values, string $attribute, bool $live): array
    {
        if ($values === []) {
            return [];
        }

        $query = User::find()->{$attribute}(['or', ...array_map(fn (string $value) => str_replace('\\,', ',', Query::escapeParam($value)), $values)])->reorder()->orderBy('elements.id');

        if (! $live) {
            $query->status(null);
        }

        $ids = [];

        foreach ($query->all() as $user) {
            $ids[mb_strtolower((string) $user->{$attribute})] ??= $user->id;
        }

        return $ids;
    }

    /**
     * Normalizes an incoming section value (an ID, UID or handle) to a section ID.
     */
    protected function normalizeSectionId(mixed $value, ElementInterface $element): ?int
    {
        return Sections::resolveSection($value)?->id;
    }

    /**
     * Normalizes an incoming entry-type value (an ID, UID or handle) to a type ID, falling back to the element's current type ID for any other kind of value.
     */
    protected function normalizeTypeId(mixed $value, ElementInterface $element): ?int
    {
        if (! is_int($value) && ! is_string($value)) {
            /** @var Entry $element */
            return $element->getTypeId();
        }

        return EntryTypes::resolveEntryType($value)?->id;
    }
}
