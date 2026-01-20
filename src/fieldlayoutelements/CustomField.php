<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\fieldlayoutelements;

use Craft;
use craft\base\Actionable;
use craft\base\CrossSiteCopyableFieldInterface;
use craft\base\ElementInterface;
use craft\base\FieldInterface;
use craft\base\Iconic;
use craft\base\PreviewableFieldInterface;
use craft\base\ThumbableFieldInterface;
use craft\elements\conditions\ElementConditionInterface;
use craft\elements\conditions\users\UserCondition;
use craft\elements\User;
use craft\errors\FieldNotFoundException;
use craft\fields\ContentBlock;
use craft\helpers\ArrayHelper;
use craft\helpers\Cp;
use craft\helpers\Html;
use craft\helpers\Inflector;
use craft\helpers\StringHelper;
use yii\base\InvalidConfigException;

/**
 * CustomField represents a custom field that can be included in field layouts.
 *
 * @property FieldInterface $field The custom field this layout field is based on
 * @property string $fieldUid The UID of the field this layout field is based on
 * @property UserCondition|null $editCondition The user condition which determines who can edit this field
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.5.0
 */
class CustomField extends BaseField
{
    /**
     * @var UserCondition
     */
    private static UserCondition $defaultEditCondition;

    /**
     * @var ElementConditionInterface[]
     */
    private static array $defaultElementEditConditions = [];

    /**
     * @return UserCondition
     */
    private static function defaultEditCondition(): UserCondition
    {
        return self::$defaultEditCondition ??= User::createCondition();
    }

    /**
     * @param class-string<ElementInterface> $elementType
     * @return ElementConditionInterface
     */
    private static function defaultElementEditCondition(string $elementType): ElementConditionInterface
    {
        return self::$defaultElementEditConditions[$elementType] ??= $elementType::createCondition();
    }

    /**
     * @var string|null The field handle override.
     * @since 5.0.0
     */
    public ?string $handle = null;

    private ?FieldInterface $_field = null;
    private ?string $_fieldUid = null;
    private ?string $_originalName = null;
    private ?string $_originalHandle = null;
    private ?string $_originalInstructions = null;

    /**
     * @var UserCondition|class-string<UserCondition>|array|null
     * @phpstan-var UserCondition|class-string<UserCondition>|array{class:class-string<UserCondition>}|null
     * @see getEditCondition()
     * @see setEditCondition()
     */
    private mixed $_editCondition = null;

    /**
     * @var ElementConditionInterface|class-string<ElementConditionInterface>|array|null
     * @phpstan-var ElementConditionInterface|class-string<ElementConditionInterface>|array{class:class-string<ElementConditionInterface>}|null
     * @see getElementEditCondition()
     * @see setElementEditCondition()
     */
    private mixed $_elementEditCondition = null;

    /**
     * @inheritdoc
     * @param FieldInterface|null $field
     */
    public function __construct(?FieldInterface $field = null, $config = [])
    {
        // ensure we set the field last, so it has access to other properties that need to be set first
        // see https://github.com/craftcms/cms/issues/15752
        $fieldUid = ArrayHelper::remove($config, 'fieldUid');
        if ($fieldUid) {
            $config['fieldUid'] = $fieldUid;
        }

        parent::__construct($config);

        if ($field) {
            $this->setField($field);
        }
    }

    /**
     * @inheritdoc
     */
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
     * @inheritdoc
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
     * @inheritdoc
     */
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
     * @inheritdoc
     */
    public function showAttribute(): bool
    {
        return true;
    }

    /**
     * @inheritdoc
     * @since 3.5.2
     */
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
     * @inheritdoc
     */
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
     * @inheritdoc
     */
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
     * @inheritdoc
     */
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
     * @inheritdoc
     */
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

