<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\fields;

use Craft;
use craft\base\ElementInterface;
use craft\base\Field;
use craft\base\PreviewableFieldInterface;
use craft\base\SortableFieldInterface;
use craft\elements\db\ElementQuery;
use craft\elements\db\ElementQueryInterface;
use craft\helpers\Db;
use GraphQL\Type\Definition\Type;
use yii\db\Schema;

/**
 * Lightswitch represents a Lightswitch field.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class Lightswitch extends Field implements PreviewableFieldInterface, SortableFieldInterface
{
    /**
     * @inheritdoc
     */
    public static function displayName(): string
    {
        return Craft::t('app', 'Lightswitch');
    }

    /**
     * @inheritdoc
     */
    public static function valueType(): string
    {
        return 'bool';
    }

    /**
     * @var bool Whether the lightswitch should be enabled by default
     */
    public $default = false;

    /**
     * @inheritdoc
     */
    public function getContentColumnType(): string
    {
        return Schema::TYPE_BOOLEAN;
    }

    /**
     * @inheritdoc
     */
    public function getSettingsHtml()
    {
        return Craft::$app->getView()->renderTemplateMacro('_includes/forms', 'lightswitchField',
            [
                [
                    'label' => Craft::t('app', 'Default Value'),
                    'id' => 'default',
                    'name' => 'default',
                    'on' => $this->default,
                ]
            ]);
    }

    /**
     * @inheritdoc
     */
    public function getInputHtml($value, ElementInterface $element = null): string
    {
        $id = Craft::$app->getView()->formatInputId($this->handle);

        return Craft::$app->getView()->renderTemplate('_includes/forms/lightswitch',
            [
                'id' => $id,
                'labelId' => $id . '-label',
                'name' => $this->handle,
                'on' => (bool)$value,
            ]);
    }

    /**
     * @inheritdoc
     */
    public function getTableAttributeHtml($value, ElementInterface $element): string
    {
        if ($value) {
            return '<div class="status enabled" title="' . Craft::t('app', 'Enabled') . '"></div>';
        }

        return '<div class="status" title="' . Craft::t('app', 'Not enabled') . '"></div>';
    }

    /**
     * @inheritdoc
     */
    public function normalizeValue($value, ElementInterface $element = null)
    {
        // If this is a new entry, look for a default option
        if ($value === null) {
            $value = $this->default;
        }

        return (bool)$value;
    }

    /**
     * @inheritdoc
     */
    public function modifyElementsQuery(ElementQueryInterface $query, $value)
    {
        if ($value === null) {
            return null;
        }

        $column = 'content.' . Craft::$app->getContent()->fieldColumnPrefix . $this->handle;
        /** @var ElementQuery $query */
        $query->subQuery->andWhere(Db::parseBooleanParam($column, $value, (bool)$this->default));
        return null;
    }

    /**
     * @inheritdoc
     */
    public function getContentGqlType()
    {
        return Type::boolean();
    }
}
