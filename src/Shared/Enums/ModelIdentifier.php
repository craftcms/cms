<?php

declare(strict_types=1);

namespace CraftCms\Cms\Shared\Enums;

use CraftCms\Cms\Support\Str;

/**
 * ModelIdentifier defines the kinds of values a model can be referenced by.
 *
 * @since 6.0.0
 */
enum ModelIdentifier
{
    case Id;
    case Uid;
    case Handle;

    /**
     * Returns the kind of identifier the value is, or null if it isn't one of the allowed kinds.
     *
     * Handles can't contain hyphens, so a UUID-shaped string is always a UID.
     *
     * @param  ModelIdentifier[]|null  $allowed
     */
    public static function detect(mixed $value, ?array $allowed = null): ?self
    {
        $identifier = match (true) {
            is_int($value), is_string($value) && ctype_digit($value) => self::Id,
            is_string($value) && Str::isUuid($value) => self::Uid,
            is_string($value) && $value !== '' => self::Handle,
            default => null,
        };

        if ($identifier === null || ($allowed !== null && ! in_array($identifier, $allowed, true))) {
            return null;
        }

        return $identifier;
    }
}
