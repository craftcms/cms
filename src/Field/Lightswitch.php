<?php

declare(strict_types=1);

namespace CraftCms\Cms\Field;

use CraftCms\Cms\Cp\Html\StatusHtml;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Field\Conditions\LightswitchFieldConditionRule;
use CraftCms\Cms\Field\Contracts\CrossSiteCopyableFieldInterface;
use CraftCms\Cms\Field\Contracts\DefaultableFieldInterface;
use CraftCms\Cms\Field\Contracts\InlineEditableFieldInterface;
use CraftCms\Cms\Field\Contracts\MergeableFieldInterface;
use CraftCms\Cms\Field\Contracts\SortableFieldInterface;
use CraftCms\Cms\Form\Contracts\Control;
use CraftCms\Cms\Form\Controls\Lightswitch as LightswitchControl;
use CraftCms\Cms\Form\Controls\Text;
use CraftCms\Cms\Form\Form;
use CraftCms\Cms\Form\FormContext;
use CraftCms\Cms\Form\Nodes\Field as FormField;
use CraftCms\Cms\Shared\Enums\Color as ColorEnum;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Html;
use CraftCms\Cms\Support\Query;
use GraphQL\Type\Definition\Type;
use Illuminate\Contracts\Database\Query\Builder;
use Override;

use function CraftCms\Cms\t;
use function CraftCms\Cms\template;

/**
 * Lightswitch represents a Lightswitch field.
 */
class Lightswitch extends Field implements CrossSiteCopyableFieldInterface, DefaultableFieldInterface, InlineEditableFieldInterface, MergeableFieldInterface, SortableFieldInterface
{
    #[Override]
    public static function displayName(): string
    {
        return t('Lightswitch');
    }

    #[Override]
    public static function icon(): string
    {
        return 'toggle-on';
    }

    #[Override]
    public static function isRequirable(): bool
    {
        return false;
    }

    #[Override]
    public static function phpType(): string
    {
        return 'bool';
    }

    #[Override]
    public static function dbType(): string
    {
        return Query::TYPE_BOOLEAN;
    }

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

    public function __construct($config = [])
    {
        // Config normalization
        if (($onLabel = Arr::pull($config, 'label')) !== null) {
            $config['onLabel'] = $onLabel;
        }

        parent::__construct($config);
    }

    #[Override]
    public function settingsForm(FormContext $context = new FormContext): Form
    {
        return Form::make([
            FormField::make(t('Default Value'))
                ->control(LightswitchControl::make('default')->value($this->default)),
            FormField::make(t('OFF Label'))
                ->instructions(t('The label text to display beside the lightswitch’s disabled state.'))
                ->control(Text::make('offLabel')->value($this->offLabel)),
            FormField::make(t('ON Label'))
                ->instructions(t('The label text to display beside the lightswitch’s enabled state.'))
                ->control(Text::make('onLabel')->value($this->onLabel)),
            FormField::make(t('Show ON/OFF labels in cards'))
                ->instructions(t('Whether card views which include this field should show the custom ON/OFF labels, rather than the field name.'))
                ->control(LightswitchControl::make('showLabelsInCards')->value($this->showLabelsInCards)),
        ]);
    }

    #[Override]
    public function getDefaultValue(): bool
    {
        return $this->default;
    }

    #[Override]
    public function formControl(FieldContext $context): Control
    {
        return LightswitchControl::make($context->path)
            ->onLabel(t($this->onLabel, category: 'site'))
            ->offLabel(t($this->offLabel, category: 'site'))
            ->value((bool) $context->value);
    }

    #[Override]
    protected function inputHtml(mixed $value, ?ElementInterface $element, bool $inline): string
    {
        return $this->_inputHtmlInternal($value, false);
    }

    /**
     * Render html for both static and interactive lightswitch field
     */
    private function _inputHtmlInternal(mixed $value, bool $static): string
    {
        $id = $this->getInputId();

        return template('_includes/forms/lightswitch', [
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

    #[Override]
    public function normalizeValue(mixed $value, ?ElementInterface $element): bool
    {
        // If this is a new entry, look for a default option
        $value ??= $this->default;

        return (bool) $value;
    }

    public function getElementConditionRuleType(): string
    {
        return LightswitchFieldConditionRule::class;
    }

    #[Override]
    public function getContentGqlType(): Type
    {
        return Type::boolean();
    }

    /** @return array{name: string, type: Type, description: string|null} */
    #[Override]
    public function getContentGqlMutationArgumentType(): array
    {
        return [
            'name' => $this->handle,
            'type' => Type::boolean(),
            'description' => $this->instructions,
        ];
    }

    /** @return array{name: string, type: Type} */
    #[Override]
    public function getContentGqlQueryArgumentType(): array
    {
        return [
            'name' => $this->handle,
            'type' => Type::boolean(),
        ];
    }

    #[Override]
    public function getPreviewHtml(mixed $value, ElementInterface $element): string
    {
        $canShowLabel = ($value && $this->onLabel) || (! $value && $this->offLabel);

        if (
            $element->viewMode === 'cards' &&
            (! $this->showLabelsInCards || ! $canShowLabel)
        ) {
            return app(StatusHtml::class)->statusLabelHtml([
                'color' => $value ? ColorEnum::Teal : ColorEnum::Gray,
                'label' => $this->getUiLabel(),
                'icon' => $value ? 'check' : 'xmark',
            ]);
        }

        if (($value && $this->onLabel) || (! $value && $this->offLabel)) {
            return app(StatusHtml::class)->statusLabelHtml([
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

    #[Override]
    public function previewPlaceholderHtml(mixed $value, ?ElementInterface $element): string
    {
        if (! $value) {
            $value = 1;
        }

        return $this->getPreviewHtml($value, $element ?? new Entry(['viewMode' => 'cards']));
    }
}
