<?php

declare(strict_types=1);

namespace CraftCms\Cms\FieldLayout\LayoutElements;

use CraftCms\Cms\Component\Contracts\Iconic;
use CraftCms\Cms\Cp\FieldLayoutDesigner\CardDesigner;
use CraftCms\Cms\Element\Conditions\Contracts\ElementConditionInterface;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Field\ContentBlock;
use CraftCms\Cms\Field\Contracts\CrossSiteCopyableFieldInterface;
use CraftCms\Cms\Field\Contracts\FieldInterface;
use CraftCms\Cms\Field\Contracts\PreviewableFieldInterface;
use CraftCms\Cms\Field\Contracts\ThumbableFieldInterface;
use CraftCms\Cms\Field\Exceptions\FieldNotFoundException;
use CraftCms\Cms\Field\FieldContext;
use CraftCms\Cms\Field\MissingField;
use CraftCms\Cms\FieldLayout\FieldLayoutElementContext;
use CraftCms\Cms\Form\Contracts\Control;
use CraftCms\Cms\Form\Controls\FieldSelect;
use CraftCms\Cms\Form\Controls\Missing as MissingControl;
use CraftCms\Cms\Form\Controls\Text;
use CraftCms\Cms\Form\Enums\ControlMode;
use CraftCms\Cms\Form\FormContext;
use CraftCms\Cms\Form\Nodes\Field;
use CraftCms\Cms\Form\Nodes\Group;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Facades\Fields;
use CraftCms\Cms\Support\Facades\I18N;
use CraftCms\Cms\Support\Str;
use CraftCms\Cms\User\Conditions\UserCondition;
use CraftCms\Cms\User\Elements\User;
use InvalidArgumentException;
use Override;
use RuntimeException;
use Throwable;

use function CraftCms\Cms\currentUser;
use function CraftCms\Cms\currentUserElement;
use function CraftCms\Cms\t;

/**
 * CustomField represents a custom field that can be included in field layouts.
 *
 * @property FieldInterface $field The custom field this layout field is based on
 * @property string $fieldUid The UID of the field this layout field is based on
 * @property UserCondition|null $editCondition The user condition which determines who can edit this field
 *
 * @phpstan-consistent-constructor
 */
class CustomField extends BaseField
{
    private static UserCondition $defaultEditCondition;

    /**
     * @var ElementConditionInterface[]
     */
    private static array $defaultElementEditConditions = [];

    private static function defaultEditCondition(): UserCondition
    {
        return self::$defaultEditCondition ??= User::createCondition();
    }

    /**
     * @param  class-string<ElementInterface>  $elementType
     */
    private static function defaultElementEditCondition(string $elementType): ElementConditionInterface
    {
        return self::$defaultElementEditConditions[$elementType] ??= $elementType::createCondition();
    }

    /**
     * @var string|null The field handle override.
     */
    public ?string $handle = null;

    /**
     * @var string|null The previously-selected field’s UUID, if there was one
     */
    public ?string $oldFieldUid = null;

    private ?FieldInterface $_field = null;

    private ?FieldInterface $_sourceField = null;

    private ?string $_fieldUid = null;

    private ?string $_originalName = null;

    private ?string $_originalHandle = null;

    private ?string $_originalInstructions = null;

    /**
     * @var UserCondition|class-string<UserCondition>|array|null
     *
     * @phpstan-var UserCondition|class-string<UserCondition>|array{class:class-string<UserCondition>}|null
     *
     * @see getEditCondition()
     * @see setEditCondition()
     */
    private mixed $_editCondition = null;

    /**
     * @var ElementConditionInterface|class-string<ElementConditionInterface>|array|null
     *
     * @phpstan-var ElementConditionInterface|class-string<ElementConditionInterface>|array{class:class-string<ElementConditionInterface>}|null
     *
     * @see getElementEditCondition()
     * @see setElementEditCondition()
     */
    private mixed $_elementEditCondition = null;

