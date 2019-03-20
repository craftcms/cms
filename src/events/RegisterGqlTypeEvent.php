<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\events;

use GraphQL\Type\Definition\Type;
use yii\base\Event;

/**
 * RegisterGqlTypeEvent class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.2
 */
class RegisterGqlTypeEvent extends Event
{
    // Properties
    // =========================================================================

    /**
     * @var Type[] List of GQL Type definitions
     */
    public $types = [];
}
