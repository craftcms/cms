<?php

declare(strict_types=1);

namespace CraftCms\Cms\FieldLayout;

use Closure;
use CraftCms\Cms\Component\Component;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Field\ContentBlock;
use CraftCms\Cms\Field\Contracts\FieldInterface;
use CraftCms\Cms\Field\Contracts\PreviewableFieldInterface;
use CraftCms\Cms\Field\Exceptions\FieldNotFoundException;
use CraftCms\Cms\Field\Field;
use CraftCms\Cms\FieldLayout\Concerns\LegacyConstants;
use CraftCms\Cms\FieldLayout\Contracts\FieldLayoutProviderInterface;
use CraftCms\Cms\FieldLayout\Events\FieldLayoutCustomFieldsResolving;
use CraftCms\Cms\FieldLayout\Events\FieldLayoutUIElementsResolving;
use CraftCms\Cms\FieldLayout\LayoutElements\BaseField;
use CraftCms\Cms\FieldLayout\LayoutElements\BaseUiElement;
use CraftCms\Cms\FieldLayout\LayoutElements\CustomField;
use CraftCms\Cms\FieldLayout\LayoutElements\Heading;
use CraftCms\Cms\FieldLayout\LayoutElements\HorizontalRule;
use CraftCms\Cms\FieldLayout\LayoutElements\LineBreak;
use CraftCms\Cms\FieldLayout\LayoutElements\Markdown;
use CraftCms\Cms\FieldLayout\LayoutElements\Tip;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Facades\Fields;
use CraftCms\Cms\Support\Html;
use CraftCms\Cms\Support\Str;
use CraftCms\Cms\Validation\Rules\HandleRule;
use Generator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;
use InvalidArgumentException;
use Override;
use RuntimeException;

use function CraftCms\Cms\t;

/**
 * @phpstan-consistent-constructor
 *
 * @phpstan-type GeneratedField array{uid: string, name?: string, handle?: string|array{value: string, hasErrors: bool}, template?: string}
 * @phpstan-type GeneratedFieldConfig array{uid?: string, name?: string, handle?: string, template?: string}
 */
class FieldLayout extends Component
{
    use LegacyConstants;

    public ?int $id = null;

    public string $uid;

    /**
     * @var class-string<ElementInterface>|null The element type
     */
    public ?string $type = null;

    public ?FieldLayoutProviderInterface $provider = null;

    /**
     * @var string[]|null Reserved custom field handles
     */
    public ?array $reservedFieldHandles = null;

    /**
     * @var string|null The element key that provides thumbnails for this layout
     */
    public ?string $thumbFieldKey = null;

    /**
     * @var BaseField[][]
     *
     * @see getAvailableCustomFields()
     */
    private array $_availableCustomFields;

    /**
     * @var BaseField[]
     *
     * @see getAvailableNativeFields()
     */
    private array $_availableNativeFields;

    /**
     * @var FieldLayoutTab[]
     *
     * @see getTabs()
     * @see setTabs()
     */
    private array $_tabs;

    /**
     * @var FieldInterface[]
     *
     * @see getCustomFields()
     */
    private ?array $_customFields = null;

    /**
     * @var array<string,FieldInterface>|null
     *
     * @see getFieldByHandle()
     */
    private ?array $_indexedCustomFields = null;

    /**
     * @var list<GeneratedField>|null
     *
     * @see getGeneratedFields()
     * @see setGeneratedFields()
     */
    private ?array $_generatedFields = null;

    /**
     * @var list<string>
     *
     * @see getCardView()
     * @see setCardView()
     */
    private array $_cardView;

    /**
     * @var array<string, array{label: string, placeholder?: mixed}>
     *
     * @see cardAttributes()
     */
    private array $_cardAttributes;

    /**
     * @see getCardThumbAlignment()
     * @see setCardThumbAlignment()
     */
    private string $_cardThumbAlignment;

    /** @param array<string, mixed> $config */
    public function __construct(
        array $config = [],
    ) {
        parent::__construct($config);

        if (! isset($this->uid)) {
            $this->uid = Str::uuid()->toString();
        }

        if (! isset($this->_tabs)) {
            // go through setTabs() so any mandatory fields get added
            $this->setTabs([]);
        }

        if (! isset($this->_cardView)) {
            if ($this->type && class_exists($this->type)) {
                $this->setCardView($this->type::defaultCardAttributes());
            } else {
                $this->setCardView([]);
            }
        }

        if (! isset($this->_cardThumbAlignment)) {
            $this->setCardThumbAlignment();
        }
    }

