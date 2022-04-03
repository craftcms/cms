<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\events;

use craft\gql\base\SingularTypeInterface;
use yii\base\Event;

/**
 * RegisterGqlTypesEvent class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.3.0
 */
class RegisterGqlTypesEvent extends Event
{
    /**
     * @var string[] List of GraphQL Type definition classes
     * @phpstan-var class-string<SingularTypeInterface>[]
     */
    public array $types = [];
}
