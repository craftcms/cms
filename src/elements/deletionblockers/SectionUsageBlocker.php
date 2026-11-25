<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\elements\deletionblockers;

use Craft;
use craft\db\Query;
use craft\db\Table;
use craft\fields\Entries as EntriesField;
use craft\models\Section;

/**
 * SectionUsageBlocker lists the Entries fields that offer a section as a source, and how many elements relate to its entries through each.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 5.13.0
 */
class SectionUsageBlocker extends BaseResolvableDeletionBlocker
{
    /**
     * Constructor
     */
    public function __construct(
        protected readonly Section $section,
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
        return Craft::t('app', 'The section is a source for {num, number} {num, plural, =1{field} other{fields}}.', [
            'num' => count($this->getItems()),
        ]);
    }

    public function getResolutionLabel(): string
    {
        return Craft::t('app', 'Remove from field sources');
    }

    protected function getResolvedMessage(): string
    {
        return Craft::t('app', 'The {num, plural, =1{field} other{fields}} will no longer include the section as a source once it’s deleted.', [
            'num' => count($this->getItems()),
        ]);
    }

    protected function defineItems(): array
    {
        $sources = ["section:{$this->section->uid}"];
        if ($this->section->type === Section::TYPE_SINGLE) {
            $sources[] = 'singles';
        }
        $items = [];

        foreach (Craft::$app->getFields()->getFieldsByType(EntriesField::class) as $field) {
            /** @var EntriesField $field */
            $offersSection = $field->sources === '*' || (
                is_array($field->sources) && array_intersect($sources, $field->sources)
            );
            if (!$offersSection) {
                continue;
            }

            $count = (int)(new Query())
                ->from(['relations' => Table::RELATIONS])
                ->innerJoin(['targets' => Table::ENTRIES], '[[targets.id]] = [[relations.targetId]]')
                ->innerJoin(['sources' => Table::ELEMENTS], '[[sources.id]] = [[relations.sourceId]]')
                ->where([
                    'relations.fieldId' => $field->id,
                    'targets.sectionId' => $this->section->id,
                    'sources.draftId' => null,
                    'sources.revisionId' => null,
                    'sources.dateDeleted' => null,
                ])
                ->count('DISTINCT [[relations.sourceId]]');

            $items[] = [
                'label' => $field->getUiLabel(),
                'count' => $count,
                'description' => Craft::t('app', '{count, number} {count, plural, =1{element relates} other{elements relate}} to entries in this section.', [
                    'count' => $count,
                ]),
            ];
        }

        return $items;
    }
}
