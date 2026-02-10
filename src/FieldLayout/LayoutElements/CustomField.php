<?php

declare(strict_types=1);

namespace CraftCms\Cms\FieldLayout\LayoutElements;

use Craft;
use craft\base\ElementInterface;
use craft\elements\conditions\ElementConditionInterface;
use craft\elements\conditions\users\UserCondition;
use craft\helpers\Cp;
use CraftCms\Cms\Component\Contracts\Actionable;
use CraftCms\Cms\Component\Contracts\Iconic;
use CraftCms\Cms\Field\ContentBlock;
use CraftCms\Cms\Field\Contracts\CrossSiteCopyableFieldInterface;
use CraftCms\Cms\Field\Contracts\FieldInterface;
use CraftCms\Cms\Field\Contracts\PreviewableFieldInterface;
use CraftCms\Cms\Field\Contracts\ThumbableFieldInterface;
use CraftCms\Cms\Field\Exceptions\FieldNotFoundException;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Facades\Fields;
use CraftCms\Cms\Support\Facades\I18N;
use CraftCms\Cms\Support\Html;
use CraftCms\Cms\Support\Str;
use CraftCms\Cms\User\Elements\User;
use Illuminate\Support\Facades\Auth;
use Override;
use Throwable;
use yii\base\InvalidConfigException;

use function CraftCms\Cms\t;

