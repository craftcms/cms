<?php

declare(strict_types=1);

namespace CraftCms\Cms\Structure\Data;

use CraftCms\Cms\Structure\Models\StructureElement;

readonly class Operation
{
    public const string MakeRoot = 'makeRoot';

    public const string PrependTo = 'prependTo';

    public const string AppendTo = 'appendTo';

    public const string InsertBefore = 'insertBefore';

    public const string InsertAfter = 'insertAfter';

    public const string Remove = 'remove';

    public const string DeleteWithChildren = 'deleteWithChildren';

    private function __construct(
        public string $type,
        public ?StructureElement $target = null,
    ) {}

    public static function makeRoot(): self
    {
        return new self(self::MakeRoot);
    }

    public static function prependTo(StructureElement $target): self
    {
        return new self(self::PrependTo, $target);
    }

    public static function appendTo(StructureElement $target): self
    {
        return new self(self::AppendTo, $target);
    }

    public static function insertBefore(StructureElement $target): self
    {
        return new self(self::InsertBefore, $target);
    }

    public static function insertAfter(StructureElement $target): self
    {
        return new self(self::InsertAfter, $target);
    }

    public static function remove(): self
    {
        return new self(self::Remove);
    }

    public static function deleteWithChildren(): self
    {
        return new self(self::DeleteWithChildren);
    }
}
