<?php

declare(strict_types=1);

namespace CraftCms\Cms\Field\TableCells;

use CraftCms\Cms\Component\Component;
use CraftCms\Cms\Component\Concerns\ConfigurableComponent;
use CraftCms\Cms\Field\Contracts\TableCellInterface;
use CraftCms\Cms\Ui\Contracts\Control;
use CraftCms\Cms\Ui\Controls\Control as UiControl;
use CraftCms\Cms\Ui\Controls\Text;
use GraphQL\Type\Definition\Type;
use InvalidArgumentException;

/**
 * @since 6.0.0
 */
abstract class TableCell extends Component implements TableCellInterface
{
    use ConfigurableComponent;

    public function uiControl(TableCellContext $context): Control
    {
        return $this->createControl($context)->value($this->controlValue($context));
    }

    protected function createControl(TableCellContext $context): UiControl
    {
        return Text::make($context->path);
    }

    protected function controlValue(TableCellContext $context): mixed
    {
        return $this->serializeValue($context->value);
    }

    public function normalizeValue(mixed $value, bool $fromRequest = false): mixed
    {
        return $this->serializeValue($value);
    }

    public function serializeValue(mixed $value, bool $forDb = false): bool|float|int|string|null
    {
        if ($value !== null && ! is_scalar($value)) {
            throw new InvalidArgumentException('Table cells must serialize to a scalar value or null.');
        }

        return $value;
    }

    public function getValueRules(): array
    {
        return [];
    }

    public function searchKeywords(mixed $value): string
    {
        return (string) $this->serializeValue($value);
    }

    public function gqlType(): Type
    {
        return Type::string();
    }

    public function gqlInputType(): Type
    {
        return $this->gqlType();
    }
}
