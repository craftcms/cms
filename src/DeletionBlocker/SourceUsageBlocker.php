<?php

declare(strict_types=1);

namespace CraftCms\Cms\DeletionBlocker;

use Closure;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Field\Assets as AssetsField;
use CraftCms\Cms\Field\BaseRelationField;
use CraftCms\Cms\Field\Entries;
use CraftCms\Cms\Support\Facades\Fields;
use Illuminate\Support\Facades\DB;

use function CraftCms\Cms\t;

/**
 * Lists the relation fields that offer a source, such as a section or volume, and how many elements relate to the
 * source’s elements through each.
 *
 * @since 6.0.0
 */
class SourceUsageBlocker extends BaseResolvableDeletionBlocker
{
    /**
     * @param  class-string<BaseRelationField>  $fieldType  The relation field type, such as {@see Entries}
     * @param  list<string>  $sourceKeys  The source keys that select the source in the fields’ settings, such as `section:<uid>`
     * @param  string  $sourceName  The source’s name, such as a section’s name
     * @param  string  $elementTable  The table that holds the source’s elements, such as {@see Table::ENTRIES}
     * @param  array<string, mixed>  $elementCondition  The condition on that table that selects the source’s elements, such as `['sectionId' => 1]`
     * @param  (Closure(int): string)|null  $summary  Returns the blocker’s summary for the number of fields, such as
     *                                                “The section is selected as a source for 2 Entries fields.”
     *                                                Defaults to a summary that doesn’t name the source.
     * @param  array<string, mixed>  $config
     */
    public function __construct(
        protected readonly string $fieldType,
        protected readonly array $sourceKeys,
        protected readonly string $sourceName,
        protected readonly string $elementTable,
        protected readonly array $elementCondition,
        protected readonly ?Closure $summary = null,
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

        return t('It’s selected as a source for {num, number} {num, plural, =1{field} other{fields}}.', [
            'num' => $num,
        ]);
    }

    public function getResolutionLabel(): string
    {
        return t('Remove from field sources');
    }

    protected function getResolvedMessage(): string
    {
        return t('The {num, plural, =1{field} other{fields}} will no longer include it as a source once it’s deleted.', [
            'num' => count($this->getItems()),
        ]);
    }

    protected function defineItems(): array
    {
        $elementType = $this->fieldType::elementType();

        return Fields::getFieldsByType($this->fieldType)
            ->filter(fn (BaseRelationField $field) => $this->offersSource($field))
            ->map(function (BaseRelationField $field) use ($elementType) {
                $query = DB::table(Table::RELATIONS, 'relations')
                    ->join("$this->elementTable as targets", 'targets.id', '=', 'relations.targetId')
                    ->join(Table::ELEMENTS.' as sources', 'sources.id', '=', 'relations.sourceId')
                    ->where('relations.fieldId', $field->id)
                    // Only count canonical source elements, not drafts, revisions, or trashed ones
                    ->whereNull([
                        'sources.canonicalId',
                        'sources.draftId',
                        'sources.revisionId',
                        'sources.dateDeleted',
                    ]);

                foreach ($this->elementCondition as $column => $value) {
                    $query->where("targets.$column", $value);
                }

                $count = $query->distinct()->count('relations.sourceId');

                return [
                    'label' => $field->getUiLabel(),
                    'count' => $count,
                    'description' => t('{count, number} {count, plural, =1{element relates} other{elements relate}} to {name} {type}.', [
                        'count' => $count,
                        'name' => $this->sourceName,
                        'type' => $elementType::pluralLowerDisplayName(),
                    ]),
                ];
            })
            ->values()
            ->all();
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

        return is_array($sources) && array_intersect(['*', ...$this->sourceKeys], $sources) !== [];
    }
}
