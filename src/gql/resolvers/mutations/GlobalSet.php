<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\gql\resolvers\mutations;

use Craft;
use craft\gql\base\ElementMutationResolver;
use GraphQL\Type\Definition\ResolveInfo;

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
     * @param $source
     * @param array $arguments
     * @param $context
     * @param ResolveInfo $resolveInfo
     * @return mixed
     * @throws \Throwable if reasons.
     */
    public function saveGlobalSet($source, array $arguments, $context, ResolveInfo $resolveInfo)
    {
        $globalSet = $this->getResolutionData('globalSet');

        $this->requireSchemaAction('globalsets.' . $globalSet->uid, 'edit');

        $globalSet = $this->populateElementWithData($globalSet, $arguments);

        $globalSet = $this->saveElement($globalSet);

        return Craft::$app->getGlobals()->getSetById($globalSet->id);
    }
}
