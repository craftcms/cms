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
use craft\fields\conditions\LightswitchFieldConditionRule;
use craft\helpers\ArrayHelper;
use craft\helpers\Cp;
use craft\helpers\Db;
use craft\helpers\ElementHelper;
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
    public static function isRequirable(): bool
    {
        return false;
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
    public bool $default = false;

    /**
     * @var string|null The label text to display beside the lightswitch’s enabled state
     * @since 3.5.4
     */
    public ?string $onLabel = null;

    /**
     * @var string|null The label text to display beside the lightswitch’s disabled state
     * @since 3.5.4
     */
    public ?string $offLabel = null;

    /**
     * @inheritdoc
     */
    public function __construct($config = [])
    {
        // Config normalization
        if (($onLabel = ArrayHelper::remove($config, 'label')) !== null) {
            $config['onLabel'] = $onLabel;
        }

        parent::__construct($config);
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
    public function getSettingsHtml(): ?string
    {
        return
            Cp::lightswitchFieldHtml([
                'label' => Craft::t('app', 'Default Value'),
                'id' => 'default',
                'name' => 'default',
                'on' => $this->default,
            ]) .
            Cp::textFieldHtml([
                'label' => Craft::t('app', 'OFF Label'),
                'instructions' => Craft::t('app', 'The label text to display beside the lightswitch’s disabled state.'),
                'id' => 'off-label',
                'name' => 'offLabel',
                'value' => $this->offLabel,
            ]) .
            Cp::textFieldHtml([
                'label' => Craft::t('app', 'ON Label'),
                'instructions' => Craft::t('app', 'The label text to display beside the lightswitch’s enabled state.'),
                'id' => 'on-label',
                'name' => 'onLabel',
                'value' => $this->onLabel,
            ]);
    }

    /**
     * @inheritdoc
     */
    protected function inputHtml(mixed $value, ?ElementInterface $element = null): string
    {
        $id = $this->getInputId();
        return Craft::$app->getView()->renderTemplate('_includes/forms/lightswitch.twig', [
            'id' => $id,
            'labelId' => $this->getLabelId(),
            'describedBy' => $this->describedBy,
            'name' => $this->handle,
            'on' => (bool)$value,
            'onLabel' => Craft::t('site', $this->onLabel),
            'offLabel' => Craft::t('site', $this->offLabel),
        ]);
    }

    /**
     * @inheritdoc
     */
    public function normalizeValue(mixed $value, ?ElementInterface $element = null): mixed
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
    public function getElementConditionRuleType(): array|string|null
    {
        return LightswitchFieldConditionRule::class;
    }

    /**
     * @inheritdoc
     */
    public function modifyElementsQuery(ElementQueryInterface $query, mixed $value): void
    {
        /** @var ElementQuery $query */
        if ($value === null) {
            return;
        }

        $column = ElementHelper::fieldColumnFromField($this);
        $query->subQuery->andWhere(Db::parseBooleanParam("content.$column", $value, $this->default));
    }

    /**
     * @inheritdoc
     */
    public function getContentGqlType(): Type|array
    {
        return Type::boolean();
    }

    /**
     * @inheritdoc
     * @since 3.5.0
     */
    public function getContentGqlMutationArgumentType(): Type|array
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
    public function getContentGqlQueryArgumentType(): Type|array
    {
        return [
            'name' => $this->handle,
            'type' => Type::boolean(),
        ];
    }

    /**
     * @inheritdoc
     */
    public function getTableAttributeHtml(mixed $value, ElementInterface $element): string
    {
        if (!$value) {
            return '';
        }
        
        $label = $this->onLabel ?: Craft::t('app', 'Enabled');

        return Html::tag('span', '', [
            'class' => 'checkbox-icon',
            'role' => 'img',
            'title' => $label,
            'aria' => [
                'label' => $label,
            ],
        ]);
    }
}
