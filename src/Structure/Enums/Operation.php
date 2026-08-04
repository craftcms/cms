<?php

declare(strict_types=1);

namespace CraftCms\Cms\Structure\Enums;

enum Operation
{
    case MakeRoot;
    case PrependTo;
    case AppendTo;
    case InsertBefore;
    case InsertAfter;
    case DeleteWithChildren;
}
