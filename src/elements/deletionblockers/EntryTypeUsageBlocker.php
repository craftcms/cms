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
        return Craft::t('app', 'The entry type is used by {num, number} {num, plural, =1{section or field} other{sections and fields}}.', [
            'num' => count($this->getItems()),
        ]);
    }

    public function getResolutionLabel(): string
    {
        return Craft::t('app', 'Remove from sections and fields');
    }

    protected function getResolvedMessage(): string
    {
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
                ->site('*')
                ->unique()
                ->status(null);

            if ($usage instanceof Section) {
                $query->sectionId($usage->id);
            } else {
                $query->fieldId($usage->id);
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
