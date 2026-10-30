<?php

declare(strict_types=1);

namespace craft\mcp\enums;

/**
 * @since 5.12.0
 */
enum ConfigurationOperation: string
{
    case List = 'list';
    case Get = 'get';
    case Create = 'create';
    case Update = 'update';
    case Delete = 'delete';

    public function writes(): bool
    {
        return in_array($this, [self::Create, self::Update, self::Delete], true);
    }

    public function needsIdentifier(): bool
    {
        return in_array($this, [self::Get, self::Update, self::Delete], true);
    }
}
