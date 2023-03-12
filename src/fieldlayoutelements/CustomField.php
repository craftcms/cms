<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\fieldlayoutelements;

use Craft;
use craft\base\ElementInterface;
use craft\base\FieldInterface;
use craft\errors\FieldNotFoundException;
use craft\helpers\ArrayHelper;

/**
 * CustomField represents a custom field that can be included in field layouts.
 *
 * @property-write FieldInterface $field The custom field this layout field is based on
 * @property string $fieldUid The UID of the field this layout field is based on
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.5.0
 */
class CustomField extends BaseField
{
    /**
     * @var FieldInterface|null The custom field this layout field is based on.
     */
    private ?FieldInterface $_field = null;

    /**
     * @inheritdoc
     * @param FieldInterface|null $field
     */
    public function __construct(?FieldInterface $field = null, $config = [])
    {
        $this->_field = $field;
        parent::__construct($config);
    }

    /**
     * @inheritdoc
     */
    public function attribute(): string
    {
        return $this->_field->handle;
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
        $this->_field = $field;
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
        $this->_field = $field;
    }

    /**
     * @inheritdoc
     */
    public function fields(): array
    {
        $fields = parent::fields();
        $fields['fieldUid'] = 'fieldUid';
        return $fields;
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
    protected function containerAttributes(?ElementInterface $element = null, bool $static = false): array
    {
        $attributes = parent::containerAttributes($element, $static);
        $attributes['id'] = "{$this->_field->handle}-field";
        $attributes['data']['type'] = get_class($this->_field);
        return $attributes;
    }

    /**
     * @inheritdoc
     */
    protected function defaultLabel(?ElementInterface $element = null, bool $static = false): ?string
    {
        if ($this->_field->name !== '' && $this->_field->name !== null && $this->_field->name !== '__blank__') {
            return Craft::t('site', $this->_field->name);
        }
        return null;
    }

    /**
     * Returns whether the label should be shown in form inputs.
     *
     * @return bool
     * @since 3.5.6
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
    protected function statusClass(?ElementInterface $element = null, bool $static = false): ?string
    {
        if ($element && ($status = $this->_field->getStatus($element))) {
            return $status[0];
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
        return $this->_field->instructions ? Craft::t('site', $this->_field->instructions) : null;
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
        $html = $view->namespaceInputs(function() use ($element, $static) {
            return (string)parent::formHtml($element, $static);
        }, 'fields');
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
        $value = $element ? $element->getFieldValue($this->_field->handle) : $this->_field->normalizeValue(null);

        if ($static) {
            return $this->_field->getStaticHtml($value, $element);
        }

        $view = Craft::$app->getView();
        $view->registerDeltaName($this->_field->handle);

        $required = $this->_field->required;
        $describedBy = $this->_field->describedBy;

        $this->_field->required = $this->required;
        $this->_field->describedBy = $this->describedBy($element, $static);

        $html = $this->_field->getInputHtml($value, $element);

        $this->_field->required = $required;
        $this->_field->describedBy = $describedBy;

        return $html;
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
}