    public static function defaultTabName(): string
    {
        return t('Content');
    }

    /**
     * @param  class-string<ElementInterface>  $type
     */
    public static function make(string $type): static
    {
        return new static(['type' => $type]);
    }

    /**
     * Creates or modifies a tab by name.
     *
     * Use {@see defaultTabName()} to target the tab created for mandatory fields.
     *
     * @param  Closure(FieldLayoutTab): mixed|null  $configure
     */
    public function tab(string $name, ?Closure $configure = null): static
    {
        $tab = array_find($this->getTabs(), fn (FieldLayoutTab $tab) => $tab->name === $name);

        if ($tab === null) {
            $tab = new FieldLayoutTab([
                'layout' => $this,
                'name' => $name,
                'elements' => [],
            ]);
            $this->setTabs([...$this->getTabs(), $tab]);
        }

        $configure?->__invoke($tab);

        return $this;
    }

    public function getTab(string $name): FieldLayoutTab
    {
        return array_find($this->getTabs(), fn (FieldLayoutTab $tab) => $tab->name === $name)
            ?? throw new InvalidArgumentException(sprintf('Unknown tab: %s', $name));
    }

    public function removeField(FieldInterface|string $field): static
    {
        if (is_string($field)) {
            $field = Fields::getFieldByHandle($field)
                ?? throw new InvalidArgumentException(sprintf('Unknown field handle: %s', $field));
        }

        foreach ($this->getTabs() as $tab) {
            $elements = array_filter(
                $tab->getElements(),
                fn (FieldLayoutElement $element) => ! $element instanceof CustomField || $element->getFieldUid() !== $field->uid,
            );

            if (count($elements) !== count($tab->getElements())) {
                $tab->setElements(array_values($elements));
            }
        }

        return $this;
    }

    public function removeTab(string $name): static
    {
        $tabs = array_values(array_filter(
            $this->getTabs(),
            fn (FieldLayoutTab $tab) => $tab->name !== $name,
        ));

        if (count($tabs) !== count($this->getTabs())) {
            $this->setTabs($tabs);
        }

        return $this;
    }

    /**
     * Creates a new field layout from the given config.
     */
    /** @param array<string, mixed> $config */
    public static function createFromConfig(array $config): self
    {
        $tabConfigs = Arr::pull($config, 'tabs');
        $layout = new self($config);

        if (is_array($tabConfigs)) {
            $layout->setTabs(array_values(array_map(
                fn (array $tabConfig) => FieldLayoutTab::createFromConfig(['layout' => $layout] + $tabConfig),
                $tabConfigs,
            )));
        } else {
            $layout->setTabs([]);
        }

        return $layout;
    }

    #[Override]
    public function getRules(): array
    {
        return [
            'id' => ['nullable', 'integer'],
            'customFields' => [function (string $attribute, array $value, Closure $fail) {
                $this->validateFields($value, $fail);
            }],
        ];
    }

    #[Override]
    public function validationData(): array
    {
        return array_merge(parent::validationData(), [
            'customFields' => $this->getCustomFields(),
        ]);
    }

    /** @param list<FieldInterface> $customFields */
    public function validateFields(array $customFields, Closure $fail): void
    {
        // Make sure no field handles are duplicated or using one of our reserved attribute names
        $handles = [];

        foreach ($customFields as $field) {
            if (isset($this->reservedFieldHandles) && in_array($field->handle, $this->reservedFieldHandles, true)) {
                $fail(t('“{handle}” is a reserved word.', [
                    'handle' => $field->handle,
                ]));
            } elseif (isset($handles[$field->handle])) {
                $fail(t('{attribute} "{value}" has already been taken.', [
                    'attribute' => t('Handle'),
                    'value' => $field->handle,
                ]));
            } else {
                $handles[$field->handle] = true;
            }
        }

        $generatedFields = $this->getGeneratedFields();

        if (empty($generatedFields)) {
            return;
        }

        $rule = new HandleRule([
            ...Field::RESERVED_HANDLES,
            ...(array) $this->reservedFieldHandles,
        ]);

        foreach ($generatedFields as &$field) {
            $field['name'] = trim($field['name'] ?? '');
            $field['handle'] = trim($field['handle'] ?? '');
            $field['template'] = trim($field['template'] ?? '');

            if ($field['handle'] === '') {
                continue;
            }

            $error = null;
            $validator = Validator::make(['handle' => $field['handle']], ['handle' => $rule]);
            $error = $validator->fails() ? $validator->errors()->first('handle') : null;

            if ($error === null && isset($handles[$field['handle']])) {
                $error = t('{attribute} "{value}" has already been taken.', [
                    'attribute' => t('Handle'),
                    'value' => $field['handle'],
                ]);
            }

            if ($error !== null) {
                $fail($error);
                $field['handle'] = [
                    'value' => $field['handle'],
                    'hasErrors' => true,
                ];
            } else {
                $handles[$field['handle']] = true;
            }
        }

        $this->setGeneratedFields($generatedFields);
    }

