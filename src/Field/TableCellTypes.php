<?php

declare(strict_types=1);

namespace CraftCms\Cms\Field;

use CraftCms\Cms\Component\ComponentHelper;
use CraftCms\Cms\Component\Exceptions\MissingComponentException;
use CraftCms\Cms\Component\TypeRegistry;
use CraftCms\Cms\Field\Contracts\TableCellInterface;
use CraftCms\Cms\Field\TableCells\Checkbox;
use CraftCms\Cms\Field\TableCells\Color;
use CraftCms\Cms\Field\TableCells\Date;
use CraftCms\Cms\Field\TableCells\Email;
use CraftCms\Cms\Field\TableCells\Heading;
use CraftCms\Cms\Field\TableCells\Lightswitch;
use CraftCms\Cms\Field\TableCells\MissingTableCell;
use CraftCms\Cms\Field\TableCells\Multiline;
use CraftCms\Cms\Field\TableCells\Number;
use CraftCms\Cms\Field\TableCells\Select;
use CraftCms\Cms\Field\TableCells\Singleline;
use CraftCms\Cms\Field\TableCells\Time;
use CraftCms\Cms\Field\TableCells\Url;
use Illuminate\Container\Attributes\Singleton;

/**
 * @extends TypeRegistry<TableCellInterface>
 *
 * @since 6.0.0
 */
#[Singleton]
class TableCellTypes extends TypeRegistry
{
    protected const string CONTRACT = TableCellInterface::class;

    private const array BUILTIN_TYPES = [
        'checkbox' => Checkbox::class,
        'color' => Color::class,
        'date' => Date::class,
        'select' => Select::class,
        'email' => Email::class,
        'heading' => Heading::class,
        'lightswitch' => Lightswitch::class,
        'multiline' => Multiline::class,
        'number' => Number::class,
        'singleline' => Singleline::class,
        'time' => Time::class,
        'url' => Url::class,
    ];

    public function __construct()
    {
        $this->register(...array_values(self::BUILTIN_TYPES));
    }

    protected function identity(string $type): string
    {
        return array_search($type, self::BUILTIN_TYPES, true) ?: $type;
    }

    /** @return array<string, class-string<TableCellInterface>> */
    public function selectableTypes(): array
    {
        return $this->typesByIdentity()
            ->filter(fn (string $type): bool => $type::isSelectable() && ComponentHelper::validateComponentClass($type, TableCellInterface::class))
            ->all();
    }

    /** @param array<string, mixed> $column */
    public function create(array $column): TableCellInterface
    {
        $identity = $column['type'];
        $type = $this->typeByIdentity($identity);
        $settings = $column['settings'] ?? [];
        $opaqueSettings = array_diff_key($column, array_flip(['heading', 'handle', 'width', 'type']));
        if ($identity === 'select' && isset($column['options'])) {
            $settings['options'] = $column['options'];
        }

        if ($type === null) {
            return new MissingTableCell([
                'expectedType' => $identity,
                'settings' => $opaqueSettings,
            ]);
        }

        try {
            $cell = ComponentHelper::createComponent($type, TableCellInterface::class);
            $attributes = $cell->settingsAttributes();
            $settings = array_intersect_key($settings, array_flip($attributes));
            foreach ($attributes as $attribute) {
                if (array_key_exists($attribute, $column)) {
                    $settings[$attribute] = $column[$attribute];
                }
            }

            return ComponentHelper::createComponent(['type' => $type, 'settings' => $settings], TableCellInterface::class);
        } catch (MissingComponentException $exception) {
            return new MissingTableCell([
                'expectedType' => $type,
                'errorMessage' => $exception->getMessage(),
                'settings' => $opaqueSettings,
            ]);
        }
    }
}
