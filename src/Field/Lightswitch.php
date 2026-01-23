<?php

declare(strict_types=1);

namespace CraftCms\Cms\Field;

use Craft;
use craft\base\ElementInterface;
use craft\fields\conditions\LightswitchFieldConditionRule;
use craft\helpers\Cp;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Field\Contracts\CrossSiteCopyableFieldInterface;
use CraftCms\Cms\Field\Contracts\InlineEditableFieldInterface;
use CraftCms\Cms\Field\Contracts\MergeableFieldInterface;
use CraftCms\Cms\Field\Contracts\SortableFieldInterface;
use CraftCms\Cms\Shared\Enums\Color as ColorEnum;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Html;
use CraftCms\Cms\Support\Query;
use GraphQL\Type\Definition\Type;
use Illuminate\Contracts\Database\Query\Builder;
use Override;
use yii\db\Schema;

use function CraftCms\Cms\t;

/**
 * Lightswitch represents a Lightswitch field.
 */
final class Lightswitch extends Field implements CrossSiteCopyableFieldInterface, InlineEditableFieldInterface, MergeableFieldInterface, SortableFieldInterface
{
    /**
     * {@inheritdoc}
     */
    #[Override]
    public static function displayName(): string
    {
        return t('Lightswitch');
    }

    /**
     * {@inheritdoc}
     */
    #[Override]
    public static function icon(): string
    {
        return 'toggle-on';
    }

    /**
     * {@inheritdoc}
     */
    #[Override]
    public static function isRequirable(): bool
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    #[Override]
    public static function phpType(): string
    {
        return 'bool';
    }

    /**
     * {@inheritdoc}
     */
    #[Override]
    public static function dbType(): string
    {
        return Schema::TYPE_BOOLEAN;
    }

    /**
     * {@inheritdoc}
     */
    #[Override]
    public static function modifyQuery(Builder $query, array $instances, mixed $value): Builder
    {
        $valueSql = self::valueSql($instances);
        $strict = false;

        if (is_array($value) && isset($value['value'])) {
            $strict = $value['strict'] ?? $strict;
            $value = $value['value'];
        }

        $defaultValue = $strict ? null : $instances[0]->default;

        return $query->whereBooleanParam($valueSql, $value, $defaultValue, Query::TYPE_JSON);
    }

    /**
     * @var bool Whether the lightswitch should be enabled by default
     */
    public bool $default = false;

    /**
     * @var string|null The label text to display beside the lightswitch’s enabled state
     */
    public ?string $onLabel = null;

    /**
     * @var string|null The label text to display beside the lightswitch’s disabled state
     */
    public ?string $offLabel = null;

    /**
     * @var bool Whether card views which include this field should show the custom ON/OFF labels, rather than the field name.
     */
    public bool $showLabelsInCards = false;

    /**
     * {@inheritdoc}
     */
    public function __construct($config = [])
    {
        // Config normalization
        if (($onLabel = Arr::pull($config, 'label')) !== null) {
            $config['onLabel'] = $onLabel;
        }

        parent::__construct($config);
    }

    /**
     * {@inheritdoc}
     */
    public function getSettingsHtml(): string
    {
        return $this->settingsHtml(false);
    }

    /**
     * {@inheritdoc}
     */
    public function getReadOnlySettingsHtml(): string
    {
        return $this->settingsHtml(true);
    }

    private function settingsHtml(bool $readOnly): string
    {
        return
            Cp::lightswitchFieldHtml([
                'label' => t('Default Value'),
                'id' => 'default',
                'name' => 'default',
                'on' => $this->default,
                'disabled' => $readOnly,
            ]).
            Cp::textFieldHtml([
                'label' => t('OFF Label'),
                'instructions' => t('The label text to display beside the lightswitch’s disabled state.'),
                'id' => 'off-label',
                'name' => 'offLabel',
                'value' => $this->offLabel,
                'disabled' => $readOnly,
            ]).
            Cp::textFieldHtml([
                'label' => t('ON Label'),
                'instructions' => t('The label text to display beside the lightswitch’s enabled state.'),
                'id' => 'on-label',
                'name' => 'onLabel',
                'value' => $this->onLabel,
                'disabled' => $readOnly,
            ]).
            Cp::lightswitchFieldHtml([
                'label' => t('Show ON/OFF labels in cards'),
                'instructions' => t('Whether card views which include this field should show the custom ON/OFF labels, rather than the field name.'),
                'id' => 'show-labels-in-cards',
                'name' => 'showLabelsInCards',
                'on' => $this->showLabelsInCards,
                'disabled' => $readOnly,
            ]);
    }

