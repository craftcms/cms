<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\elements\deletionblockers;

use Craft;
use craft\base\ElementInterface;
use craft\db\Query;
use craft\db\Table;
use craft\fields\Assets as AssetsField;
use craft\fields\BaseRelationField;

/**
 * SourceUsageBlocker lists the relation fields that offer a source, such as a section or volume, and how many elements
 * relate to the source’s elements through each.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 5.13.0
 */
class SourceUsageBlocker extends BaseResolvableDeletionBlocker
{
    /**
     * Constructor
     *
     * @param class-string<BaseRelationField> $fieldType The relation field type, such as `craft\fields\Entries`
     * @param string[] $sourceKeys The source keys that select the source in the fields’ settings, such as `section:<uid>`
     * @param string $sourceName The source’s name, such as a section’s name
     * @param string $elementTable The table that holds the source’s elements, such as `{{%entries}}`
     * @param array $elementCondition The condition on that table that selects the source’s elements, such as `['sectionId' => 1]`
     * @param (callable(int): string)|null $summary A callable that returns the blocker’s summary for the number of
     * fields, such as “The section is selected as a source for 2 Entries fields.” Defaults to a summary that doesn’t name the source.
     * Pass a callable rather than a string so translators get a whole sentence.
     */
    public function __construct(
        protected readonly string $fieldType,
        protected readonly array $sourceKeys,
        protected readonly string $sourceName,
        protected readonly string $elementTable,
        protected readonly array $elementCondition,
        protected readonly mixed $summary = null,
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
        $num = count($this->getItems());

        if ($this->summary !== null) {
            return ($this->summary)($num);
        }

        return Craft::t('app', 'It’s selected as a source for {num, number} {num, plural, =1{field} other{fields}}.', [
            'num' => $num,
        ]);
    }

    public function getResolutionLabel(): string
    {
        return Craft::t('app', 'Remove from field sources');
    }

    protected function getResolvedMessage(): string
    {
        return Craft::t('app', 'The {num, plural, =1{field} other{fields}} will no longer include it as a source once it’s deleted.', [
            'num' => count($this->getItems()),
        ]);
    }

    protected function defineItems(): array
    {
        /** @var class-string<ElementInterface> $elementType */
        $elementType = $this->fieldType::elementType();
        $items = [];

        foreach (Craft::$app->getFields()->getFieldsByType($this->fieldType) as $field) {
            /** @var BaseRelationField $field */
            if (!$this->offersSource($field)) {
                continue;
            }

            $condition = ['relations.fieldId' => $field->id];
            foreach ($this->elementCondition as $column => $value) {
                $condition["targets.$column"] = $value;
            }

            $count = (int)(new Query())
                ->from(['relations' => Table::RELATIONS])
                ->innerJoin(['targets' => $this->elementTable], '[[targets.id]] = [[relations.targetId]]')
                ->innerJoin(['sources' => Table::ELEMENTS], '[[sources.id]] = [[relations.sourceId]]')
                ->where($condition)
                ->andWhere([
                    'sources.canonicalId' => null,
                    'sources.draftId' => null,
                    'sources.revisionId' => null,
                    'sources.dateDeleted' => null,
                ])
                ->count('DISTINCT [[relations.sourceId]]');

            $items[] = [
                'label' => $field->getUiLabel(),
                'count' => $count,
                'description' => Craft::t('app', '{count, number} {count, plural, =1{element relates} other{elements relate}} to {name} {type}.', [
                    'count' => $count,
                    'name' => $this->sourceName,
                    'type' => $elementType::pluralLowerDisplayName(),
                ]),
            ];
        }

        return $items;
    }

    private function offersSource(BaseRelationField $field): bool
    {
        if ($field->allowMultipleSources) {
            $sources = $field->sources;
        } else {
            $sources = $field->source !== null ? [$field->source] : [];
        }

        if ($field instanceof AssetsField && $field->restrictLocation && $field->restrictedLocationSource) {
            $sources = [$field->restrictedLocationSource];
        }

        if ($sources === '*') {
            return true;
        }

        return is_array($sources) && array_intersect(['*', ...$this->sourceKeys], $sources);
    }
}