    public function __construct(?FieldInterface $field = null, $config = [])
    {
        // ensure we set the field last, so it has access to other properties that need to be set first
        // see https://github.com/craftcms/cms/issues/15752
        $fieldUid = Arr::pull($config, 'fieldUid');
        if ($fieldUid) {
            $config['fieldUid'] = $fieldUid;
        }

        parent::__construct($config);

        if ($field) {
            $this->setField($field);
        }
    }

    public static function make(FieldInterface|string $field): static
    {
        if (is_string($field)) {
            $field = Fields::getFieldByHandle($field)
                ?? throw new InvalidArgumentException(sprintf('Unknown field handle: %s', $field));
        }

        return new static($field);
    }

    #[Override]
    public function label(?string $label = null): static|string|null
    {
        if (func_num_args() === 0) {
            return parent::label();
        }

        parent::label($label);

        if ($this->_field !== null) {
            $this->_field->name = $this->label ?? $this->_originalName;
        }

        return $this;
    }

    #[Override]
    public function instructions(?string $instructions): static
    {
        parent::instructions($instructions);

        if ($this->_field !== null) {
            $this->_field->instructions = $this->instructions ?? $this->_originalInstructions;
        }

        return $this;
    }

    public function handle(?string $handle): static
    {
        $this->handle = $handle;

        if ($this->_field !== null) {
            $this->_field->handle = $handle ?? $this->_originalHandle;
        }

        return $this;
    }

    public function editCondition(mixed $editCondition): static
    {
        $this->setEditCondition($editCondition);

        return $this;
    }

    public function elementEditCondition(mixed $elementEditCondition): static
    {
        $this->setElementEditCondition($elementEditCondition);

        return $this;
    }

    #[Override]
    public function isMultiInstance(): bool
    {
        try {
            $field = $this->getField();
        } catch (FieldNotFoundException) {
            return false;
        }

        return $field::isMultiInstance();
    }

    public function attribute(): string
    {
        if (isset($this->handle)) {
            return $this->handle;
        }

        try {
            $field = $this->getField();
        } catch (FieldNotFoundException) {
            return '';
        }

        return $field->handle;
    }

    #[Override]
    public function key(): string
    {
        try {
            $field = $this->getField();
        } catch (FieldNotFoundException) {
            $field = null;
        }

        $prefix = $field instanceof ContentBlock ? 'contentBlock' : 'layoutElement';
        $uid = $this->uid ?? '{uid}';

        return "$prefix:$uid";
    }

    #[Override]
    public function showAttribute(): bool
    {
        return true;
    }

    #[Override]
    protected function value(?ElementInterface $element = null): mixed
    {
        if ($element === null) {
            return null;
        }

        try {
            $field = $this->getField();
        } catch (FieldNotFoundException) {
            return null;
        }

        return $element->getFieldValue($field->handle);
    }

    #[Override]
    public function requirable(): bool
    {
        try {
            $field = $this->getField();
        } catch (FieldNotFoundException) {
            return false;
        }

        return $field::isRequirable();
    }

    #[Override]
    public function thumbable(): bool
    {
        try {
            $field = $this->getField();
        } catch (FieldNotFoundException) {
            return false;
        }

        return $field instanceof ThumbableFieldInterface;
    }

    #[Override]
    public function previewable(): bool
    {
        try {
            $field = $this->getField();
        } catch (FieldNotFoundException) {
            return false;
        }

        return $field instanceof PreviewableFieldInterface;
    }

    /** @return list<array{label: string, value: string}>|null */
    #[Override]
    public function getPreviewOptions(): ?array
    {
        try {
            $field = $this->getField();
        } catch (FieldNotFoundException) {
            return null;
        }

        if ($field instanceof ContentBlock) {
            $options = [];
            $label = $this->selectorLabel();
            $nestedOptions = app(CardDesigner::class)->previewOptions($field->getFieldLayout(), false);
            foreach ($nestedOptions as $key => $option) {
                $options[] = [
                    'label' => "$label → {$option['label']}",
                    'value' => "contentBlock:{uid}.$key",
                ];
            }

            return $options;
        }

        if (! $this->previewable()) {
            return null;
        }

        return [
            [
                'label' => $this->selectorLabel() ?? $this->attribute(),
                'value' => 'layoutElement:{uid}',
            ],
        ];
    }

