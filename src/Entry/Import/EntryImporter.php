<?php

declare(strict_types=1);

namespace CraftCms\Cms\Entry\Import;

use Closure;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\Import\ElementImporter;
use CraftCms\Cms\Entry\Data\EntryType;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Section\Data\Section;
use CraftCms\Cms\Section\Enums\SectionType;
use CraftCms\Cms\Support\Facades\Elements;
use CraftCms\Cms\Support\Facades\EntryTypes;
use CraftCms\Cms\Support\Facades\Sections;
use CraftCms\Cms\Ui\Controls\Choice;
use CraftCms\Cms\Ui\Nodes\Field as UiField;
use CraftCms\Cms\Ui\Nodes\Group;
use Illuminate\Contracts\Database\Query\Builder;
use Illuminate\Validation\Validator;
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

    /**
     * Convenience factory returning a new instance.
     */
    public static function create(): self
    {
        return new self;
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

    #[Override]
    public function storeSettings(array $settings): void
    {
        parent::storeSettings($settings);

        $this->section($settings['section'] ?? null);
        $this->entryType($settings['entryType'] ?? null);
    }

    #[Override]
    public function getSettings(): array
    {
        $settings = parent::getSettings();

        $settings['section'] = $this->section;
        $settings['entryType'] = $this->entryType;

        return $settings;
    }

    /**
     * Sets the target section by uid.
     */
    public function section(string|int|Section|null $value): self
    {
        $result = self::normalizeSection($value);
        $this->section = $result?->uid;

        return $this;
    }

    /**
     * Sets the target entry type by uid.
     */
    public function entryType(string|int|EntryType|null $value): self
    {
        $result = self::normalizeEntryType($value);
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
    public static function getSettingsRules(): array
    {
        return array_merge(parent::getSettingsRules(), [
            'settings.section' => [
                'required',
                fn ($attribute, $value, Closure $fail, Validator $validator) => static::validateSection($value, $attribute, $fail, $validator),
            ],
            'settings.entryType' => [
                'required',
                fn ($attribute, $value, Closure $fail, Validator $validator) => static::validateEntryType($value, $attribute, $fail, $validator),
            ],
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

    public static function validateSection(mixed $value, string $attribute, Closure $fail, Validator $validator): bool
    {
        // can't be empty
        if (empty($value)) {
            $fail($attribute, t('Section must be provided.'));

            return false;
        }

        if (self::normalizeSection($value) === null) {
            $fail($attribute, t('No Section found for “{section}”.', [
                'section' => $value,
            ]));

            return false;
        }

        return true;
    }

    public static function validateEntryType(mixed $value, string $attribute, Closure $fail, Validator $validator): bool
    {
        // can't be empty
        if (empty($value)) {
            $fail($attribute, t('Entry Type must be provided.'));

            return false;
        }

        if (self::normalizeEntryType($value) === null) {
            $fail($attribute, t('No Entry Type found for “{entryType}”.', [
                'entryType' => $value,
            ]));

            return false;
        }

        return true;
    }

    private static function normalizeSection(string|int|Section|null $value): ?Section
    {
        return match (true) {
            $value instanceof Section => $value,
            $value === null => null,
            is_numeric($value) => Sections::getSectionById((int) $value),
            default => Sections::getSectionByUid($value) ?? Sections::getSectionByHandle($value),
        };
    }

    private static function normalizeEntryType(string|int|EntryType|null $value): ?EntryType
    {
        return match (true) {
            $value instanceof EntryType => $value,
            $value === null => null,
            is_numeric($value) => EntryTypes::getEntryTypeById((int) $value),
            default => EntryTypes::getEntryTypeByUid($value) ?? EntryTypes::getEntryTypeByHandle($value),
        };
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
                $section = self::normalizeSection($data['sectionId']);
            }
            if ($this->entryType) {
                $entryType = EntryTypes::getEntryTypeByUid($this->entryType);
                $data['typeId'] = $entryType->id;
            } elseif (isset($data['typeId'])) {
                $entryType = self::normalizeEntryType($data['typeId']);
            }

            $element = new ($this::targetClass());
            $element->sectionId = $section?->id;

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
            if ($element->getParentId() !== $parent->id) {
                $element->setParent($parent);
            }
        }

        parent::setAttributesForImport($element, $attributes, $data);
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
