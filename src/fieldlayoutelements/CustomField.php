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
use craft\base\PreviewableFieldInterface;
use craft\base\ThumbableFieldInterface;
use craft\elements\conditions\users\UserCondition;
use craft\elements\User;
use craft\errors\FieldNotFoundException;
use craft\helpers\ArrayHelper;
use craft\helpers\Cp;
use craft\helpers\Html;
use craft\helpers\Inflector;
use craft\helpers\StringHelper;

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
     * @return UserCondition
     */
    private static function defaultEditCondition(): UserCondition
    {
        return self::$defaultEditCondition ??= User::createCondition();
    }

    /**
     * @var string|null The field handle override.
     * @since 5.0.0
     */
    public ?string $handle = null;

    private ?FieldInterface $_field = null;
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
        return $this->_field::isMultiInstance();
    }

    /**
     * @inheritdoc
     */
    public function attribute(): string
    {
        return $this->handle ?? $this->_field->handle;
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
        return $element?->getFieldValue($this->_field->handle);
    }

    /**
     * @inheritdoc
     */
    public function requirable(): bool
    {
        return $this->_field::isRequirable();
    }

    /**
     * @inheritdoc
     */
    public function thumbable(): bool
    {
        return $this->_field instanceof ThumbableFieldInterface;
    }

    /**
     * @inheritdoc
     */
    public function previewable(): bool
    {
        return $this->_field instanceof PreviewableFieldInterface;
    }

    /**
     * @inheritdoc
     */
    public function thumbHtml(ElementInterface $element, int $size): ?string
    {
        $field = $this->getField();
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
        $field = $this->getField();
        if (!$field instanceof PreviewableFieldInterface) {
            return '';
        }
        return $field->getPreviewHtml($element->getFieldValue($field->handle), $element);
    }

    /**
     * Returns the custom field this layout field is based on.
     *
     * @return FieldInterface
     */
    public function getField(): FieldInterface
    {
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
        return $this->_field->uid;
    }

    /**
     * Sets the UID of the field this layout field is based on.
     *
     * @param string $uid
     * @throws FieldNotFoundException if $uid is invalid
     */
    public function setFieldUid(string $uid): void
    {
        if (($field = Craft::$app->getFields()->getFieldByUid($uid)) === null) {
            throw new FieldNotFoundException($uid);
        }
        $this->setField($field);
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
        return parent::hasConditions() || $this->getEditCondition();
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
     * @inheritdoc
     */
    public function fields(): array
    {
        return [
            ...parent::fields(),
            'fieldUid' => 'fieldUid',
            'editCondition' => fn() => $this->getEditCondition()?->getConfig(),
        ];
    }

    /**
     * @inheritdoc
     */
    protected function selectorAttributes(): array
    {
        return ArrayHelper::merge(parent::selectorAttributes(), [
            'data' => [
                'id' => $this->_field->id,
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
        /** @var FieldInterface $field */
        $field = $this->_field;

        return ArrayHelper::merge(parent::containerAttributes($element, $static), [
            'id' => "{$this->_field->handle}-field",
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

        return $this->_field->name !== '__blank__';
    }

    /**
     * @inheritdoc
     */
    protected function selectorIcon(): ?string
    {
        return $this->_field::icon();
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
    protected function statusClass(?ElementInterface $element = null, bool $static = false): ?string
    {
        if ($element && ($status = $this->_field->getStatus($element))) {
            return StringHelper::toString($status[0]);
        }
        return null;
    }

    /**
     * @inheritdoc
     */
    protected function statusLabel(?ElementInterface $element = null, bool $static = false): ?string
    {
        if ($element && ($status = $this->_field->getStatus($element))) {
            return $status[1];
        }
        return null;
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

        $html .= Html::beginTag('fieldset', ['class' => 'pane']) .
            Html::tag('legend', Craft::t('app', 'Editability Conditions')) .
            Html::beginTag('div') .
            Cp::fieldHtml($editCondition->getBuilderHtml(), [
                'label' => Craft::t('app', 'Current User Condition'),
                'instructions' => Craft::t('app', 'Only make editable for users who match the following rules:'),
            ]) .
            Html::endTag('div') .
            Html::endTag('fieldset');

        return $html;
    }

    /**
     * Returns whether the field can be edited by the current user.
     *
     * @return bool
     * @since 5.7.0
     */
    public function editable(): bool
    {
        $editCondition = $this->getEditCondition();

        if ($editCondition) {
            $currentUser = Craft::$app->getUser()->getIdentity();
            if ($currentUser && !$editCondition->matchElement($currentUser)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @inheritdoc
     */
    public function formHtml(?ElementInterface $element = null, bool $static = false): ?string
    {
        $static = $static || !$this->editable();

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
        return $this->_field->useFieldset();
    }

    /**
     * @inheritdoc
     */
    protected function id(): string
    {
        return $this->_field->getInputId();
    }

    /**
     * @inheritdoc
     */
    protected function labelId(): string
    {
        return $this->_field->getLabelId();
    }

    /**
     * @inheritdoc
     */
    protected function inputHtml(?ElementInterface $element = null, bool $static = false): ?string
    {
        $this->_field->static = $static;
        $value = $element ? $element->getFieldValue($this->_field->handle) : $this->_field->normalizeValue(null, null);

        if ($static) {
            return $this->_field->getStaticHtml($value, $element);
        }

        $view = Craft::$app->getView();
        $isDirty = $element?->isFieldDirty($this->_field->handle);
        $view->registerDeltaName($this->_field->handle, $isDirty);

        $describedBy = $this->_field->describedBy;
        $this->_field->describedBy = $this->describedBy($element, $static);

        $html = $this->_field->getInputHtml($value, $element);

        $this->_field->describedBy = $describedBy;

        return $html !== '' ? $html : null;
    }

    /**
     * @inheritdoc
     */
    protected function orientation(?ElementInterface $element = null, bool $static = false): string
    {
        return $this->_field->getOrientation($element);
    }

    /**
     * @inheritdoc
     */
    protected function translatable(?ElementInterface $element = null, bool $static = false): bool
    {
        return $this->_field->getIsTranslatable($element);
    }

    /**
     * @inheritdoc
     */
    protected function translationDescription(?ElementInterface $element = null, bool $static = false): ?string
    {
        return $this->_field->getTranslationDescription($element);
    }

    /**
     * @inheritdoc
     */
    public function isCrossSiteCopyable(ElementInterface $element): bool
    {
        return $this->_field instanceof CrossSiteCopyableFieldInterface && $this->_field->getIsTranslatable($element);
    }

    /**
     * @inheritdoc
     */
    protected function actionMenuItems(?ElementInterface $element = null, bool $static = false): array
    {
        if ($this->_field instanceof Actionable) {
            $this->_field->static = $static;
            $items = $this->_field->getActionMenuItems();
        } else {
            $items = [];
        }

        return $items;
    }
}
