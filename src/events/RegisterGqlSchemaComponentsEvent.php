<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\events;

use yii\base\Event;

/**
 * RegisterGqlSchemaComponentsEvent class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.5.0
 */
class RegisterGqlSchemaComponentsEvent extends Event
{
    /**
     * @var array List of registered GraphQL query components.
     */
    public array $queries = [];

    /**
     * @var array List of registered GraphQL mutation components.
     */
    public array $mutations = [];
}
