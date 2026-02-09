<?php

declare(strict_types=1);

namespace CraftCms\Cms\FieldLayout;

use Closure;
use Craft;
use craft\base\ElementInterface;
use craft\base\FieldLayoutElement;
use CraftCms\Cms\Component\Concerns\ConfigConstructor;
use CraftCms\Cms\Field\ContentBlock;
use CraftCms\Cms\Field\Contracts\FieldInterface;
use CraftCms\Cms\Field\Contracts\PreviewableFieldInterface;
use CraftCms\Cms\Field\Exceptions\FieldNotFoundException;
use CraftCms\Cms\Field\Field;
use CraftCms\Cms\Field\Fields;
use CraftCms\Cms\FieldLayout\Contracts\FieldLayoutProviderInterface;
use CraftCms\Cms\FieldLayout\Data\FieldLayoutForm;
use CraftCms\Cms\FieldLayout\Data\FieldLayoutFormElement;
use CraftCms\Cms\FieldLayout\Data\FieldLayoutFormTab;
use CraftCms\Cms\FieldLayout\Events\CreateFieldLayoutForm;
use CraftCms\Cms\FieldLayout\Events\DefineCustomFields;
use CraftCms\Cms\FieldLayout\Events\DefineNativeFields;
use CraftCms\Cms\FieldLayout\Events\DefineUIElements;
use CraftCms\Cms\FieldLayout\LayoutElements\BaseField;
use CraftCms\Cms\FieldLayout\LayoutElements\BaseUiElement;
use CraftCms\Cms\FieldLayout\LayoutElements\CustomField;
use CraftCms\Cms\FieldLayout\LayoutElements\Heading;
use CraftCms\Cms\FieldLayout\LayoutElements\HorizontalRule;
use CraftCms\Cms\FieldLayout\LayoutElements\LineBreak;
use CraftCms\Cms\FieldLayout\LayoutElements\Markdown;
use CraftCms\Cms\FieldLayout\LayoutElements\Template;
use CraftCms\Cms\FieldLayout\LayoutElements\Tip;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Html;
use CraftCms\Cms\Support\Str;
use CraftCms\Cms\Validation\Concerns\Validates;
use CraftCms\Cms\Validation\Contracts\Validatable;
use CraftCms\Cms\Validation\Rules\HandleRule;
use Generator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;
use yii\base\InvalidArgumentException;
use yii\base\InvalidConfigException;

use function CraftCms\Cms\t;

class FieldLayout implements Validatable
{
    use ConfigConstructor {
        __construct as private __configConstruct;
    }
    use Validates {
        getAttributes as traitGetAttributes;
    }

    /**
     * Creates a new field layout from the given config.
     */
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

    /**
     * @var int|null ID
     */
    public ?int $id = null;

    /**
     * @var class-string<ElementInterface>|null The element type
     */
    public ?string $type = null;

    /**
     * @var string UID
     */
    public string $uid;

    /**
     * @var FieldLayoutProviderInterface|null The field layout’s provider.
     */
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
     * @see getGeneratedFields()
     * @see setGeneratedFields()
     */
    private ?array $_generatedFields = null;

    /**
     * @see getCardView()
     * @see setCardView()
     */
    private array $_cardView;

    /**
     * @see cardAttributes()
     */
    private array $_cardAttributes;

    /**
     * @see getCardThumbAlignment()
     * @see setCardThumbAlignment()
     */
    private string $_cardThumbAlignment;