        if (!$this->previewable()) {
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
     * @inheritdoc
     */
    public function thumbHtml(ElementInterface $element, int $size): ?string
    {
        try {
            $field = $this->getField();
        } catch (FieldNotFoundException) {
            return null;
        }

        if (!$field instanceof ThumbableFieldInterface) {
            return null;
        }
        return $field->getThumbHtml($element->getFieldValue($field->handle), $element, $size);
    }

    /**
     * @inheritdoc
     */
    public function previewHtml(ElementInterface $element): string
    {
        try {
            $field = $this->getField();
        } catch (FieldNotFoundException) {
            return '';
        }

        if (!$field instanceof PreviewableFieldInterface) {
            return '';
        }

        return $field->getPreviewHtml($element->getFieldValue($field->handle), $element);
    }

    /**
     * @inheritdoc
     */
    public function keywords(): array
    {
        $fieldTypeKeyword = [];

        try {
            $field = $this->getField();
            // include field type display name in the field layout designer's keywords
            $fieldTypeKeyword = [$field->displayName()];
        } catch (\Throwable $e) {
            // fail silently
        }

        return array_filter(
            array_merge(parent::keywords(), $fieldTypeKeyword)
        );
    }

    /**
     * Returns the custom field this layout field is based on.
     *
     * @return FieldInterface
     * @throws InvalidConfigException
     * @throws FieldNotFoundException
     */
    public function getField(): FieldInterface
    {
        if (!isset($this->_field)) {
            if (!isset($this->_fieldUid)) {
                throw new InvalidConfigException('No field UUID set.');
            }
            if (($field = Craft::$app->getFields()->getFieldByUid($this->_fieldUid)) === null) {
                throw new FieldNotFoundException($this->_fieldUid);
            }
            $this->setField($field);
        }

        return $this->_field;
    }

    /**
     * Sets the custom field this layout field is based on.
     *
     * @param FieldInterface $field
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
        $this->_field->required = $this->required;
    }

    /**
     * Returns the UID of the field this layout field is based on.
     *
     * @return string
     */
    public function getFieldUid(): string
    {
        return $this->_fieldUid;
    }

    /**
     * Sets the UID of the field this layout field is based on.
     *
     * @param string $uid
     */
    public function setFieldUid(string $uid): void
    {
        $this->_fieldUid = $uid;
        $this->_field = null;
    }

    /**
     * Returns the field’s original handle.
     *
     * @return string
     * @since 5.0.0
     */
    public function getOriginalHandle(): string
    {
        return $this->_originalHandle;
    }

    /**
     * @inheritdoc
     */
    public function hasConditions(): bool
    {
        return parent::hasConditions() || $this->getEditCondition() || $this->getElementEditCondition();
    }

    /**
     * Returns the edit condition for this layout element.
     *
     * @return UserCondition|null
     * @since 5.7.0
     */
    public function getEditCondition(): ?UserCondition
    {
        if (isset($this->_editCondition) && !$this->_editCondition instanceof UserCondition) {
            $this->_editCondition = $this->normalizeCondition($this->_editCondition);
        }

        return $this->_editCondition;
    }

    /**
     * Sets the edit condition for this layout element.
     *
     * @param UserCondition|class-string<UserCondition>|array|null $editCondition
     * @phpstan-param UserCondition|class-string<UserCondition>|array{class:class-string<UserCondition>}|null $editCondition
     * @since 5.7.0
     */
    public function setEditCondition(mixed $editCondition): void
    {
        $this->_editCondition = $editCondition;
    }

    /**
     * Returns the element edit condition for this layout element.
     *
     * @return ElementConditionInterface|null
     * @since 5.9.0
     */
    public function getElementEditCondition(): ?ElementConditionInterface
    {
        if (isset($this->_elementEditCondition) && !$this->_elementEditCondition instanceof ElementConditionInterface) {
            if (is_string($this->_elementEditCondition)) {
                $this->_elementEditCondition = ['class' => $this->_elementEditCondition];
            }
            $this->_elementEditCondition = array_merge(
                ['fieldLayouts' => [$this->getLayout()]],
                $this->_elementEditCondition,
            );
            $this->_elementEditCondition = $this->normalizeCondition($this->_elementEditCondition);
        }

        return $this->_elementEditCondition;
    }

    /**
     * Sets the element edit condition for this layout element.
     *
     * @param ElementConditionInterface|class-string<ElementConditionInterface>|array|null $elementEditCondition
     * @phpstan-param ElementConditionInterface|class-string<ElementConditionInterface>|array{class:class-string<ElementConditionInterface>}|null $elementEditCondition
     * @since 5.9.0
     */
    public function setElementEditCondition(mixed $elementEditCondition): void
    {
        $this->_elementEditCondition = $elementEditCondition;
    }

    /**
     * @inheritdoc
     */
    public function fields(): array
    {
        return [
            ...parent::fields(),
            'fieldUid' => 'fieldUid',
            'editCondition' => fn() => $this->getEditCondition()?->getConfig(),
            'elementEditCondition' => fn() => $this->getElementEditCondition()?->getConfig(),
        ];
    }

    /**
     * @inheritdoc
     */
    protected function selectorAttributes(): array
    {
        $attributes = parent::selectorAttributes();

        try {
            $field = $this->getField();
        } catch (FieldNotFoundException) {
            return $attributes;
        }

        return ArrayHelper::merge($attributes, [
            'data' => [
                'id' => $field->id,
            ],
        ]);
    }

    /**
     * @inheritdoc
     */
    protected function settingsHtml(): ?string
    {
        return Craft::$app->getView()->renderTemplate('_includes/forms/fld/custom-field-settings.twig', [
            'field' => $this,
            'defaultLabel' => $this->defaultLabel(),
            'defaultHandle' => $this->_originalHandle,
            'defaultInstructions' => $this->defaultInstructions(),
            'labelHidden' => !$this->showLabel(),
        ]);
    }

    /**
     * @inheritdoc
     */
    protected function containerAttributes(?ElementInterface $element = null, bool $static = false): array
    {
        $attributes = parent::containerAttributes($element, $static);

        try {
            $field = $this->getField();
        } catch (FieldNotFoundException) {
            return $attributes;
        }

        return ArrayHelper::merge($attributes, [
            'id' => "{$field->handle}-field",
            'data' => [
                'type' => get_class($field),
            ],
        ]);
    }

    /**
     * @inheritdoc
     */
    protected function defaultLabel(?ElementInterface $element = null, bool $static = false): ?string
    {
        if ($this->_originalName !== '' && $this->_originalName !== null && $this->_originalName !== '__blank__') {
            return Craft::t('site', $this->_originalName);
        }
        return null;
    }

    /**
     * @inheritdoc
     */
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
     * @inheritdoc
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

    protected function selectorIndicators(): array
    {
        $indicators = parent::selectorIndicators();

        if (isset($this->label) || isset($this->instructions) || isset($this->handle)) {
            $attributes = array_values(array_filter([
                isset($this->label) ? Craft::t('app', 'Name') : null,
                isset($this->instructions) ? Craft::t('app', 'Instructions') : null,
                isset($this->handle) ? Craft::t('app', 'Handle') : null,
            ]));
            array_unshift($indicators, [
                'label' => Craft::t('app', 'This field’s {attributes} {totalAttributes, plural, =1{has} other{have}} been overridden.', [
                    'attributes' => mb_strtolower(Inflector::sentence($attributes)),
                    'totalAttributes' => count($attributes),
                ]),
                'icon' => 'pencil',
                'iconColor' => 'teal',
            ]);
        }

        return $indicators;
    }

    /**
     * @inheritdoc
     */
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
     * @inheritdoc
     */
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
        return $status ? StringHelper::toString($status[0]) : null;
    }