    /**
     * Returns the layout’s tabs.
     *
     * @return FieldLayoutTab[] The layout’s tabs.
     */
    public function getTabs(): array
    {
        if (! isset($this->_tabs)) {
            // go through setTabs() so any mandatory fields get added
            $this->setTabs([]);
        }

        return $this->_tabs;
    }

    /** @return array<string, string> */
    public function objectTemplateSuggestions(): array
    {
        return $this->resolveObjectTemplateSuggestions();
    }

    /**
     * @param  list<int>  $contentBlockStack
     * @return array<string, string>
     */
    private function resolveObjectTemplateSuggestions(
        string $prefix = '',
        array $contentBlockStack = [],
    ): array {
        $suggestions = [];

        foreach ($this->getTabs() as $tab) {
            foreach ($tab->getElements() as $layoutElement) {
                if (! $layoutElement instanceof CustomField) {
                    continue;
                }

                try {
                    $field = $layoutElement->getField();
                } catch (FieldNotFoundException) {
                    continue;
                }

                $attribute = $layoutElement->attribute();
                if ($attribute === '') {
                    continue;
                }

                $property = $prefix.$attribute;
                $suggestions[$property] = t($layoutElement->label() ?? $field->name, category: 'site');

                if (! $field instanceof ContentBlock) {
                    continue;
                }

                $fieldId = spl_object_id($field);
                if (in_array($fieldId, $contentBlockStack, true)) {
                    continue;
                }

                $suggestions = array_merge(
                    $suggestions,
                    $field->getFieldLayout()->resolveObjectTemplateSuggestions(
                        "$property.",
                        [...$contentBlockStack, $fieldId],
                    ),
                );
            }
        }

        return $suggestions;
    }

    /**
     * Sets the layout’s tabs.
     *
     * @param  array  $tabs  An array of the layout’s tabs, which can either be FieldLayoutTab objects or arrays defining the tab’s attributes.
     *
     * @phpstan-param array<array<string, mixed>|FieldLayoutTab> $tabs
     */
    public function setTabs(array $tabs): void
    {
        $this->_tabs = [];

        $index = 0;

        foreach ($tabs as $tab) {
            if (is_array($tab)) {
                // Set the layout before anything else
                $tab = ['layout' => $this] + $tab;
                $tab = new FieldLayoutTab($tab);
            } else {
                $tab->setLayout($this);
            }

            $tab->sortOrder = ++$index;
            $this->_tabs[] = $tab;
        }

        // Make sure that we aren't missing any mandatory fields
        $includedFields = [];
        $missingFields = [];

        foreach ($this->getElementsByType(BaseField::class) as $field) {
            try {
                /** @var BaseField $field */
                $includedFields[$field->attribute()] = true;
            } catch (FieldNotFoundException) {
                // move on
            }
        }

        foreach ($this->getAvailableNativeFields() as $field) {
            if ($field->mandatory()) {
                $attribute = $field->attribute();
                if (! isset($includedFields[$attribute])) {
                    $missingFields[] = $field;
                    $includedFields[$attribute] = true;
                }
            }
        }

        if (! empty($missingFields)) {
            $this->prependElements($missingFields);
        }

        // Clear caches
        $this->reset();
    }

    /** @return list<GeneratedField> */
    public function getGeneratedFields(): array
    {
        return $this->_generatedFields ?? [];
    }

    /** @return GeneratedField|null */
    public function getGeneratedFieldByUid(string $uid): ?array
    {
        return array_find($this->getGeneratedFields(), fn (array $field) => $field['uid'] === $uid);
    }

