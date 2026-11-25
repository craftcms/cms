<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\elements\deletionblockers;

use Craft;
use craft\elements\Entry;
use craft\models\EntryType;
use craft\models\Section;

/**
 * EntryTypeUsageBlocker lists the sections and fields that use an entry type, and how many entries of that type each has.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 5.13.0
 */
class EntryTypeUsageBlocker extends BaseResolvableDeletionBlocker
{
    /**
     * @var int The number of sections that use the entry type
     */
    private int $numSections = 0;

    /**
     * @var int The number of fields that use the entry type
     */
    private int $numFields = 0;

    /**
     * Constructor
     */
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

        if (!$this->numFields) {
            return Craft::t('app', 'The entry type is used by {num, number} {num, plural, =1{section} other{sections}}.', [
                'num' => $this->numSections,
            ]);
        }

        if (!$this->numSections) {
            return Craft::t('app', 'The entry type is used by {num, number} {num, plural, =1{field} other{fields}}.', [
                'num' => $this->numFields,
            ]);
        }

        return Craft::t('app', 'The entry type is used by {numSections, number} {numSections, plural, =1{section} other{sections}} and {numFields, number} {numFields, plural, =1{field} other{fields}}.', [
            'numSections' => $this->numSections,
            'numFields' => $this->numFields,
        ]);
    }

    public function getResolutionLabel(): string
    {
        $this->getItems();

        if (!$this->numFields) {
            return Craft::t('app', 'Remove from {num, plural, =1{section} other{sections}}', [
                'num' => $this->numSections,
            ]);
        }

        if (!$this->numSections) {
            return Craft::t('app', 'Remove from {num, plural, =1{field} other{fields}}', [
                'num' => $this->numFields,
            ]);
        }

        return Craft::t('app', 'Remove from sections and fields');
    }

    protected function getResolvedMessage(): string
    {
        $this->getItems();

        if (!$this->numFields) {
            return Craft::t('app', 'The entry type will be removed from the {num, plural, =1{section} other{sections}} once it’s deleted.', [
                'num' => $this->numSections,
            ]);
        }

        if (!$this->numSections) {
            return Craft::t('app', 'The entry type will be removed from the {num, plural, =1{field} other{fields}} once it’s deleted.', [
                'num' => $this->numFields,
            ]);
        }

        return Craft::t('app', 'The entry type will be removed from the sections and fields once it’s deleted.');
    }

    protected function defineItems(): array
    {
        $items = [];

        foreach ($this->entryType->findUsages() as $usage) {
            $query = Entry::find()
                ->typeId($this->entryType->id)
                ->drafts(null)
                ->draftOf(false)
                ->andWhere(['elements.canonicalId' => null])
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

            $count = (int)$query->count();

            $items[] = [
                'label' => $usage->getUiLabel(),
                'count' => $count,
                'description' => Craft::t('app', '{count, number} {count, plural, =1{entry} other{entries}} of this type.', [
                    'count' => $count,
                ]),
            ];
        }

        return $items;
    }
}
