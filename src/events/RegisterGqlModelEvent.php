<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\events;

use yii\base\Event;

/**
 * RegisterGqlModelEvent class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.2
 */
class RegisterGqlModelEvent extends Event
{
    // Properties
    // =========================================================================

    /**
     * @var string[] List of model classes that support GraphQL
     */
    public $models = [];
}
