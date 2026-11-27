<?php

declare(strict_types=1);

namespace CraftCms\Cms\DeletionBlocker;

use CraftCms\Cms\Entry\Data\EntryType;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Section\Data\Section;

use function CraftCms\Cms\t;

/**
 * Lists the sections and fields that use an entry type, and how many entries of that type each has.
 *
 * @since 6.0.0
 */
class EntryTypeUsageBlocker extends BaseResolvableDeletionBlocker
{
    private int $numSections = 0;

    private int $numFields = 0;

    /** @param array<string, mixed> $config */
    public function __construct(
        protected readonly EntryType $entryType,
        array $config = [],
    ) {
        parent::__construct($config);
    }

    public function getKey(): string
    {
        return 'usages';
    }

    public function getSummary(): string
    {
        $this->getItems();

        if (! $this->numFields) {
            return t('The entry type is used by {num, number} {num, plural, =1{section} other{sections}}.', [
                'num' => $this->numSections,
            ]);
        }

        if (! $this->numSections) {
            return t('The entry type is used by {num, number} {num, plural, =1{field} other{fields}}.', [
                'num' => $this->numFields,
            ]);
        }

        return t('The entry type is used by {numSections, number} {numSections, plural, =1{section} other{sections}} and {numFields, number} {numFields, plural, =1{field} other{fields}}.', [
            'numSections' => $this->numSections,
            'numFields' => $this->numFields,
        ]);
    }

    public function getResolutionLabel(): string
    {
        $this->getItems();

        if (! $this->numFields) {
            return t('Remove from {num, plural, =1{section} other{sections}}', [
                'num' => $this->numSections,
            ]);
        }

        if (! $this->numSections) {
            return t('Remove from {num, plural, =1{field} other{fields}}', [
                'num' => $this->numFields,
            ]);
        }

        return t('Remove from sections and fields');
    }

    protected function getResolvedMessage(): string
    {
        $this->getItems();

        if (! $this->numFields) {
            return t('The entry type will be removed from the {num, plural, =1{section} other{sections}} once it’s deleted.', [
                'num' => $this->numSections,
            ]);
        }

        if (! $this->numSections) {
            return t('The entry type will be removed from the {num, plural, =1{field} other{fields}} once it’s deleted.', [
                'num' => $this->numFields,
            ]);
        }

        return t('The entry type will be removed from the sections and fields once it’s deleted.');
    }

    protected function defineItems(): array
    {
        $items = [];

        foreach ($this->entryType->findUsages() as $usage) {
            // Match what the deletion trashes: canonical entries and unpublished drafts
            $query = Entry::find()
                ->typeId($this->entryType->id)
                ->drafts(null)
                ->draftOf(false)
                ->whereNull('elements.canonicalId')
                ->site('*')
                ->unique()
                ->status(null);

            if ($usage instanceof Section) {
                $this->numSections++;
                $query->sectionId($usage->id);
            } else {
                $this->numFields++;
                // Nested entries in unpublished owner drafts are trashed too
                $query->fieldId($usage->id)->allowOwnerDrafts();
            }

            $count = $query->count();

            $items[] = [
                'label' => $usage->getUiLabel(),
                'count' => $count,
                'description' => t('{count, number} {count, plural, =1{entry} other{entries}} of this type.', [
                    'count' => $count,
                ]),
            ];
        }

        return $items;
    }
}
