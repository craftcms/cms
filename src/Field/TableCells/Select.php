<?php

declare(strict_types=1);

namespace CraftCms\Cms\Field\TableCells;

use CraftCms\Cms\Ui\Controls\Choice;
use CraftCms\Cms\Ui\Controls\Control;
use CraftCms\Cms\Ui\Controls\Table;
use CraftCms\Cms\Ui\Controls\TableColumn;
use CraftCms\Cms\Ui\Nodes\Field;
use CraftCms\Cms\Ui\Ui;
use CraftCms\Cms\Ui\UiContext;

use function CraftCms\Cms\t;

/**
 * @since 6.0.0
 */
class Select extends TableCell
{
    /** @var list<array{label: string, value: string, default?: bool, group?: string}|array{label: string, options: list<array{label: string, value: string, default?: bool}>}> */
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
            ...(isset($option['group']) ? ['group' => t($option['group'], category: 'site', locale: $context->locale)] : []),
        ], TableColumn::options($this->options));

        return Choice::make($context->path)->options($options)->withoutPlaceholder();
    }

    protected function controlValue(TableCellContext $context): mixed
    {
        $options = TableColumn::options($this->options);
        $default = array_find($options, fn (array $option): bool => ! empty($option['default']));

        return $context->value ?? $default['value'] ?? $options[0]['value'] ?? null;
    }

    public function settingsUi(UiContext $context = new UiContext): ?Ui
    {
        return Ui::make([
            Field::make(t('Options'))->control(Table::make('options')
                ->columns([
                    'label' => ['heading' => t('Label'), 'type' => 'singleline'],
                    'value' => ['heading' => t('Value'), 'type' => 'singleline'],
                    'group' => ['heading' => t('Group'), 'type' => 'singleline'],
                    'default' => ['heading' => t('Default'), 'type' => 'checkbox', 'radioMode' => true],
                ])
                ->allowAdd()->allowDelete()->allowReorder()->value(TableColumn::options($this->options))),
        ]);
    }
}
