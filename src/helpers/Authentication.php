<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\helpers;

use Craft;
use craft\base\ElementInterface;
use craft\errors\GqlException;
use craft\gql\base\Directive;
use craft\gql\ElementQueryConditionBuilder;
use craft\gql\GqlEntityRegistry;
use craft\models\GqlSchema;
use craft\services\Gql as GqlService;
use GraphQL\Language\AST\ListValueNode;
use GraphQL\Language\AST\ValueNode;
use GraphQL\Language\AST\VariableNode;
use GraphQL\Type\Definition\NonNull;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Definition\UnionType;

/**
 * Class Authentication
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.0
 */
class Authentication
{

}
