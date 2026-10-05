<?php

declare(strict_types=1);

namespace CraftCms\Cms\Field\TableCells;

use CraftCms\Cms\Form\Controls\Choice;
use CraftCms\Cms\Form\Controls\Control;
use CraftCms\Cms\Form\Controls\Table;
use CraftCms\Cms\Form\Form;
use CraftCms\Cms\Form\FormContext;
use CraftCms\Cms\Form\Nodes\Field;

use function CraftCms\Cms\t;

/**
 * @since 6.0.0
 */
class Select extends TableCell
{
    /** @var list<array{label: string, value: string, default?: bool}> */
    public array $options = [];

    public static function displayName(): string
    {
        return t('Dropdown');
    }

    protected function createControl(TableCellContext $context): Control
    {
        $options = array_map(fn (array $option): array => [
            ...$option,
            'label' => t($option['label'], category: 'site', locale: $context->locale),
        ], $this->options);

        return Choice::make($context->path)->options($options)->withoutPlaceholder();
    }

    protected function controlValue(TableCellContext $context): mixed
    {
        $default = array_find($this->options, fn (array $option): bool => ! empty($option['default']));

        return $context->value ?? $default['value'] ?? $this->options[0]['value'] ?? null;
    }

    public function settingsForm(FormContext $context = new FormContext): ?Form
    {
        return Form::make([
            Field::make(t('Options'))->control(Table::make('options')
                ->columns([
                    'label' => ['heading' => t('Label'), 'type' => 'singleline'],
                    'value' => ['heading' => t('Value'), 'type' => 'singleline'],
                    'default' => ['heading' => t('Default'), 'type' => 'checkbox', 'radioMode' => true],
                ])
                ->allowAdd()->allowDelete()->allowReorder()->value($this->options)),
        ]);
    }
}
