<?php

declare(strict_types=1);

namespace CraftCms\Cms\Entry\Import;

use CraftCms\Cms\Element\Concerns\Structurable;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\Import\ElementImporter;
use CraftCms\Cms\Element\Queries\Contracts\ElementQueryInterface;
use CraftCms\Cms\Element\Queries\EntryQuery;
use CraftCms\Cms\Entry\Data\EntryType;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Entry\Validation\Rules\EntryTypeRule;
use CraftCms\Cms\Section\Data\Section;
use CraftCms\Cms\Section\Enums\SectionType;
use CraftCms\Cms\Section\Validation\Rules\SectionRule;
use CraftCms\Cms\Support\Facades\Elements;
use CraftCms\Cms\Support\Facades\EntryTypes;
use CraftCms\Cms\Support\Facades\Sections;
use CraftCms\Cms\Ui\Controls\Choice;
use CraftCms\Cms\Ui\Nodes\Field as UiField;
use CraftCms\Cms\Ui\Nodes\Group;
use Illuminate\Contracts\Database\Query\Builder;
use Override;

use function CraftCms\Cms\t;

/**
 * Imports data into Entry elements.
 *
 * @since 6.0.0
 */
class EntryImporter extends ElementImporter
{
    public protected(set) ?string $section = null;

    public protected(set) ?string $entryType = null;

    #[Override]
    public static function targetClass(): string
    {
        return Entry::class;
    }

    #[Override]
    public static function displayName(): string
    {
        return t('Entries');
    }

    #[Override]
    public static function getDefaultTransformer(): ?string
    {
        return EntryTransformer::class;
    }

    #[Override]
    public function settingsUi(): array
    {
        return [
            ...parent::settingsUi(),
            UiField::make(t('Section'), Choice::make(['section'])
                ->value($this->section)
                ->placeholder(t('Please select'))
                ->options($this->availableSections())
                ->reactive())
                ->instructions(t('The section to import into.')),
            Group::make('entry-type-group', [
                // reactive: choosing an entry type is what resolves the field layout,
                // which the step's mapping is gated on
                UiField::make(t('Entry Type'), Choice::make('entryType')
                    ->value($this->entryType)
                    ->placeholder(t('Please select'))
                    ->options($this->availableEntryTypes())
                    ->reactive())
                    ->instructions(t('The entry type to import into.')),
            ])
                ->dependsOn('settings.section')
                ->visible($this->section !== null),
        ];
    }

    #[Override]
    public function refreshSettingsUi(array $settings): void
    {
        parent::refreshSettingsUi($settings);

        if (array_key_exists('section', $settings)) {
            $this->section($settings['section']);
        }
        if (array_key_exists('entryType', $settings)) {
            $this->entryType($settings['entryType']);
        }
    }

    /**
     * Sets the target section by uid.
     */
    public function section(string|int|Section|null $value): self
    {
        $result = Sections::resolveSection($value);
        $this->section = $result?->uid;

        return $this;
    }

    /**
     * Sets the target entry type by uid.
     */
    public function entryType(string|int|EntryType|null $value): self
    {
        $result = EntryTypes::resolveEntryType($value);
        if (! $result) {
            $this->entryType = null;
            $this->fieldLayout(null);
        } else {
            $this->entryType = $result->uid;
            $this->fieldLayout($result->getFieldLayout());
        }

        return $this;
    }

    #[Override]
    protected static function settingNames(): array
    {
        return [...parent::settingNames(), 'section', 'entryType'];
    }

    #[Override]
    public static function getSettingsRules(): array
    {
        return array_merge(parent::getSettingsRules(), [
            'settings.section' => ['required', new SectionRule],
            'settings.entryType' => ['required', new EntryTypeRule],
        ]);
    }

    #[Override]
    public static function getSettingsMessages(): array
    {
        return array_merge(parent::getSettingsMessages(), [
            'settings.section.required' => t('Section must be provided.'),
            'settings.entryType.required' => t('Entry Type must be provided.'),
        ]);
    }

    #[Override]
    public function toArrayData(): array
    {
        $data = parent::toArrayData();
        $data['settings']['section'] = $this->section ?? null;
        $data['settings']['entryType'] = $this->entryType ?? null;

        return $data;
    }

