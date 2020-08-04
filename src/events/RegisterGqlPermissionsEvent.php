<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\events;

use yii\base\Event;

/**
 * RegisterGqlPermissionsEvent class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.4.0
 * @deprecated in 3.5.0. Use [[RegisterGqlSchemaComponentsEvent]] instead.
 */
class RegisterGqlPermissionsEvent extends Event
{
    /**
     * @var array List of registered GraphQL permissions.
     */
    public $permissions = [];
}
