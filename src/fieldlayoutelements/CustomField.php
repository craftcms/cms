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
use craft\helpers\ArrayHelper;
use craft\helpers\Html;
use yii\base\InvalidArgumentException;

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
     * @var FieldInterface The custom field this layout field is based on.
     */
    private $_field;

    /**
     * @inheritdoc
     * @param FieldInterface|null $field
     */
    public function __construct(FieldInterface $field = null, $config = [])
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
    protected function value(ElementInterface $element = null)
    {
        if (!$element) {
            return null;
        }
        return $element->getFieldValue($this->_field->handle);
    }

    /**
     * @inheritdoc
     */
    public function requirable(): bool
    {
        return true;
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
    public function setField(FieldInterface $field)
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
     * @throws InvalidArgumentException if $uid is invalid
     */
    public function setFieldUid(string $uid)
    {
        if (($field = \Craft::$app->getFields()->getFieldByUid($uid)) === null) {
            throw new InvalidArgumentException("Invalid field UID: $uid");
        }
        $this->_field = $field;
    }

    /**
     * @inheritdoc
     */
    public function fields()
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
    protected function containerAttributes(ElementInterface $element = null, bool $static = false): array
    {
        $attributes = parent::containerAttributes($element, $static);
        $attributes['id'] = "{$this->_field->handle}-field";
        $attributes['data']['type'] = get_class($this->_field);
        return $attributes;
    }

    /**
     * @inheritdoc
     */
    protected function labelAttributes(ElementInterface $element = null, bool $static = false): array
    {
        return [
            'id' => "{$this->_field->handle}-label",
            'for' => $this->_field->handle,
        ];
    }

    /**
     * @inheritdoc
     */
    protected function defaultLabel(ElementInterface $element = null, bool $static = false)
    {
        if ($this->_field->name !== '' && $this->_field->name !== null && $this->_field->name !== '__blank__') {
            return Html::encode(Craft::t('site', $this->_field->name));
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
        if ($this->label !== null && $this->label !== '') {
            return parent::showLabel();
        }

        return $this->_field->name !== '__blank__';
    }

    /**
     * @inheritdoc
     */
    protected function statusClass(ElementInterface $element = null, bool $static = false)
    {
        if ($element && ($status = $element->getFieldStatus($this->_field->handle))) {
            return $status[0];
        }
        return null;
    }

    /**
     * @inheritdoc
     */
    protected function statusLabel(ElementInterface $element = null, bool $static = false)
    {
        if ($element && ($status = $element->getFieldStatus($this->_field->handle))) {
            return $status[1];
        }
        return null;
    }

    /**
     * @inheritdoc
     */
    protected function defaultInstructions(ElementInterface $element = null, bool $static = false)
    {
        return $this->_field->instructions ? Craft::t('site', $this->_field->instructions) : null;
    }

    /**
     * @inheritdoc
     */
    public function formHtml(ElementInterface $element = null, bool $static = false)
    {
        $view = Craft::$app->getView();
        $registerDeltas = ($element->id ?? false) && $view->getIsDeltaRegistrationActive();
        $namespace = $view->getNamespace();
        $view->setIsDeltaRegistrationActive(!$static);
        $view->setNamespace($view->namespaceInputName('fields'));

        $html = Html::namespaceHtml(parent::formHtml($element, $static), 'fields');

        $view->setIsDeltaRegistrationActive($registerDeltas);
        $view->setNamespace($namespace);

        return $html;
    }

    /**
     * @inheritdoc
     */
    protected function inputHtml(ElementInterface $element = null, bool $static = false): string
    {
        $value = $element ? $element->getFieldValue($this->_field->handle) : $this->_field->normalizeValue(null);

        if ($static) {
            return $this->_field->getStaticHtml($value, $element);
        }

        $view = Craft::$app->getView();
        $view->registerDeltaName($this->_field->handle);
        if (empty($element->id) || $element->isFieldEmpty($this->_field->handle)) {
            $view->setInitialDeltaValue($this->_field->handle, null);
        }
        return $this->_field->getInputHtml($value, $element);
    }

    /**
     * @inheritdoc
     */
    protected function translatable(ElementInterface $element = null, bool $static = false): bool
    {
        return $this->_field->getIsTranslatable($element);
    }

    /**
     * @inheritdoc
     */
    protected function translationDescription(ElementInterface $element = null, bool $static = false)
    {
        return $this->_field->getTranslationDescription($element);
    }
}