    /** @param list<GeneratedFieldConfig>|null $fields */
    public function setGeneratedFields(?array $fields): void
    {
        if (empty($fields)) {
            $this->_generatedFields = null;

            return;
        }

        foreach ($fields as &$field) {
            // make sure it has a UUID
            $field['uid'] ??= Str::uuid()->toString();
        }

        $this->_generatedFields = array_values($fields);
    }

    /** @param list<GeneratedFieldConfig>|null $fields */
    public function generatedFields(?array $fields): static
    {
        $this->setGeneratedFields($fields);

        return $this;
    }

    /** @return list<string> */
    public function getCardView(): array
    {
        if (! isset($this->_cardView)) {
            $this->setCardView([]);
        }

        return $this->_cardView;
    }

    /**
     * Sets the layout’s card view makeup.
     *
     * @param  list<string>|null  $items  An array of the layout’s card view items
     */
    public function setCardView(?array $items): void
    {
        $this->_cardView = array_values($items ?? []);

        $this->reset();
    }

    /** @param list<string>|null $items */
    public function cardView(?array $items): static
    {
        $this->setCardView($items);

        return $this;
    }

    public function thumbFieldKey(?string $key): static
    {
        $this->thumbFieldKey = $key;

        return $this;
    }

    /**
     * Returns the thumbnail alignment that should be used in element cards.
     *
     * @return string `start` or `end`
     */
    public function getCardThumbAlignment(): string
    {
        if (! isset($this->_cardThumbAlignment)) {
            $this->setCardThumbAlignment();
        }

        return $this->_cardThumbAlignment;
    }

    /**
     * Sets the thumbnail alignment that should be used in element cards.
     *
     * @param  string|null  $alignment  `start` or `end`
     */
    public function setCardThumbAlignment(?string $alignment = null): void
    {
        $validOptions = ['start', 'end'];

        if (! in_array($alignment, $validOptions)) {
            $alignment = null;
        }

        $this->_cardThumbAlignment = $alignment ?? 'end';
    }

    public function cardThumbAlignment(?string $alignment = null): static
    {
        if ($alignment !== null && ! in_array($alignment, ['start', 'end'], true)) {
            throw new InvalidArgumentException("Invalid card thumbnail alignment: $alignment");
        }

        $this->setCardThumbAlignment($alignment);

        return $this;
    }

    /**
     * Returns the available fields, grouped by field group name.
     *
     * @return BaseField[][]
     */
    public function getAvailableCustomFields(): array
    {
        if (isset($this->_availableCustomFields)) {
            return $this->_availableCustomFields;
        }

        $customFields = Fields::getAllFields()->map(fn (FieldInterface $field) => new CustomField($field, [
            'layout' => $this,
        ]))->all();

        $this->_availableCustomFields = [
            t('Custom Fields') => $customFields,
        ];

        event($event = new FieldLayoutCustomFieldsResolving($this, $this->_availableCustomFields));

        return $this->_availableCustomFields = $event->fields;
    }

    /**
     * Returns the available native fields.
     *
     * @return BaseField[]
     */
    public function getAvailableNativeFields(): array
    {
        if (isset($this->_availableNativeFields)) {
            return $this->_availableNativeFields;
        }

        $this->_availableNativeFields = [];

        // Instantiate them
        foreach (app(NativeFields::class)->apply($this, $this->_availableNativeFields) as $field) {
            $field = match (true) {
                is_string($field) => app()->make($field),
                is_array($field) => app()->make(Arr::pull($field, 'class'), ['config' => $field]),
                default => $field,
            };

            if (! $field instanceof BaseField) {
                throw new RuntimeException('Invalid standard field config');
            }

            $field->setLayout($this);
            $this->_availableNativeFields[] = $field;
        }

        return $this->_availableNativeFields;
    }

    /**
     * Returns the layout elements that are available to the field layout, grouped by the type name and (optionally) group name.
     *
     * @return FieldLayoutElement[]
     */
    public function getAvailableUiElements(): array
    {
        $elements = [
            new Heading,
            new Tip(['style' => Tip::STYLE_TIP]),
            new Tip(['style' => Tip::STYLE_WARNING]),
            new Markdown,
        ];

        event($event = new FieldLayoutUIElementsResolving($this, $elements));
        $elements = $event->elements;

        // HR and Line Break should always be last
        $elements[] = HorizontalRule::make();
        $elements[] = LineBreak::make();

        // Instantiate them
        foreach ($elements as &$element) {
            $element = match (true) {
                is_string($element) => app()->make($element),
                is_array($element) => app()->make(Arr::pull($element, 'class'), ['config' => $element]),
                default => $element,
            };

            if (! $element instanceof FieldLayoutElement) {
                throw new RuntimeException('Invalid UI element config');
            }
        }

        return $elements;
    }

