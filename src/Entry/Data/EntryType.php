<?php

declare(strict_types=1);

namespace CraftCms\Cms\Entry\Data;

use Craft;
use craft\base\GqlInlineFragmentInterface;
use craft\helpers\UrlHelper;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Component\Component;
use CraftCms\Cms\Component\Contracts\Actionable;
use CraftCms\Cms\Component\Contracts\Chippable;
use CraftCms\Cms\Component\Contracts\Colorable;
use CraftCms\Cms\Component\Contracts\CpEditable;
use CraftCms\Cms\Component\Contracts\Describable;
use CraftCms\Cms\Component\Contracts\Iconic;
use CraftCms\Cms\Component\Contracts\Indicative;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Field\Contracts\ElementContainerFieldInterface;
use CraftCms\Cms\Field\Enums\TranslationMethod;
use CraftCms\Cms\FieldLayout\Concerns\HasFieldLayout;
use CraftCms\Cms\FieldLayout\Contracts\FieldLayoutProviderInterface;
use CraftCms\Cms\Section\Data\Section;
use CraftCms\Cms\Shared\Enums\Color;
use CraftCms\Cms\Support\Facades\EntryTypes;
use CraftCms\Cms\Support\Facades\Fields;
use CraftCms\Cms\Support\Facades\Sections;
use CraftCms\Cms\Validation\Rules\HandleRule;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Stringable;

use function CraftCms\Cms\t;

final class EntryType extends Component implements Actionable, Chippable, Colorable, CpEditable, Describable, FieldLayoutProviderInterface, GqlInlineFragmentInterface, Iconic, Indicative, Stringable
{
    use HasFieldLayout;

    public ?int $id = null;

    public ?string $name = null;

    public ?string $handle = null;

    public ?string $description = null;

    public ?string $icon = null;

    public ?Color $color = null;

    public string $uiLabelFormat = '{title}';

    public bool $hasTitleField = true;

    public TranslationMethod $titleTranslationMethod = TranslationMethod::Site;

    public ?string $titleTranslationKeyFormat = null;

    public ?string $titleFormat = null;

    public ?bool $allowLineBreaksInTitles = false;

    public ?bool $showSlugField = true;

    public TranslationMethod $slugTranslationMethod = TranslationMethod::Site;

    public ?string $slugTranslationKeyFormat = null;

    public ?bool $showStatusField = true;

    public ?string $uid = null;

    public bool $validateHandleUniqueness = true;

    public ?string $group = null;

    public ?self $original = null;

    public function __construct(
        array|object $config = [],
    ) {
        parent::__construct($config);

        if ($this->titleFormat === '') {
            $this->titleFormat = null;
        }

        if ($this->titleTranslationKeyFormat === '') {
            $this->titleTranslationKeyFormat = null;
        }

        if ($this->slugTranslationKeyFormat === '') {
            $this->slugTranslationKeyFormat = null;
        }
    }

    public static function get(int|string $id): ?static
    {
        return EntryTypes::getEntryTypeById($id);
    }

    public function getElementType(): string
    {
        return Entry::class;
    }

