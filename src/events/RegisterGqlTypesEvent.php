<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\events;

use yii\base\Event;

/**
 * RegisterGqlTypeEvent class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.2
 */
class RegisterGqlTypesEvent extends Event
{
    // Properties
    // =========================================================================

    /**
     * @var array List of GQL Type definition classes
     */
    public $types = [];
}