    public function isFieldIncluded(callable|string $filter): bool
    {
        try {
            $this->getField($filter);

            return true;
        } catch (InvalidArgumentException) {
            return false;
        }
    }

    public function isUiElementIncluded(callable $filter): bool
    {
        $element = $this->_element(fn (FieldLayoutElement $layoutElement) => (
            $layoutElement instanceof BaseUiElement &&
            $filter($layoutElement)
        ));

        if (! $element) {
            return false;
        }

        return true;
    }

    /**
     * Returns a field that’s included in the layout by a callback or its attribute name.
     *
     * @throws InvalidArgumentException if the field isn’t included
     */
    public function getField(callable|string $filter): BaseField
    {
        if (is_string($filter)) {
            $attribute = $filter;
            $filter = fn (BaseField $field) => $field->attribute() === $attribute;
        }

        /** @var BaseField|null $field */
        $field = $this->_element(fn (FieldLayoutElement $layoutElement) => (
            $layoutElement instanceof BaseField &&
            $filter($layoutElement)
        ));

        if (! $field) {
            throw new InvalidArgumentException(isset($attribute) ? "Invalid field: $attribute" : 'Invalid field');
        }

        return $field;
    }

    /**
     * Returns all fields in the layout that match a given callback.
     *
     * @return BaseField[]
     *
     * @throws InvalidArgumentException if the field isn’t included
     */
    public function getFields(callable $filter): array
    {
        return iterator_to_array($this->_elements(fn (FieldLayoutElement $layoutElement) => (
            $layoutElement instanceof BaseField &&
            $filter($layoutElement)
        )));
    }

    /**
     * Returns the field layout’s config.
     */
    /**
     * @return array{tabs: list<array<string, mixed>>, generatedFields: list<GeneratedField>, cardView: list<string>, thumbFieldKey: string|null, cardThumbAlignment: string}|null
     */
    public function getConfig(): ?array
    {
        $tabConfigs = array_map(
            fn (FieldLayoutTab $tab) => $tab->getConfig(),
            $this->getTabs(),
        );

        $generatedFields = $this->getGeneratedFields();
        $cardViewConfig = $this->getCardView();
        $cardThumbAlignment = $this->getCardThumbAlignment();

        if (empty($generatedFields) && empty($tabConfigs) && empty($cardViewConfig)) {
            // no point bothering with the thumb alignment if we don't have the card view
            return null;
        }

        return [
            'tabs' => $tabConfigs,
            'generatedFields' => $generatedFields,
            'cardView' => $cardViewConfig,
            'thumbFieldKey' => $this->thumbFieldKey,
            'cardThumbAlignment' => $cardThumbAlignment,
        ];
    }

    public function resetUids(): void
    {
        $this->uid = Str::uuid()->toString();
        $cardViewReplacements = [];

        foreach ($this->getTabs() as $tab) {
            $tab->uid = Str::uuid()->toString();

            foreach ($tab->getElements() as $element) {
                $oldUid = $element->uid;
                $element->uid = Str::uuid()->toString();
                $cardViewReplacements["layoutElement:$oldUid"] = "layoutElement:$element->uid";
            }
        }

        // update the card view items
        // (look for `layoutElement:x` anywhere in the item, in case it also
        // includes a content block field UUID)
        $cardViewItems = [];
        foreach ($this->getCardView() as $item) {
            $cardViewItems[] = strtr($item, $cardViewReplacements);
        }

        $this->setCardView($cardViewItems);
    }

    public function getElementByUid(string $uid): ?FieldLayoutElement
    {
        $filter = fn (FieldLayoutElement $layoutElement) => $layoutElement->uid === $uid;

        return $this->_element($filter);
    }

