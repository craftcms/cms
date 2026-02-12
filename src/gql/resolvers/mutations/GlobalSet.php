<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\gql\resolvers\mutations;

use Craft;
use craft\elements\GlobalSet as GlobalSetElement;
use craft\gql\base\ElementMutationResolver;
use GraphQL\Type\Definition\ResolveInfo;
use Throwable;

/**
 * Class SaveGlobalSet
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.5.0
 */
class GlobalSet extends ElementMutationResolver
{
    /**
     * Save the global set identified by resolver data.
     *
     * @param mixed $source
     * @param array $arguments
     * @param mixed $context
     * @param ResolveInfo $resolveInfo
     * @return GlobalSetElement
     * @throws Throwable if reasons.
     */
    public function saveGlobalSet(mixed $source, array $arguments, mixed $context, ResolveInfo $resolveInfo): GlobalSetElement
    {
        $globalSet = $this->getResolutionData('globalSet');

        $this->requireSchemaAction('globalsets.' . $globalSet->uid, 'edit');

        $globalSet = $this->populateElementWithData($globalSet, $arguments, $resolveInfo);

        $globalSet = $this->saveElement($globalSet);

        return Craft::$app->getGlobals()->getSetById($globalSet->id);
    }
}
