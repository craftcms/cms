<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\gql\resolvers\mutations;

use Craft;
use craft\db\Table;
use craft\elements\Asset;
use craft\gql\base\MutationResolver;
use craft\helpers\Db;
use GraphQL\Type\Definition\ResolveInfo;

/**
 * Class DeleteAsset
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.5.0
 */
class DeleteAsset extends MutationResolver
{
    /**
     * @inheritdoc
     */
    public function resolve($source, array $arguments, $context, ResolveInfo $resolveInfo)
    {
        $assetId = $arguments['id'];

        $asset = Asset::findOne($assetId);

        if (!$asset) {
            return true;
        }

        $volumeUid = Db::uidById(Table::VOLUMES, $asset->volumeId);
        $this->requireSchemaAction('volumes.' . $volumeUid, 'delete');

        Craft::$app->getElements()->deleteElementById($assetId);

        return true;
    }
}
