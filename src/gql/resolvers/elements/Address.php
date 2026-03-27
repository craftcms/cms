<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\gql\resolvers\elements;

use Craft;
use craft\db\Query;
use craft\db\Table;
use craft\elements\Address as AddressElement;
use craft\elements\db\AddressQuery;
use craft\elements\db\ElementQuery;
use craft\elements\ElementCollection;
use craft\gql\base\ElementResolver;
use craft\helpers\Gql as GqlHelper;
use Illuminate\Support\Collection;
use yii\base\UnknownMethodException;

/**
 * Class Address
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.0
 */
class Address extends ElementResolver
{
    /**
     * @inheritdoc
     */
    public static function prepareQuery(mixed $source, array $arguments, ?string $fieldName = null): mixed
    {
        // If this is the beginning of a resolver chain, start fresh
        if ($source === null) {
            $query = AddressElement::find();
            $pairs = GqlHelper::extractAllowedEntitiesFromSchema('read');
            $condition = [];

            if (isset($pairs['usergroups'])) {
                $userGroupsService = Craft::$app->getUserGroups();
                $groupIds = array_filter(array_map(
                    fn(string $uid) => $userGroupsService->getGroupByUid($uid)?->id,
                    $pairs['usergroups'],
                ));
                if (!empty($groupIds)) {
                    $condition[] = ['exists', (new Query())
                        ->from(['ugu' => Table::USERGROUPS_USERS])
                        ->where('[[ugu.userId]] = [[addresses.ownerId]]')
                        ->andWhere(['in', 'ugu.groupId', $groupIds]),
                    ];
                }
            }

            if (empty($condition)) {
                return ElementCollection::empty();
            }

            $query->andWhere(['or', ...$condition]);
        } else {
            // If not, get the prepared element query
            /** @var AddressQuery $query */
            $query = $source->$fieldName;
        }

        // If it's preloaded, it's preloaded.
        if (!$query instanceof ElementQuery) {
            return $query;
        }

        foreach ($arguments as $key => $value) {
            try {
                $query->$key($value);
            } catch (UnknownMethodException $e) {
                if ($value !== null) {
                    throw $e;
                }
            }
        }

        if (!GqlHelper::canQueryUsers()) {
            return Collection::empty();
        }

        return $query;
    }
}