/**
 * CustomField represents a custom field that can be included in field layouts.
 *
 * @property FieldInterface $field The custom field this layout field is based on
 * @property string $fieldUid The UID of the field this layout field is based on
 * @property UserCondition|null $editCondition The user condition which determines who can edit this field
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

    private ?FieldInterface $_field = null;

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

    /**
     * {@inheritdoc}
     */
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

    /**
     * {@inheritdoc}
     */
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

    /**
     * {@inheritdoc}
     */
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

    /**
     * {@inheritdoc}
     */
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

    /**
     * {@inheritdoc}
     */
    #[Override]
    public function showAttribute(): bool
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
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

    /**
     * {@inheritdoc}
     */
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

    /**
     * {@inheritdoc}
     */
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

    /**
     * {@inheritdoc}
     */
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

    /**
     * {@inheritdoc}
     */
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
            $nestedOptions = Cp::cardPreviewOptions($field->getFieldLayout(), false);
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

    /**
     * {@inheritdoc}
     */
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
            $nestedOptions = Cp::cardThumbOptions($field->getFieldLayout());
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

    /**
     * {@inheritdoc}
     */
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

    /**
     * {@inheritdoc}
     */
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

    /**
     * {@inheritdoc}
     */
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
     * @throws InvalidConfigException
     * @throws FieldNotFoundException
     */
    public function getField(): FieldInterface
    {
        if (isset($this->_field)) {
            return $this->_field;
        }

        if (! isset($this->_fieldUid)) {
            throw new InvalidConfigException('No field UUID set.');
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
    }

    /**
     * Returns the field’s original handle.
     */
    public function getOriginalHandle(): string
    {
        return $this->_originalHandle;
    }

    /**
     * {@inheritdoc}
     */
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

    /**
     * {@inheritdoc}
     */
    #[Override]
    public function fields(): array
    {
        return [
            ...parent::fields(),
            'fieldUid' => 'fieldUid',
            'editCondition' => fn () => $this->getEditCondition()?->getConfig(),
            'elementEditCondition' => fn () => $this->getElementEditCondition()?->getConfig(),
        ];
    }

    /**
     * {@inheritdoc}
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

    /**
     * {@inheritdoc}
     */
    #[Override]
    protected function settingsHtml(): ?string
    {
        return Craft::$app->getView()->renderTemplate('_includes/forms/fld/custom-field-settings.twig', [
            'field' => $this,
            'defaultLabel' => $this->defaultLabel(),
            'defaultHandle' => $this->_originalHandle,
            'defaultInstructions' => $this->defaultInstructions(),
            'labelHidden' => ! $this->showLabel(),
        ]);
    }

    /**
     * {@inheritdoc}
     */
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

    /**
     * {@inheritdoc}
     */
    protected function defaultLabel(?ElementInterface $element = null, bool $static = false): ?string
    {
        if ($this->_originalName !== '' && $this->_originalName !== null && $this->_originalName !== '__blank__') {
            return t($this->_originalName, category: 'site');
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
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

    /**
     * {@inheritdoc}
     */
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

    /**
     * {@inheritdoc}
     */
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

    /**
     * {@inheritdoc}
     */
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

    /**
     * {@inheritdoc}
     */
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

    /**
     * {@inheritdoc}
     */
    protected function defaultInstructions(?ElementInterface $element = null, bool $static = false): ?string
    {
        return $this->_originalInstructions ? t($this->_originalInstructions, category: 'site') : null;
    }

    /**
     * {@inheritdoc}
     */
    #[Override]
    protected function conditionalSettingsHtml(): string
    {
        $html = (string) parent::conditionalSettingsHtml();

        $editCondition = $this->getEditCondition() ?? self::defaultEditCondition();
        $editCondition->mainTag = 'div';
        $editCondition->id = 'edit-condition';
        $editCondition->name = 'editCondition';
        $editCondition->forProjectConfig = true;

        $editConditionsHtml = Cp::fieldHtml($editCondition->getBuilderHtml(), [
            'label' => t('Current User Condition'),
            'instructions' => t('Only make editable for users who match the following rules:'),
        ]);

        // Do we know the element type?
        /** @var class-string<ElementInterface>|string|null $elementType */
        $elementType = $this->elementType ?? $this->getLayout()->type;

        if ($elementType && is_subclass_of($elementType, ElementInterface::class)) {
            $elementEditCondition = $this->getElementEditCondition();
            if (! $elementEditCondition) {
                $elementEditCondition = clone self::defaultElementEditCondition($elementType);
                $elementEditCondition->setFieldLayouts([$this->getLayout()]);
            }
            $elementEditCondition->mainTag = 'div';
            $elementEditCondition->id = 'element-edit-condition';
            $elementEditCondition->name = 'elementEditCondition';
            $elementEditCondition->forProjectConfig = true;

            $editConditionsHtml .= Cp::fieldHtml($elementEditCondition->getBuilderHtml(), [
                'label' => t('{type} Condition', [
                    'type' => $elementType::displayName(),
                ]),
                'instructions' => t('Only make editable when editing {type} that match the following rules:', [
                    'type' => $elementType::pluralLowerDisplayName(),
                ]),
            ]);
        }

        return $html.Html::beginTag('fieldset', ['class' => 'pane']).
            Html::tag('legend', t('Editability Conditions')).
            Html::tag('div', $editConditionsHtml).
            Html::endTag('fieldset');
    }

    /**
     * Returns whether the field can be edited by the current user.
     */
    public function editable(?ElementInterface $element): bool
    {
        $editCondition = $this->getEditCondition();

        if ($editCondition) {
            $currentUser = Auth::user();
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

    /**
     * {@inheritdoc}
     */
    #[Override]
    public function formHtml(?ElementInterface $element = null, bool $static = false): ?string
    {
        $view = Craft::$app->getView();
        $isDeltaRegistrationActive = $view->getIsDeltaRegistrationActive();
        $view->setIsDeltaRegistrationActive(
            $isDeltaRegistrationActive &&
            ($element->id ?? false) &&
            ! $static
        );
        $html = $view->namespaceInputs(fn () => (string) parent::formHtml($element, $static), 'fields');
        $view->setIsDeltaRegistrationActive($isDeltaRegistrationActive);

        return $html;
    }

    /**
     * {@inheritdoc}
     */
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

    /**
     * {@inheritdoc}
     */
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

    /**
     * {@inheritdoc}
     */
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

    /**
     * {@inheritdoc}
     */
    protected function inputHtml(?ElementInterface $element = null, bool $static = false): ?string
    {
        try {
            $field = $this->getField();
        } catch (FieldNotFoundException) {
            return null;
        }

        $field->static = $static;
        $value = $element ? $element->getFieldValue($field->handle) : $field->normalizeValue(null, null);

        if ($static) {
            return $field->getStaticHtml($value, $element);
        }

        $view = Craft::$app->getView();
        $isDirty = $element?->isFieldDirty($field->handle);
        $view->registerDeltaName($field->handle, $isDirty);

        $describedBy = $field->describedBy;
        $field->describedBy = $this->describedBy($element, $static);

        $html = $field->getInputHtml($value, $element);

        $field->describedBy = $describedBy;

        return $html !== '' ? $html : null;
    }

    /**
     * {@inheritdoc}
     */
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

    /**
     * {@inheritdoc}
     */
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

    /**
     * {@inheritdoc}
     */
    protected function translationDescription(?ElementInterface $element = null, bool $static = false): ?string
    {
        try {
            $field = $this->getField();
        } catch (FieldNotFoundException) {
            return null;
        }

        return $field->getTranslationDescription($element);
    }

    /**
     * {@inheritdoc}
     */
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

    /**
     * {@inheritdoc}
     */
    #[Override]
    protected function actionMenuItems(?ElementInterface $element = null, bool $static = false): array
    {
        try {
            $field = $this->getField();
        } catch (FieldNotFoundException) {
            $field = null;
        }

        if ($field instanceof Actionable) {
            $field->static = $static;
            $items = $field->getActionMenuItems();
        } else {
            $items = [];
        }

        $user = Auth::user();
        if ($user?->isAdmin() && ! $user->getPreference('showFieldHandles')) {
            $items[] = $this->copyAttributeAction([
                'label' => t('Copy field handle'),
                'promptLabel' => t('Field Handle'),
            ]);
        }

        return $items;
    }
}
