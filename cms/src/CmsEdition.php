<?php

namespace CraftCms\Cms;

use InvalidArgumentException;

/**
 * CmsEdition defines all available Craft CMS editions
 *
 * @since 6.0.0
 */
enum CmsEdition: int
{
    case Solo = 0;
    case Team = 1;
    case Pro = 2;
    case Enterprise = 3;

    public static function fromHandle(string $handle): self
    {
        foreach (self::cases() as $case) {
            if ($case->handle() === $handle) {
                return $case;
            }
        }
        throw new InvalidArgumentException("Invalid Craft CMS edition handle: $handle");
    }

    public function handle(): string
    {
        return strtolower($this->name);
    }
}
