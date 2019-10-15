<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\events;

use yii\base\Event;

/**
 * Graphql after execute event class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.3
 */
class GraphqlAfterExecuteEvent extends Event
{
    // Properties
    // =========================================================================

    /**
     * @var array The results of the GraphQL execute command.
     */
    public $result;
}
