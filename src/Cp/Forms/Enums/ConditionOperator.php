<?php

declare(strict_types=1);

namespace CraftCms\Cms\Cp\Forms\Enums;

enum ConditionOperator: string
{
    case Equals = 'equals';
    case NotEquals = 'notEquals';
    case LessThan = 'lessThan';
    case LessThanOrEqual = 'lessThanOrEqual';
    case GreaterThan = 'greaterThan';
    case GreaterThanOrEqual = 'greaterThanOrEqual';
    case BeginsWith = 'beginsWith';
    case EndsWith = 'endsWith';
    case Contains = 'contains';
    case In = 'in';
    case NotIn = 'notIn';
    case Empty = 'empty';
    case NotEmpty = 'notEmpty';

    public function requiresValue(): bool
    {
        return ! in_array($this, [self::Empty, self::NotEmpty], true);
    }
}