    public function getUiLabel(): string
    {
        return t($this->name, category: 'site');
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getIcon(): ?string
    {
        return $this->icon;
    }

    public function getIndicators(): array
    {
        if (! isset($this->original)) {
            return [];
        }

        $indicators = [];

        $attributes = array_values(array_filter([
            $this->name !== $this->original->name ? t('Name') : null,
            $this->handle !== $this->original->handle ? t('Handle') : null,
            $this->description !== $this->original->description ? t('Description') : null,
        ]));

        if (! empty($attributes)) {
            array_unshift($indicators, [
                'label' => t('This entry type’s {attributes} {totalAttributes, plural, =1{has} other{have}} been overridden.', [
                    'attributes' => mb_strtolower(collect($attributes)->sentence()),
                    'totalAttributes' => count($attributes),
                ]),
                'icon' => 'pencil',
                'iconColor' => 'teal',
            ]);
        }

        return $indicators;
    }

    public function getColor(): ?Color
    {
        return $this->color;
    }

    public function getActionMenuItems(): array
    {
        if (! $this->id) {
            return [];
        }

        if (! Auth::user()?->isAdmin()) {
            return [];
        }

        if (! Cms::config()->allowAdminChanges) {
            return [];
        }

        $editId = sprintf('action-edit-%s', mt_rand());
        $items = [[
            'id' => $editId,
            'icon' => 'gear',
            'label' => t('Entry type settings'),
        ]];

        $view = Craft::$app->getView();
        $view->registerJsWithVars(fn ($id, $params) => <<<JS
$('#' + $id).on('click', () => {
new Craft.CpScreenSlideout('entry-types/edit', {
params: $params,
})
});
JS, [
            $view->namespaceInputId($editId),
            ['entryTypeId' => $this->id],
        ]);

        return $items;
    }

    public function getRules(): array
    {
        $rules = [
            'id' => ['nullable', 'integer'],
            'color' => [
                'nullable',
                'string',
                Rule::in(array_merge(
                    array_map(fn (Color $color) => $color->value, Color::cases()),
                    ['__blank__']
                )),
            ],
            'fieldLayoutId' => ['nullable', 'integer', function (string $attribute, int $value, $fail) {
                $fieldLayout = Fields::assembleLayoutFromPost();
                $fieldLayout->reservedFieldHandles = [
                    'author',
                    'authorId',
                    'authorIds',
                    'authors',
                    'section',
                    'sectionId',
                    'type',
                    'postDate',
                ];

                if (! $fieldLayout->validate()) {
                    $fail(t('The field layout is invalid.'));
                }
            }],
            'name' => ['required', 'string', 'max:255'],
            'handle' => [
                'required',
                'string',
                'max:255',
                new HandleRule(['id', 'dateCreated', 'dateUpdated', 'uid', 'title']),
            ],
        ];

        if ($this->validateHandleUniqueness) {
            $rules['handle'][] = Rule::unique(Table::ENTRYTYPES, 'handle')->ignore($this->id)->withoutTrashed('dateDeleted');
        }

        return $rules;
    }

    /**
     * Use the handle as the string representation.
     */
    public function __toString(): string
    {
        return (string) $this->handle ?: self::class;
    }

    public function getHandle(): ?string
    {
        return $this->handle;
    }

    public function getFieldContext(): string
    {
        return 'global';
    }

    public function getEagerLoadingPrefix(): string
    {
        return $this->handle;
    }

    public function getCpEditUrl(): ?string
    {
        if (! $this->id || ! Auth::user()?->isAdmin()) {
            return null;
        }

        return UrlHelper::cpUrl("settings/entry-types/$this->id");
    }

    /**
     * Returns the entry type’s config.
     */
    public function getConfig(): array
    {
        $config = [
            'name' => $this->name,
            'handle' => $this->handle,
            'description' => $this->description ?: null,
            'icon' => $this->icon || $this->icon === '0' ? $this->icon : null,
            'color' => $this->color?->value,
            'uiLabelFormat' => $this->uiLabelFormat,
            'hasTitleField' => $this->hasTitleField,
            'titleTranslationMethod' => $this->titleTranslationMethod->value,
            'titleTranslationKeyFormat' => $this->titleTranslationKeyFormat ?: null,
            'titleFormat' => $this->titleFormat ?: null,
            'allowLineBreaksInTitles' => $this->allowLineBreaksInTitles,
            'showSlugField' => $this->showSlugField,
            'slugTranslationMethod' => $this->slugTranslationMethod->value,
            'slugTranslationKeyFormat' => $this->slugTranslationKeyFormat ?: null,
            'showStatusField' => $this->showStatusField,
        ];

        $fieldLayout = $this->getFieldLayout();

        if ($fieldLayoutConfig = $fieldLayout->getConfig()) {
            $config['fieldLayouts'] = [
                $fieldLayout->uid => $fieldLayoutConfig,
            ];
        }

        return $config;
    }

    /**
     * Returns the entry type’s usage info, possibly with name and handle override values.
     */
    public function getUsageConfig(): array
    {
        $config = ['uid' => $this->uid];

        if (! isset($this->original)) {
            return $config;
        }

        if ($this->name !== $this->original->name) {
            $config['name'] = $this->name;
        }

        if ($this->handle !== $this->original->handle) {
            $config['handle'] = $this->handle;
        }

        if ($this->description !== $this->original->description) {
            $config['description'] = $this->description;
        }

        if (isset($this->group)) {
            $config['group'] = $this->group;
        }

        return $config;
    }

    /**
     * Returns an array of sections and custom fields that make use of this entry type.
     *
     * @return array<Section|ElementContainerFieldInterface>
     */
    public function findUsages(): array
    {
        if (! isset($this->id)) {
            return [];
        }

        $usages = [];

        // Sections
        foreach (Sections::getAllSections() as $section) {
            foreach ($section->getEntryTypes() as $entryType) {
                if ($entryType->id === $this->id) {
                    $usages[] = $section;
                    break;
                }
            }
        }

        // Fields
        foreach (Fields::getNestedEntryFieldTypes() as $type) {
            /** @var ElementContainerFieldInterface[] $fields */
            $fields = Fields::getFieldsByType($type);
            foreach ($fields as $field) {
                foreach ($field->getFieldLayoutProviders() as $provider) {
                    if ($provider instanceof self && $provider->id === $this->id) {
                        $usages[] = $field;
                    }
                }
            }
        }

        return $usages;
    }
}