    /** @return list<array{label: string, value: string}>|null */
    #[Override]
    public function getThumbOptions(): ?array
    {
        try {
            $field = $this->getField();
        } catch (FieldNotFoundException) {
            return null;
        }

        if ($field instanceof ContentBlock) {
            $options = [];
            $label = $this->selectorLabel();
            $nestedOptions = app(CardDesigner::class)->thumbOptions($field->getFieldLayout());
            foreach ($nestedOptions as $key => $option) {
                $options[] = [
                    'label' => "$label → {$option['label']}",
                    'value' => "contentBlock:{uid}.$key",
                ];
            }

            return $options;
        }

        if (! $this->thumbable()) {
            return null;
        }

        return [
            [
                'label' => $this->selectorLabel() ?? $this->attribute(),
                'value' => 'layoutElement:{uid}',
            ],
        ];
    }

    public function thumbHtml(ElementInterface $element, int $size): ?string
    {
        try {
            $field = $this->getField();
        } catch (FieldNotFoundException) {
            return null;
        }

        if (! $field instanceof ThumbableFieldInterface) {
            return null;
        }

        return $field->getThumbHtml($element->getFieldValue($field->handle), $element, $size);
    }

    #[Override]
    public function previewHtml(ElementInterface $element): string
    {
        try {
            $field = $this->getField();
        } catch (FieldNotFoundException) {
            return '';
        }

        if (! $field instanceof PreviewableFieldInterface) {
            return '';
        }

        return $field->getPreviewHtml($element->getFieldValue($field->handle), $element);
    }

    #[Override]
    public function keywords(): array
    {
        $fieldTypeKeyword = [];

        try {
            $field = $this->getField();
            // include field type display name in the field layout designer's keywords
            $fieldTypeKeyword = [$field->displayName()];
        } catch (Throwable) {
            // fail silently
        }

        return array_filter(
            array_merge(parent::keywords(), $fieldTypeKeyword)
        );
    }

    /**
     * Returns the custom field this layout field is based on.
     *
     * @throws RuntimeException
     * @throws FieldNotFoundException
     */
    /**
     * The layout author's warning, plus anything the field itself needs to
     * flag — a misconfigured volume, say, which the author can't see from the
     * layout. Both are shown when both apply.
     */
    #[Override]
    protected function warningText(?ElementInterface $element = null, bool $static = false): ?string
    {
        $warnings = array_filter([
            parent::warningText($element, $static),
            $this->getField()->formWarning($element),
        ]);

        return $warnings !== [] ? implode(' ', $warnings) : null;
    }

    public function getField(): FieldInterface
    {
        if (isset($this->_field)) {
            return $this->_field;
        }

        if (! isset($this->_fieldUid)) {
            throw new RuntimeException('No field UUID set.');
        }

        if (($field = Fields::getFieldByUid($this->_fieldUid)) === null) {
            throw new FieldNotFoundException($this->_fieldUid);
        }

        $this->setField($field);

        return $this->_field;
    }

    /**
     * Sets the custom field this layout field is based on.
     */
    public function setField(FieldInterface $field): void
    {
        $this->_sourceField = $field;
        $this->_field = clone $field;
        $this->_fieldUid = $this->_field->uid;
        $this->_field->layoutElement = $this;
        $this->_originalName = $this->_field->name;
        $this->_originalHandle = $this->_field->handle;
        $this->_originalInstructions = $this->_field->instructions;

        // Set the instance overrides
        $this->_field->name = $this->label ?? $this->_field->name;
        $this->_field->handle = $this->handle ?? $this->_field->handle;
        $this->_field->instructions = $this->instructions ?? $this->_field->instructions;
    }

    /**
     * Returns the UID of the field this layout field is based on.
     */
    public function getFieldUid(): string
    {
        return $this->_fieldUid;
    }

    /**
     * Sets the UID of the field this layout field is based on.
     */
    public function setFieldUid(string $uid): void
    {
        $this->_fieldUid = $uid;
        $this->_field = null;
        $this->_sourceField = null;
    }