    public function getElementByKey(string $key): ?FieldLayoutElement
    {
        if (str_starts_with($key, 'layoutElement:')) {
            $uid = Str::after($key, 'layoutElement:');

            return $this->getElementByUid($uid);
        }

        if (! str_starts_with($key, 'contentBlock:')) {
            return null;
        }

        $keyParts = explode('.', $key);
        $key = array_shift($keyParts);

        // get the Content Block field
        $uid = Str::after($key, 'contentBlock:');
        $layoutElement = $this->getElementByUid($uid);

        if (! $layoutElement instanceof CustomField) {
            return null;
        }

        try {
            $field = $layoutElement->getField();
        } catch (FieldNotFoundException) {
            return null;
        }

        if (! $field instanceof ContentBlock) {
            return null;
        }

        return $field->getFieldLayout()->getElementByKey(implode('.', $keyParts));
    }

    /**
     * Returns the layout elements of a given type.
     *
     * @return FieldLayoutElement[]
     */
    public function getAllElements(): array
    {
        return iterator_to_array($this->_elements());
    }

    /**
     * Returns the layout elements of a given type.
     *
     * @template T
     *
     * @param  class-string<T>  $class
     * @return T[]
     */
    public function getElementsByType(string $class): array
    {
        $filter = fn (FieldLayoutElement $layoutElement) => $layoutElement instanceof $class;

        return iterator_to_array($this->_elements($filter));
    }

    /**
     * Returns the visible layout elements of a given type, taking conditions into account.
     *
     * @template T
     *
     * @param  class-string<T>  $class
     * @return T[]
     */
    public function getVisibleElementsByType(string $class, ElementInterface $element): array
    {
        $filter = fn (FieldLayoutElement $layoutElement) => $layoutElement instanceof $class;

        return iterator_to_array($this->_elements($filter, $element));
    }

    /**
     * Returns the first layout element of a given type.
     *
     * @template T of FieldLayoutElement
     *
     * @param  class-string<T>  $class
     * @return FieldLayoutElement|null The layout element, or `null` if none were found
     */
    public function getFirstElementByType(string $class): ?FieldLayoutElement
    {
        $filter = fn (FieldLayoutElement $layoutElement) => $layoutElement instanceof $class;

        return $this->_element($filter);
    }

    /**
     * Returns the first visible layout element of a given type, taking conditions into account.
     *
     * @template T of FieldLayoutElement
     *
     * @param  class-string<T>  $class
     * @return FieldLayoutElement|null The layout element, or `null` if none were found
     */
    public function getFirstVisibleElementByType(string $class, ElementInterface $element): ?FieldLayoutElement
    {
        $filter = fn (FieldLayoutElement $layoutElement) => $layoutElement instanceof $class;

        return $this->_element($filter, $element);
    }

    /**
     * Returns the layout elements representing custom fields.
     *
     * @return CustomField[]
     */
    public function getCustomFieldElements(): array
    {
        return Collection::make($this->getElementsByType(CustomField::class))
            ->filter(function (CustomField $layoutElement) {
                try {
                    $layoutElement->getField();
                } catch (FieldNotFoundException) {
                    return false;
                }

                return true;
            })
            ->values()
            ->all();
    }

    /**
     * Returns the visible layout elements representing custom fields, taking conditions into account.
     *
     * @return CustomField[]
     */
    public function getVisibleCustomFieldElements(ElementInterface $element): array
    {
        return iterator_to_array($this->_elements(function (FieldLayoutElement $layoutElement) {
            if (! $layoutElement instanceof CustomField) {
                return false;
            }

            try {
                $layoutElement->getField();
            } catch (FieldNotFoundException) {
                return false;
            }

            return true;
        }, $element));
    }

    /**
     * Returns the editable layout elements representing custom fields, taking conditions into account.
     *
     * @return CustomField[]
     */
    public function getEditableCustomFieldElements(ElementInterface $element): array
    {
        return iterator_to_array($this->_elements(function (FieldLayoutElement $layoutElement) use ($element) {
            if (! $layoutElement instanceof CustomField) {
                return false;
            }

            if (! $layoutElement->editable($element)) {
                return false;
            }

            try {
                $layoutElement->getField();
            } catch (FieldNotFoundException) {
                return false;
            }

            return true;
        }, $element));
    }

    /**
     * Prepends elements to the first tab.
     *
     * @param  FieldLayoutElement[]  $elements
     */
    public function prependElements(array $elements): void
    {
        // Make sure there's at least one tab
        $tab = reset($this->_tabs);
        if (! $tab) {
            $this->_tabs[] = $tab = new FieldLayoutTab([
                'layout' => $this,
                'layoutId' => $this->id,
                'name' => static::defaultTabName(),
                'sortOrder' => 1,
                'elements' => [],
            ]);
        }

        $layoutElements = $tab->getElements();
        array_unshift($layoutElements, ...$elements);
        $tab->setElements($layoutElements);
    }

