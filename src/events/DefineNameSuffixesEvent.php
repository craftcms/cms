<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\events;

use yii\base\Event;

/**
 * DefineNameSuffixesEvent is used to define the suffixes for the name parser.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.3.0
 */
class DefineNameSuffixesEvent extends Event
{
    /**
     * @var array
     */
    public array $suffixes;
}