    /**
     * Sets the ID of the field this layout field is based on.
     */
    public function setFieldId(int $id): void
    {
        $field = Fields::getFieldById($id);

        if (! $field) {
            throw new FieldNotFoundException($id);
        }

        $this->setField($field);
    }

    /**
     * Returns the field’s original handle.
     */
    public function getOriginalHandle(): string
    {
        return $this->_originalHandle;
    }

    #[Override]
    public function hasConditions(): bool
    {
        if (parent::hasConditions()) {
            return true;
        }

        if ($this->getEditCondition()) {
            return true;
        }

        return (bool) $this->getElementEditCondition();
    }

    /**
     * Returns the edit condition for this layout element.
     */
    public function getEditCondition(): ?UserCondition
    {
        if (isset($this->_editCondition) && ! $this->_editCondition instanceof UserCondition) {
            $this->_editCondition = $this->normalizeCondition($this->_editCondition);
        }

        return $this->_editCondition;
    }

    /**
     * Sets the edit condition for this layout element.
     *
     * @param  UserCondition|class-string<UserCondition>|array{class:class-string<UserCondition>}|null  $editCondition
     */
    public function setEditCondition(mixed $editCondition): void
    {
        $this->_editCondition = $editCondition;
    }

    /**
     * Returns the element edit condition for this layout element.
     */
    public function getElementEditCondition(): ?ElementConditionInterface
    {
        if (! isset($this->_elementEditCondition)) {
            return null;
        }

        if ($this->_elementEditCondition instanceof ElementConditionInterface) {
            return $this->_elementEditCondition;
        }

        if (is_string($this->_elementEditCondition)) {
            $this->_elementEditCondition = ['class' => $this->_elementEditCondition];
        }

        $this->_elementEditCondition = array_merge(
            ['fieldLayouts' => [$this->getLayout()]],
            $this->_elementEditCondition,
        );

        return $this->_elementEditCondition = $this->normalizeCondition($this->_elementEditCondition);
    }

    /**
     * Sets the element edit condition for this layout element.
     *
     * @param  ElementConditionInterface|class-string<ElementConditionInterface>|array{class:class-string<ElementConditionInterface>}|null  $elementEditCondition
     */
    public function setElementEditCondition(mixed $elementEditCondition): void
    {
        $this->_elementEditCondition = $elementEditCondition;
    }

    #[Override]
    public function fields(): array
    {
        return [
            ...parent::fields(),
            'fieldUid' => 'fieldUid',
            ...($this->oldFieldUid !== null ? ['oldFieldUid' => 'oldFieldUid'] : []),
            'editCondition' => fn () => $this->getEditCondition()?->getConfig(),
            'elementEditCondition' => fn () => $this->getElementEditCondition()?->getConfig(),
        ];
    }

    /**
     * @return array{class: string, data: array{attribute: string, mandatory: bool, requirable: bool, thumbable: bool, preview-options: list<array{label: string, value: string}>|null, thumb-options: list<array{label: string, value: string}>|null, id?: int}}
     */
    #[Override]
    protected function selectorAttributes(): array
    {
        $attributes = parent::selectorAttributes();

        try {
            $field = $this->getField();
        } catch (FieldNotFoundException) {
            return $attributes;
        }

        return Arr::merge($attributes, [
            'data' => [
                'id' => $field->id,
            ],
        ]);
    }

    #[Override]
    protected function settingsNodes(FormContext $context): array
    {
        // Make sure setField() has had a chance to set the default values
        $field = $this->getField();
        $originalField = Fields::getFieldByUid($field->uid);

        return [
            Group::make('custom-field-settings', array_values(array_filter([
                $originalField === null ? null : Field::make(t('Field'), FieldSelect::make('fieldId')
                    ->limit(1)
                    ->value($originalField->id)
                    ->reactive())
                    ->warning(t('Changing this may result in data loss.')),
                $this->labelSettingsNode($context),
                Field::make(t('Handle'), Text::make('handle')
                    ->monospace()
                    ->maxLength(64)
                    ->value($this->handle)
                    ->placeholder($this->_originalHandle))
                    ->required(),
                ...$this->instructionsSettingsNodes($context),
                ...$this->noticeSettingsNodes($context),
            ]))),
        ];
    }