    /**
     * @inheritdoc
     */
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
     * @inheritdoc
     */
    protected function defaultInstructions(?ElementInterface $element = null, bool $static = false): ?string
    {
        return $this->_originalInstructions ? Craft::t('site', $this->_originalInstructions) : null;
    }

    /**
     * @inheritdoc
     */
    protected function conditionalSettingsHtml(): string
    {
        $html = (string)parent::conditionalSettingsHtml();

        $editCondition = $this->getEditCondition() ?? self::defaultEditCondition();
        $editCondition->mainTag = 'div';
        $editCondition->id = 'edit-condition';
        $editCondition->name = 'editCondition';
        $editCondition->forProjectConfig = true;

        $editConditionsHtml = Cp::fieldHtml($editCondition->getBuilderHtml(), [
            'label' => Craft::t('app', 'Current User Condition'),
            'instructions' => Craft::t('app', 'Only make editable for users who match the following rules:'),
        ]);

        // Do we know the element type?
        /** @var class-string<ElementInterface>|string|null $elementType */
        $elementType = $this->elementType ?? $this->getLayout()->type;

        if ($elementType && is_subclass_of($elementType, ElementInterface::class)) {
            $elementEditCondition = $this->getElementEditCondition();
            if (!$elementEditCondition) {
                $elementEditCondition = clone self::defaultElementEditCondition($elementType);
                $elementEditCondition->setFieldLayouts([$this->getLayout()]);
            }
            $elementEditCondition->mainTag = 'div';
            $elementEditCondition->id = 'element-edit-condition';
            $elementEditCondition->name = 'elementEditCondition';
            $elementEditCondition->forProjectConfig = true;

            $editConditionsHtml .= Cp::fieldHtml($elementEditCondition->getBuilderHtml(), [
                'label' => Craft::t('app', '{type} Condition', [
                    'type' => $elementType::displayName(),
                ]),
                'instructions' => Craft::t('app', 'Only make editable when editing {type} that match the following rules:', [
                    'type' => $elementType::pluralLowerDisplayName(),
                ]),
            ]);
        }

        return $html . Html::beginTag('fieldset', ['class' => 'pane']) .
            Html::tag('legend', Craft::t('app', 'Editability Conditions')) .
            Html::tag('div', $editConditionsHtml) .
            Html::endTag('fieldset');
    }

