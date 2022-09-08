<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\events;

use yii\base\Event;

/**
 * DefineLastNamePrefixesEvent is used to define the last name prefixes for the name parser.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.3.0
 */
class DefineLastNamePrefixesEvent extends Event
{
    /**
     * @var array Last name prefixes.
     */
    public array $lastNamePrefixes;
}