    private static function normalizeParent(string|int|Entry|null $value, int $sectionId, int $siteId): ?Entry
    {
        // @phpstan-ignore return.type
        return match (true) {
            $value instanceof Entry => $value,
            $value === null => null,
            is_numeric($value) => Elements::getElementById((int) $value, Entry::class),
            // if it's a string query entries by title or slug only in the section and site we're importing into
            default => Entry::find()
                ->sectionId($sectionId)
                ->siteId($siteId)
                ->status(null)
                ->where(fn (Builder $query) => $query->where('title', $value)->orWhere('slug', $value))
                ->one()
        };
    }

    #[Override]
    public function prepareNewRootElementForImport(array &$data, ?ElementInterface $element = null): ElementInterface
    {
        /** @var Entry $element */
        if ($element === null) {
            $section = null;
            $entryType = null;

            // if it's UI-driven element import where the fieldLayout was chosen in the editable config,
            // we need to ensure the sectionId and typeId are both set
            if ($this->section) {
                $section = Sections::getSectionByUid($this->section);
                $data['sectionId'] = $section->id;
            } elseif (isset($data['sectionId'])) {
                $section = Sections::resolveSection($data['sectionId']);
            }
            if ($this->entryType) {
                $entryType = EntryTypes::getEntryTypeByUid($this->entryType);
                $data['typeId'] = $entryType->id;
            } elseif (isset($data['typeId'])) {
                $entryType = EntryTypes::resolveEntryType($data['typeId']);
            }

            $element = new ($this::targetClass());
            $element->sectionId = $section?->id;

            // Element::attributes() only lists parentId once the structure ID is known
            if ($section?->type === SectionType::Structure) {
                $element->structureId = $section->structureId;
            }

            if ($entryType) {
                $element->setTypeId($entryType->id);
                $element->fieldLayoutId = $entryType->getFieldLayoutId();
                if (isset($data['matchCriteria']['typeId'])) {
                    unset($data['matchCriteria']['typeId']);
                }
            }
        }

        parent::prepareNewRootElementForImport($data, $element);

        return $element;
    }

    #[Override]
    public function prepareRootElementImportQuery(ElementInterface $element, ElementQueryInterface $query): ElementQueryInterface
    {
        /** @var Entry $element */
        /** @var EntryQuery<Entry> $query */
        return $query
            ->sectionId($element->sectionId)
            ->typeId($element->getTypeId());
    }

    #[Override]
    public function setAttributesForImport(ElementInterface $element, array $attributes, array $data): void
    {
        // for UI-based import, ensure we're not changing type ID compared to what we chose in the field layout provider step
        if (isset($this->section)) {
            unset($attributes['sectionId']);
        }
        if (isset($this->entryType) || isset($this->fieldLayout)) {
            unset($attributes['typeId']);
        }

        // if we're importing into a structure and have parentId data, try to find and set it
        /** @var Entry $element */
        if ($element->section?->type === SectionType::Structure && isset($data['parentId'])) {
            $parent = self::normalizeParent($data['parentId'], $element->section->id, $this->site->id);
            if ($parent !== null && $element->getParentId() !== $parent->id) {
                $element->setParent($parent);
            }

            unset($attributes['parentId']);
        }

        parent::setAttributesForImport($element, $attributes, $data);
    }

    #[Override]
    protected function isPropertyAvailable(array $prop): bool
    {
        // structure props only apply when importing into a structure section
        if ($prop['trait'] === Structurable::class) {
            $section = Sections::resolveSection($this->section);

            return $section && $section->type === SectionType::Structure;
        }

        return parent::isPropertyAvailable($prop);
    }

    /**
     * Returns the sections that can be imported into, as choice options.
     *
     * @return list<array{label: string, value: string}>
     */
    protected function availableSections(): array
    {
        return Sections::getAllSections()->map(fn ($section) => [
            'label' => $section->name,
            'value' => $section->uid,
        ])->all();
    }

    /**
     * Returns the selected section’s entry types, as choice options.
     *
     * @return list<array{label: string, value: string}>
     */
    protected function availableEntryTypes(): array
    {
        if ($this->section === null) {
            return [];
        }

        $section = Sections::getSectionByUid($this->section);

        return collect($section?->getEntryTypes())->map(fn ($entryType) => [
            'label' => $entryType->name,
            'value' => $entryType->uid,
        ])->all();
    }
}