    /**
     * Returns whether the field can be edited by the current user.
     *
     * @param ElementInterface|null $element
     * @return bool
     * @since 5.7.0
     */
    public function editable(?ElementInterface $element): bool
    {
        $editCondition = $this->getEditCondition();

        if ($editCondition) {
            $currentUser = Craft::$app->getUser()->getIdentity();
            if (!$currentUser || !$editCondition->matchElement($currentUser)) {
                return false;
            }
        }

        $elementEditCondition = $this->getElementEditCondition();

        if ($elementEditCondition && $element && !$elementEditCondition->matchElement($element)) {
            return false;
        }

        return true;
    }

    /**
     * @inheritdoc
     */
    public function formHtml(?ElementInterface $element = null, bool $static = false): ?string
    {
        $view = Craft::$app->getView();
        $isDeltaRegistrationActive = $view->getIsDeltaRegistrationActive();
        $view->setIsDeltaRegistrationActive(
            $isDeltaRegistrationActive &&
            ($element->id ?? false) &&
            !$static
        );
        $html = $view->namespaceInputs(fn() => (string)parent::formHtml($element, $static), 'fields');
        $view->setIsDeltaRegistrationActive($isDeltaRegistrationActive);

        return $html;
    }

    /**
     * @inheritdoc
     */
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
     * @inheritdoc
     */
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
     * @inheritdoc
     */
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
     * @inheritdoc
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
     * @inheritdoc
     */
    protected function orientation(?ElementInterface $element = null, bool $static = false): string
    {
        try {
            $field = $this->getField();
        } catch (FieldNotFoundException) {
            return Craft::$app->getLocale()->getOrientation();
        }

        return $field->getOrientation($element);
    }

    /**
     * @inheritdoc
     */
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
     * @inheritdoc
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
     * @inheritdoc
     */
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
     * @inheritdoc
     */
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

        $user = Craft::$app->getUser()->getIdentity();
        if ($user?->admin && !$user->getPreference('showFieldHandles')) {
            $items[] = $this->copyAttributeAction([
                'label' => Craft::t('app', 'Copy field handle'),
                'promptLabel' => Craft::t('app', 'Field Handle'),
            ]);
        }

        return $items;
    }
}