    /** @return array{class?: list<string>, id?: string, data: array{base-input-name: string, error-key: string, type?: class-string<FieldInterface>}} */
    #[Override]
    protected function containerAttributes(?ElementInterface $element = null, bool $static = false): array
    {
        $attributes = parent::containerAttributes($element, $static);

        try {
            $field = $this->getField();
        } catch (FieldNotFoundException) {
            return $attributes;
        }

        return Arr::merge($attributes, [
            'id' => "{$field->handle}-field",
            'data' => [
                'type' => $field::class,
            ],
        ]);
    }

    protected function defaultLabel(?ElementInterface $element = null, bool $static = false): ?string
    {
        if ($this->_originalName !== '' && $this->_originalName !== null && $this->_originalName !== '__blank__') {
            return t($this->_originalName, category: 'site');
        }

        return null;
    }

    #[Override]
    protected function showLabel(): bool
    {
        // Does the field have a custom label?
        if (isset($this->label) && $this->label !== '') {
            return parent::showLabel();
        }

        try {
            $field = $this->getField();
        } catch (FieldNotFoundException) {
            return false;
        }

        return $field->name !== '__blank__';
    }

    protected function selectorIcon(): ?string
    {
        try {
            $field = $this->getField();
        } catch (FieldNotFoundException) {
            return null;
        }

        if ($field instanceof Iconic) {
            return $field->getIcon();
        }

        return $field::icon();
    }

    /** @return list<array{label: string, icon: string, iconColor: string}> */
    #[Override]
    protected function selectorIndicators(): array
    {
        $indicators = parent::selectorIndicators();

        if (isset($this->label) || isset($this->instructions) || isset($this->handle)) {
            $attributes = array_values(array_filter([
                isset($this->label) ? t('Name') : null,
                isset($this->instructions) ? t('Instructions') : null,
                isset($this->handle) ? t('Handle') : null,
            ]));
            array_unshift($indicators, [
                'label' => t('This field’s {attributes} {totalAttributes, plural, =1{has} other{have}} been overridden.', [
                    'attributes' => mb_strtolower(collect($attributes)->sentence()),
                    'totalAttributes' => count($attributes),
                ]),
                'icon' => 'pencil',
                'iconColor' => 'teal',
            ]);
        }

        return $indicators;
    }

    #[Override]
    protected function showStatus(): bool
    {
        try {
            $field = $this->getField();
        } catch (FieldNotFoundException) {
            return false;
        }

        return $field->showStatus();
    }

    #[Override]
    protected function statusClass(?ElementInterface $element = null, bool $static = false): ?string
    {
        if ($element === null) {
            return null;
        }

        try {
            $field = $this->getField();
        } catch (FieldNotFoundException) {
            return null;
        }

        $status = $field->getStatus($element);

        return $status ? Str::toString($status[0]) : null;
    }

    #[Override]
    protected function statusLabel(?ElementInterface $element = null, bool $static = false): ?string
    {
        if ($element === null) {
            return null;
        }

        try {
            $field = $this->getField();
        } catch (FieldNotFoundException) {
            return null;
        }

        $status = $field->getStatus($element);

        return $status ? $status[1] : null;
    }

    protected function defaultInstructions(?ElementInterface $element = null, bool $static = false): ?string
    {
        return $this->_originalInstructions ? t($this->_originalInstructions, category: 'site') : null;
    }

    #[Override]
    protected function conditionalSettingsNodes(FormContext $context): array
    {
        $elementType = $this->elementType ?? $this->getLayout()?->type;

        return [
            ...parent::conditionalSettingsNodes($context),
            $this->conditionGroupNode(
                'editability-conditions',
                t('Editability Conditions'),
                'editCondition',
                t('Only make editable for users who match the following rules:'),
                $this->getEditCondition(),
                'elementEditCondition',
                'Only make editable when editing {type} that match the following rules:',
                $this->getElementEditCondition(),
                self::defaultEditCondition(),
                $elementType && is_subclass_of($elementType, ElementInterface::class)
                    ? self::defaultElementEditCondition($elementType)::class
                    : null,
            ),
        ];
    }

