<?php

declare(strict_types=1);

namespace CraftCms\Cms\Field\Contracts;

use CraftCms\Cms\Component\Contracts\ComponentInterface;
use CraftCms\Cms\Component\Contracts\ConfigurableComponentInterface;
use CraftCms\Cms\Field\TableCells\TableCellContext;
use CraftCms\Cms\Ui\Contracts\Control;
use CraftCms\Cms\Validation\Contracts\Validatable;
use GraphQL\Type\Definition\Type;

interface TableCellInterface extends ComponentInterface, ConfigurableComponentInterface, Validatable
{
    public function formControl(TableCellContext $context): Control;

    public function normalizeValue(mixed $value, bool $fromRequest = false): mixed;

    public function serializeValue(mixed $value, bool $forDb = false): bool|float|int|string|null;

    /** @return list<mixed> */
    public function getValueRules(): array;

    public function searchKeywords(mixed $value): string;

    public function gqlType(): Type;

    public function gqlInputType(): Type;
}
