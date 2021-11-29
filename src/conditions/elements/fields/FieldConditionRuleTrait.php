<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\conditions\elements\fields;

use Craft;
use craft\base\FieldInterface;
use craft\elements\db\ElementQueryInterface;
use yii\db\QueryInterface;

/**
 * FieldConditionRuleTrait implements the common methods and properties for custom fields’ query condition rule classes.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.0
 */
trait FieldConditionRuleTrait
{
    /**
     * @var FieldInterface The custom field this rule is associated with.
     */
    private FieldInterface $_field;

    /**
     * @inheritdoc
     */
    public function setFieldUid(string $uid): void
    {
        $this->_field = Craft::$app->getFields()->getFieldByUid($uid);
    }

    /**
     * @inheritdoc
     */
    public function getFieldUid(): string
    {
        return $this->_field->uid;
    }

    /**
     * @inheritdoc
     */
    public function getConfig(): array
    {
        return array_merge(parent::getConfig(), [
            'fieldUid' => $this->_field->uid,
        ]);
    }

    /**
     * @inheritdoc
     */
    public function getLabel(): string
    {
        return $this->_field->name;
    }

    /**
     * @inheritdoc
     */
    public function getExclusiveQueryParams(): array
    {
        return [$this->_field->handle];
    }

    /**
     * @inheritdoc
     */
    public function modifyQuery(QueryInterface $query): void
    {
        $param = $this->elementQueryParam();
        if ($param !== null) {
            /** @var ElementQueryInterface $query */
            $query->{$this->_field->handle}($param);
        }
    }

    /**
     * @return mixed
     */
    abstract protected function elementQueryParam();

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
