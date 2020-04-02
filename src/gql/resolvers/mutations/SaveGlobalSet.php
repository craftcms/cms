<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\gql\resolvers\mutations;

use craft\elements\GlobalSet;
use craft\gql\base\MutationResolver;
use GraphQL\Type\Definition\ResolveInfo;

/**
 * Class SaveGlobalSet
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.5.0
 */
class SaveGlobalSet extends MutationResolver
{

    /**
     * @inheritdoc
     */
    public function resolve($source, array $arguments, $context, ResolveInfo $resolveInfo)
    {
        $globalSet = $this->_getData('globalSet');

        $this->requireSchemaAction('globalsets.' . $globalSet->uid, 'edit');

        $globalSet = $this->populateElementWithData($globalSet, $arguments);

        $this->saveElement($globalSet);

        return GlobalSet::find()->anyStatus()->id($globalSet->id)->one();
    }
}