    public function __construct(
        array $config = [],
    ) {
        $this->__configConstruct($config);

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

    public function getRules(): array
    {
        return [
            'id' => ['integer'],
            'customFields' => [function (string $attribute, array $value, Closure $fail) {
                $this->validateFields($value, $fail);
            }],
        ];
    }

    public function getAttributes(): array
    {
        return array_merge($this->traitGetAttributes(), [
            'customFields' => $this->getCustomFields(),
        ]);
    }

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

    /**
     * Sets the layout’s tabs.
     *
     * @param  array  $tabs  An array of the layout’s tabs, which can either be FieldLayoutTab objects or arrays defining the tab’s attributes.
     *
     * @phpstan-param array<array|FieldLayoutTab> $tabs
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

    public function getGeneratedFields(): array
    {
        return $this->_generatedFields ?? [];
    }

    public function getGeneratedFieldByUid(string $uid): ?array
    {
        return array_find($this->getGeneratedFields(), fn (array $field) => $field['uid'] === $uid);
    }

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
     * @param  array|null  $items  An array of the layout’s card view items
     */
    public function setCardView(?array $items): void
    {
        $this->_cardView = array_values($items ?? []);

        $this->reset();
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

        $customFields = [];

        foreach (app(Fields::class)->getAllFields() as $field) {
            $customFields[] = Craft::createObject([
                'class' => CustomField::class,
                'layout' => $this,
            ], [$field]);
        }

        $this->_availableCustomFields = [
            t('Custom Fields') => $customFields,
        ];

        event($event = new DefineCustomFields($this, $this->_availableCustomFields));

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

        event($event = new DefineNativeFields($this, $this->_availableNativeFields));

        // Instantiate them
        foreach ($event->fields as $field) {
            if (is_string($field) || is_array($field)) {
                $field = Craft::createObject($field);
            }

            if (! $field instanceof BaseField) {
                throw new InvalidConfigException('Invalid standard field config');
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
            new Template,
        ];

        event($event = new DefineUiElements($this, $elements));
        $elements = $event->elements;

        // HR and Line Break should always be last
        $elements[] = new HorizontalRule;
        $elements[] = new LineBreak;

        // Instantiate them
        foreach ($elements as &$element) {
            if (is_string($element) || is_array($element)) {
                $element = Craft::createObject($element);
            }

            if (! $element instanceof FieldLayoutElement) {
                throw new InvalidConfigException('Invalid UI element config');
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
                'name' => t('Content'),
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
            return $element->getGeneratedFieldValues()[$uid] ?? null;
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

    /**
     * Creates a new [[FieldLayoutForm]] object for the given element.
     *
     * The `$config` array can contain the following keys:
     *
     * - `tabIdPrefix` – prefix that should be applied to the tab content containers’ `id` attributes
     * - `namespace` – Namespace that should be applied to the tab contents
     * - `registerDeltas` – Whether delta name registration should be enabled/disabled for the form (by default its state will be left alone)
     * - `visibleElements` – Lists of already-visible layout elements from [[FieldLayoutForm::getVisibleElements()]]
     *
     * @param  ElementInterface|null  $element  The element the form is being rendered for
     * @param  bool  $static  Whether the form should be static (non-interactive)
     * @param  array  $config  The [[FieldLayoutForm]] config
     */
    public function createForm(?ElementInterface $element = null, bool $static = false, array $config = []): FieldLayoutForm
    {
        $view = Craft::$app->getView();

        // Calling this with an existing namespace isn’t fully supported,
        // since the tab anchors’ `href` attributes won’t end up getting set properly
        $namespace = Arr::pull($config, 'namespace');

        // Register delta names?
        $registerDeltas = Arr::pull($config, 'registerDeltas');
        $changeDeltaRegistration = $registerDeltas !== null;
        if ($changeDeltaRegistration) {
            $view = Craft::$app->getView();
            $isDeltaRegistrationActive = $view->getIsDeltaRegistrationActive();
            $view->setIsDeltaRegistrationActive($registerDeltas);
        }

        // Any already-included layout elements?
        $visibleElements = Arr::pull($config, 'visibleElements');
        $staticElements = Arr::pull($config, 'staticElements');

        $form = FieldLayoutForm::from($config);
        $tabs = $this->getTabs();

        event($event = new CreateFieldLayoutForm(
            fieldLayout: $this,
            form: $form,
            element: $element,
            static: $static,
            tabs: $tabs,
        ));

        $static = $event->static;

        foreach ($event->tabs as $tab) {
            $layoutElements = [];
            $showTab = ! isset($tab->uid) || $tab->showInForm($element);
            $hasVisibleFields = false;

            foreach ($tab->getElements() as $layoutElement) {
                // Only tabs + elements that were saved with UUIDs can be conditional
                $isConditional = isset($tab->uid, $layoutElement->uid);

                if ($showTab && (! $isConditional || $layoutElement->showInForm($element))) {
                    if ($layoutElement instanceof CustomField) {
                        $isStatic = $static || ! $layoutElement->editable($element);
                    } else {
                        $isStatic = $static;
                    }

                    // If it was already included and we just need the missing elements, only keep track that it’s still included
                    if (
                        ! $layoutElement->alwaysRefresh() &&
                        $visibleElements !== null &&
                        (! $isConditional || (
                            (isset($visibleElements[$tab->uid]) && in_array($layoutElement->uid, $visibleElements[$tab->uid])) &&
                            ($staticElements === null || $isStatic === in_array($layoutElement->uid, $staticElements[$tab->uid] ?? []))
                        ))
                    ) {
                        $layoutElements[] = new FieldLayoutFormElement($layoutElement, $isConditional, true, $isStatic);
                        $hasVisibleFields = true;
                    } else {
                        $html = $view->namespaceInputs(fn () => $layoutElement->formHtml($element, $isStatic) ?? '', $namespace);

                        if ($html) {
                            $errorKey = null;
                            // if error key prefix was set on the FieldLayoutForm - use it
                            if ($form->errorKeyPrefix) {
                                $tagAttributes = Html::parseTagAttributes($html);
                                // if we already have an error-key for this field, prefix it
                                if (isset($tagAttributes['data']['error-key'])) {
                                    $errorKey = $form->errorKeyPrefix.'.'.$tagAttributes['data']['error-key'];
                                } elseif ($layoutElement instanceof BaseField) {
                                    // otherwise let's construct it
                                    $errorKey = $form->errorKeyPrefix.'.'.($layoutElement->name ?? $layoutElement->attribute());
                                }
                            }

                            $html = Html::modifyTagAttributes($html, [
                                'data' => [
                                    'layout-element' => $isConditional ? $layoutElement->uid : true,
                                    'error-key' => $errorKey,
                                    'static' => $isStatic,
                                ],
                            ]);

                            $layoutElements[] = new FieldLayoutFormElement($layoutElement, $isConditional, $html, $isStatic);
                            $hasVisibleFields = true;
                        } else {
                            $layoutElements[] = new FieldLayoutFormElement($layoutElement, $isConditional, false, false);
                        }
                    }
                } else {
                    $layoutElements[] = new FieldLayoutFormElement($layoutElement, $isConditional, false, false);
                }
            }

            if ($hasVisibleFields) {
                $form->tabs[] = FieldLayoutFormTab::from([
                    'layoutTab' => $tab,
                    'hasErrors' => $element && $tab->elementHasErrors($element),
                    'elements' => $layoutElements,
                ]);
            }
        }

        if ($changeDeltaRegistration) {
            $view->setIsDeltaRegistrationActive($isDeltaRegistrationActive);
        }

        return $form;
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