    /**
     * Returns the custom fields included in the layout.
     *
     * @return FieldInterface[]
     */
    public function getCustomFields(): array
    {
        return $this->_customFields ??= $this->_customFields();
    }

    /**
     * Returns the custom fields included in the layout, taking visibility conditions into account.
     *
     * @return FieldInterface[]
     */
    public function getVisibleCustomFields(ElementInterface $element): array
    {
        return $this->_customFields(element: $element);
    }

    /**
     * Returns the custom fields included in the layout, taking editability conditions into account.
     *
     * @return FieldInterface[]
     */
    public function getEditableCustomFields(ElementInterface $element): array
    {
        return $this->_customFields(
            fn (CustomField $layoutElement) => $layoutElement->editable($element),
            $element,
        );
    }

    public function hasThumbField(): bool
    {
        if (! isset($this->thumbFieldKey)) {
            return false;
        }

        $field = $this->getElementByKey($this->thumbFieldKey);

        return $field instanceof BaseField && $field->thumbable();
    }

    /**
     * Returns the card body HTML for a given card element key.
     *
     * @param  int  $size  The maximum width and height the thumbnail should have.
     */
    public function getThumbHtmlForElement(string $key, ElementInterface $element, int $size): ?string
    {
        return match (true) {
            str_starts_with($key, 'layoutElement:') => $this->thumbHtmlForLayoutElement($key, $element, $size),
            str_starts_with($key, 'contentBlock:') => $this->thumbHtmlForContentBlock($key, $element, $size),
            default => null,
        };
    }

    private function thumbHtmlForLayoutElement(string $key, ElementInterface $element, int $size): ?string
    {
        $layoutElement = $this->getElementByKey($key);

        if (! $layoutElement instanceof BaseField) {
            return null;
        }

        return $layoutElement->thumbHtml($element, $size);
    }

    private function thumbHtmlForContentBlock(string $key, ElementInterface $element, int $size): ?string
    {
        // the key will be in the format `contentBlock:X::[...]::layoutElement:X`
        $keyParts = explode('.', $key);
        $key = array_shift($keyParts);

        // get the Content Block field
        $uid = Str::after($key, 'contentBlock:');
        $layoutElement = $this->getElementByUid($uid);

        if (! $layoutElement instanceof CustomField) {
            return null;
        }

        try {
            $field = $layoutElement->getField();
        } catch (FieldNotFoundException) {
            return null;
        }

        if (! $field instanceof ContentBlock) {
            return null;
        }

        return $field->getFieldLayout()->getThumbHtmlForElement(
            implode('.', $keyParts),
            $element->getFieldValue($field->handle),
            $size,
        );
    }

    /**
     * Returns the fields and attributes that should be used in element card bodies in the correct order.
     *
     * @return array<string,string>
     */
    public function getCardBodyElements(?ElementInterface $element = null): array
    {
        $cardElements = [];

        foreach ($this->getCardView() as $key) {
            $html = $this->getCardBodyHtmlForElement($key, $element);

            if ($html) {
                $cardElements[$key] = $html;
            }
        }

        return $cardElements;
    }

    public function getCardBodyHtmlForElement(string $key, ?ElementInterface $element = null): ?string
    {
        return match (true) {
            str_starts_with($key, 'layoutElement:') => $this->cardHtmlForLayoutElement($key, $element),
            str_starts_with($key, 'contentBlock:') => $this->cardHtmlForContentBlock($key, $element),
            str_starts_with($key, 'generatedField:') => $this->cardHtmlForGeneratedField($key, $element),
            default => $this->cardHtmlForAttribute($key, $element),
        };
    }

    private function cardHtmlForLayoutElement(string $key, ?ElementInterface $element): ?string
    {
        $layoutElement = $this->getElementByKey($key);

        if (! $layoutElement instanceof BaseField) {
            return null;
        }

        if ($element) {
            if (! $layoutElement->showInForm($element)) {
                return null;
            }

            return $layoutElement->previewHtml($element);
        }

        if ($layoutElement instanceof CustomField) {
            try {
                $field = $layoutElement->getField();
            } catch (FieldNotFoundException) {
                return null;
            }

            if (! $field instanceof PreviewableFieldInterface) {
                return null;
            }

            return $field->previewPlaceholderHtml(null, null);
        }

        return $layoutElement->previewPlaceholderHtml(null, $element);
    }

