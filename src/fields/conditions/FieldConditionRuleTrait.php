<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\fields\conditions;

use Craft;
use craft\base\ElementInterface;
use craft\base\FieldInterface;
use craft\elements\db\ElementQueryInterface;
use craft\errors\InvalidFieldException;
use yii\base\InvalidConfigException;
use yii\db\QueryInterface;

/**
 * FieldConditionRuleTrait implements the common methods and properties for custom fields’ query condition rule classes.
 *
 * @property-write string $fieldUid The UUID of the custom field associated with this rule
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.0
 */
trait FieldConditionRuleTrait
{
    /**
     * @var string The UUID of the custom field associated with this rule
     */
    private string $_fieldUid;

    /**
     * @var FieldInterface The custom field associated with this rule
     */
    private FieldInterface $_field;

    /**
     * @inheritdoc
     */
    public function getGroupLabel(): ?string
    {
        return Craft::t('app', 'Fields');
    }

    /**
     * @inheritdoc
     */
    public function setFieldUid(string $uid): void
    {
        $this->_fieldUid = $uid;
        if (isset($this->_field) && $this->_field->uid !== $uid) {
            unset($this->_field);
        }
    }

    /**
     * Returns the custom field associated with this rule.
     *
     * @return FieldInterface
     * @throws InvalidConfigException if [[fieldUid]] is invalid
     */
    protected function field(): FieldInterface
    {
        if (!isset($this->_field)) {
            if (!isset($this->_fieldUid)) {
                throw new InvalidConfigException('No field UUID set on the field condition rule yet.');
            }
            $field = Craft::$app->getFields()->getFieldByUid($this->_fieldUid);
            if (!$field) {
                throw new InvalidConfigException("Invalid field UUID: $this->_fieldUid");
            }
            $this->_field = $field;
        }
        return $this->_field;
    }

    /**
     * @inheritdoc
     */
    public function getConfig(): array
    {
        return array_merge(parent::getConfig(), [
            'fieldUid' => $this->field()->uid,
        ]);
    }

    /**
     * @inheritdoc
     */
    public function getLabel(): string
    {
        return $this->field()->name;
    }

    /**
     * @inheritdoc
     */
    public function getExclusiveQueryParams(): array
    {
        try {
            $field = $this->field();
        } catch (InvalidConfigException) {
            // The field doesn't exist
            return [];
        }

        return [$field->handle];
    }

    /**
     * @inheritdoc
     */
    public function modifyQuery(QueryInterface $query): void
    {
        $param = $this->elementQueryParam();
        if ($param !== null) {
            /** @var ElementQueryInterface $query */
            $query->{$this->field()->handle}($param);
        }
    }

    /**
     * @inheritdoc
     */
    public function matchElement(ElementInterface $element): bool
    {
        try {
            $field = $this->field();
        } catch (InvalidConfigException) {
            // The field doesn't exist
            return true;
        }

        try {
            $value = $element->getFieldValue($field->handle);
        } catch (InvalidFieldException) {
            // The field doesn't belong to the element's field layout
            return false;
        }

        return $this->matchFieldValue($value);
    }

    /**
     * @return mixed
     */
    abstract protected function elementQueryParam(): mixed;

    /**
     * @return mixed
     */
    abstract protected function matchFieldValue($value): bool;

    /**
     * @inheritdoc
     */
    protected function defineRules(): array
    {
        return array_merge(parent::defineRules(), [
            [['fieldUid'], 'safe'],
        ]);
    }
}
