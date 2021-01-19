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
use craft\helpers\ArrayHelper;
use craft\helpers\Db;
use craft\helpers\Html;
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
     * @var string|null The label text to display beside the lightswitch’s enabled state
     * @since 3.5.4
     */
    public $onLabel;

    /**
     * @var string|null The label text to display beside the lightswitch’s disabled state
     * @since 3.5.4
     */
    public $offLabel;

    /**
     * @inheritdoc
     */
    public function __construct($config = [])
    {
        if (($onLabel = ArrayHelper::remove($config, 'label')) !== null) {
            $config['onLabel'] = $onLabel;
        }

        parent::__construct($config);
    }

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();

        $this->default = (bool)$this->default;
        if ($this->onLabel === '') {
            $this->onLabel = null;
        }
        if ($this->offLabel === '') {
            $this->offLabel = null;
        }
    }

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
        $view = Craft::$app->getView();

        return
            $view->renderTemplateMacro('_includes/forms', 'lightswitchField', [
                [
                    'label' => Craft::t('app', 'Default Value'),
                    'id' => 'default',
                    'name' => 'default',
                    'on' => $this->default,
                ]
            ]) .
            $view->renderTemplateMacro('_includes/forms', 'textField', [
                [
                    'label' => Craft::t('app', 'ON Label'),
                    'instructions' => Craft::t('app', 'The label text to display beside the lightswitch’s enabled state.'),
                    'id' => 'on-label',
                    'name' => 'onLabel',
                    'value' => $this->onLabel,
                ]
            ]) .
            $view->renderTemplateMacro('_includes/forms', 'textField', [
                [
                    'label' => Craft::t('app', 'OFF Label'),
                    'instructions' => Craft::t('app', 'The label text to display beside the lightswitch’s disabled state.'),
                    'id' => 'off-label',
                    'name' => 'offLabel',
                    'value' => $this->offLabel,
                ]
            ]);
    }

    /**
     * @inheritdoc
     */
    protected function inputHtml($value, ElementInterface $element = null): string
    {
        $id = Html::id($this->handle);

        return Craft::$app->getView()->renderTemplate('_includes/forms/lightswitch',
            [
                'id' => Html::id($this->handle),
                'labelId' => $id . '-label',
                'name' => $this->handle,
                'on' => (bool)$value,
                'onLabel' => $this->onLabel,
                'offLabel' => $this->offLabel,
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

    /**
     * @inheritdoc
     * @since 3.5.0
     */
    public function getContentGqlMutationArgumentType()
    {
        return [
            'name' => $this->handle,
            'type' => Type::boolean(),
            'description' => $this->instructions,
        ];
    }

    /**
     * @inheritdoc
     * @since 3.5.0
     */
    public function getContentGqlQueryArgumentType()
    {
        return [
            'name' => $this->handle,
            'type' => Type::boolean(),
        ];
    }
}