    /**
     * {@inheritdoc}
     */
    #[Override]
    protected function inputHtml(mixed $value, ?ElementInterface $element, bool $inline): string
    {
        return $this->_inputHtmlInternal($value, false);
    }

    /**
     * {@inheritdoc}
     */
    #[Override]
    public function getStaticHtml(mixed $value, ?ElementInterface $element = null): string
    {
        return $this->_inputHtmlInternal($value, true);
    }

    /**
     * Render html for both static and interactive lightswitch field
     */
    private function _inputHtmlInternal(mixed $value, bool $static): string
    {
        $id = $this->getInputId();

        return Craft::$app->getView()->renderTemplate('_includes/forms/lightswitch.twig', [
            'id' => $id,
            'labelId' => $this->getLabelId(),
            'describedBy' => $this->describedBy,
            'name' => $this->handle,
            'on' => (bool) $value,
            'onLabel' => t($this->onLabel, category: 'site'),
            'offLabel' => t($this->offLabel, category: 'site'),
            'disabled' => $static,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    #[Override]
    public function normalizeValue(mixed $value, ?ElementInterface $element): bool
    {
        // If this is a new entry, look for a default option
        if ($value === null) {
            $value = $this->default;
        }

        return (bool) $value;
    }

    /**
     * {@inheritdoc}
     */
    public function getElementConditionRuleType(): string
    {
        return LightswitchFieldConditionRule::class;
    }

    /**
     * {@inheritdoc}
     */
    #[Override]
    public function getContentGqlType(): Type
    {
        return Type::boolean();
    }

    /**
     * {@inheritdoc}
     */
    #[Override]
    public function getContentGqlMutationArgumentType(): array
    {
        return [
            'name' => $this->handle,
            'type' => Type::boolean(),
            'description' => $this->instructions,
        ];
    }

    /**
     * {@inheritdoc}
     */
    #[Override]
    public function getContentGqlQueryArgumentType(): array
    {
        return [
            'name' => $this->handle,
            'type' => Type::boolean(),
        ];
    }

    /**
     * {@inheritdoc}
     */
    #[Override]
    public function getPreviewHtml(mixed $value, ElementInterface $element): string
    {
        $canShowLabel = ($value && $this->onLabel) || (! $value && $this->offLabel);

        if (
            $element->viewMode === 'cards' &&
            (! $this->showLabelsInCards || ! $canShowLabel)
        ) {
            return Cp::statusLabelHtml([
                'color' => $value ? ColorEnum::Teal : ColorEnum::Gray,
                'label' => $this->getUiLabel(),
                'icon' => $value ? 'check' : 'xmark',
            ]);
        }

        if (($value && $this->onLabel) || (! $value && $this->offLabel)) {
            return Cp::statusLabelHtml([
                'color' => $value ? ColorEnum::Teal : ColorEnum::Gray,
                'label' => t($value ? $this->onLabel : $this->offLabel, category: 'site'),
                'icon' => $value ? 'check' : 'xmark',
            ]);
        }

        if (! $value) {
            return '';
        }

        $label = $this->onLabel ? t($this->onLabel, category: 'site') : t('Enabled');

        return
            Html::tag('span', '', [
                'class' => 'checkbox-icon',
                'role' => 'img',
                'title' => $label,
                'aria' => [
                    'label' => $label,
                ],
            ]).
            Html::tag('span', $this->getUiLabel(), [
                'class' => 'checkbox-preview-label',
            ]);
    }

    /**
     * {@inheritdoc}
     */
    #[Override]
    public function previewPlaceholderHtml(mixed $value, ?ElementInterface $element): string
    {
        if (! $value) {
            $value = 1;
        }

        return $this->getPreviewHtml($value, $element ?? new Entry(['viewMode' => 'cards']));
    }
}
