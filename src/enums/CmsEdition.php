<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\enums;

use yii\base\InvalidArgumentException;

/**
 * CmsEdition defines all available Craft CMS editions
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 5.0.0
 */
enum CmsEdition: int
{
    /**
     * Returns a Craft CMS edition case by its handle.
     *
     * @param string $handle An edition’s handle
     * @return self The edition case
     * @throws InvalidArgumentException if $handle is invalid
     */
    public static function fromHandle(string $handle): self
    {
        foreach (self::cases() as $case) {
            if ($case->handle() === $handle) {
                return $case;
            }
        }
        throw new InvalidArgumentException("Invalid Craft CMS edition handle: $handle");
    }

    case Solo = 0;
    case Team = 1;
    case Pro = 2;
    /** @since 5.3.0 */
    case Enterprise = 3;

    /**
     * Returns the integer value of the edition.
     *
     * @return string
     */
    public function handle(): string
    {
        return strtolower($this->name);
    }
}