    private function cardHtmlForContentBlock(string $key, ?ElementInterface $element): ?string
    {
        // the key will be in the format `contentBlock:X::[...]::layoutElement:X`
        $keyParts = explode('.', $key);
        $key = array_shift($keyParts);

        // get the Content Block field
        $uid = Str::after($key, 'contentBlock:');
        $layoutElement = $this->getElementByUid($uid);

        if (! $layoutElement instanceof CustomField) {
            return null;
        }

        try {
            $field = $layoutElement->getField();
        } catch (FieldNotFoundException) {
            return null;
        }

        if (! $field instanceof ContentBlock) {
            return null;
        }

        return $field->getFieldLayout()->getCardBodyHtmlForElement(
            implode('.', $keyParts),
            $element?->getFieldValue($field->handle),
        );
    }

    private function cardHtmlForGeneratedField(string $key, ?ElementInterface $element): ?string
    {
        $uid = Str::after($key, 'generatedField:');
        $field = $this->getGeneratedFieldByUid($uid);

        if (! $field) {
            return null;
        }

        if ($element) {
            $html = $element->getGeneratedFieldValues()[$uid] ?? null;

            if (! $html) {
                return null;
            }

            return Html::tag('div', $html, ['class' => 'no-truncate']);
        }

        return Html::encode($field['name'] ?? '');
    }

    private function cardHtmlForAttribute(string $key, ?ElementInterface $element): ?string
    {
        if ($element) {
            return $element->getAttributeHtml($key);
        }

        $attribute = $this->cardAttributes()[$key] ?? null;

        if (! $attribute) {
            return null;
        }

        $html = $this->type::attributePreviewHtml([
            ...$attribute,
            'value' => $key,
        ]);

        if (is_callable($html)) {
            return $html();
        }

        return $html;
    }

    /** @return array<string, array{label: string, placeholder?: mixed}> */
    private function cardAttributes(): array
    {
        return $this->_cardAttributes ??= $this->type::cardAttributes($this);
    }

    /**
     * @return FieldInterface[]
     */
    private function _customFields(?callable $filter = null, ?ElementInterface $element = null): array
    {
        return array_map(
            fn (CustomField $layoutElement) => $layoutElement->getField(),
            iterator_to_array($this->_elements(function (FieldLayoutElement $layoutElement) use ($filter) {
                if (
                    ! $layoutElement instanceof CustomField ||
                    ($filter && ! $filter($layoutElement))
                ) {
                    return false;
                }

                // make sure the field exists
                try {
                    $layoutElement->getField();
                } catch (FieldNotFoundException) {
                    return false;
                }

                return true;
            }, $element)),
        );
    }

    public function getFieldById(int $id): ?FieldInterface
    {
        return array_find($this->getCustomFields(), fn (FieldInterface $field) => $field->id === $id);

    }

    public function getFieldByUid(string $uid): ?FieldInterface
    {
        return array_find($this->getCustomFields(), fn (FieldInterface $field) => $field->uid === $uid);

    }

    public function getFieldByHandle(string $handle): ?FieldInterface
    {
        $this->_indexedCustomFields ??= Arr::keyBy($this->getCustomFields(), fn (FieldInterface $field) => $field->handle);

        return $this->_indexedCustomFields[$handle] ?? null;
    }

    private function _element(callable $filter, ?ElementInterface $element = null): ?FieldLayoutElement
    {
        return $this->_elements($filter, $element)->current();
    }

    private function _elements(?callable $filter = null, ?ElementInterface $element = null): Generator
    {
        foreach ($this->getTabs() as $tab) {
            if (! $element || ! isset($tab->uid) || $tab->showInForm($element)) {
                foreach ($tab->getElements() as $layoutElement) {
                    if (
                        (! $filter || $filter($layoutElement)) &&
                        (! $element || ! isset($layoutElement->uid) || $layoutElement->showInForm($element))
                    ) {
                        yield $layoutElement;
                    }
                }
            }
        }
    }

    /**
     * Resets the memoized custom fields.
     *
     * @internal
     */
    public function reset(): void
    {
        $this->_customFields = $this->_indexedCustomFields = null;
    }
}