    /**
     * Returns whether the field can be edited by the current user.
     */
    public function editable(?ElementInterface $element): bool
    {
        $editCondition = $this->getEditCondition();

        if ($editCondition) {
            $currentUser = currentUserElement();
            if (! $currentUser || ! $editCondition->matchElement($currentUser)) {
                return false;
            }
        }

        $elementEditCondition = $this->getElementEditCondition();

        if ($elementEditCondition && $element && ! $elementEditCondition->matchElement($element)) {
            return false;
        }

        return true;
    }

    #[Override]
    public function formMode(?ElementInterface $element): ControlMode
    {
        return $this->editable($element) ? ControlMode::Editable : ControlMode::ReadOnly;
    }

    #[Override]
    protected function formControl(FieldLayoutElementContext $context): ?Control
    {
        try {
            $this->getField();
        } catch (FieldNotFoundException) {
            return null;
        }

        $field = $this->_sourceField;

        if ($field instanceof MissingField) {
            return MissingControl::make(['fields', $this->attribute()])
                ->provider($field->expectedType)
                ->mode(ControlMode::Disabled)
                ->value($this->value($context->element));
        }

        return $field->formControl(new FieldContext(
            ['fields', $this->attribute()],
            $this->value($context->element),
            $context->element,
            $context->form,
            $context->mode,
        ));
    }

    #[Override]
    protected function useFieldset(): bool
    {
        try {
            $field = $this->getField();
        } catch (FieldNotFoundException) {
            return false;
        }

        return $field->useFieldset();
    }

    #[Override]
    protected function id(): string
    {
        try {
            $field = $this->getField();
        } catch (FieldNotFoundException) {
            return '';
        }

        return $field->getInputId();
    }

    #[Override]
    protected function labelId(): string
    {
        try {
            $field = $this->getField();
        } catch (FieldNotFoundException) {
            return '';
        }

        return $field->getLabelId();
    }

    #[Override]
    protected function orientation(?ElementInterface $element = null, bool $static = false): string
    {
        try {
            $field = $this->getField();
        } catch (FieldNotFoundException) {
            return I18N::getLocale()->getOrientation();
        }

        return $field->getOrientation($element);
    }

    #[Override]
    protected function translatable(?ElementInterface $element = null, bool $static = false): bool
    {
        try {
            $field = $this->getField();
        } catch (FieldNotFoundException) {
            return false;
        }

        return $field->getIsTranslatable($element);
    }

    protected function translationDescription(?ElementInterface $element = null, bool $static = false): ?string
    {
        try {
            $field = $this->getField();
        } catch (FieldNotFoundException) {
            return null;
        }

        return $field->getTranslationDescription($element);
    }

    #[Override]
    public function isCrossSiteCopyable(ElementInterface $element): bool
    {
        try {
            $field = $this->getField();
        } catch (FieldNotFoundException) {
            return false;
        }

        return $field instanceof CrossSiteCopyableFieldInterface && $field->getIsTranslatable($element);
    }

    /** @return list<array<string, mixed>> */
    #[Override]
    protected function actionMenuItemsForContext(FieldLayoutElementContext $context): array
    {
        try {
            $field = $this->getField();
        } catch (FieldNotFoundException) {
            $field = null;
        }

        return [
            ...($field?->getFieldLayoutActionMenuItems($context) ?? []),
            ...parent::actionMenuItemsForContext($context),
        ];
    }

    /** @return list<array<string, mixed>> */
    #[Override]
    protected function actionMenuItems(?ElementInterface $element = null, bool $static = false): array
    {
        $items = parent::actionMenuItems($element, $static);

        $user = currentUser();
        if ($user?->isAdmin() && ! $user->getPreference('showFieldHandles')) {
            $items[] = $this->copyAttributeAction([
                'label' => t('Copy field handle'),
                'promptLabel' => t('Field Handle'),
            ]);
        }

        return $items;
    }
}
