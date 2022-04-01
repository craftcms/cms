<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\gql;

use Craft as Craft;
use craft\base\Component;
use craft\events\DefineGqlTypeFieldsEvent;

/**
 * Class TypeManager
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.4.0
 */
class TypeManager extends Component
{
    /**
     * @event DefineGqlTypeFields The event that is triggered when GraphQL type fields are being prepared.
     *
     * Plugins can use this event to add, remove or modify fields on a given GraphQL type.
     *
     * ---
     * ```php
     * use craft\events\DefineGqlTypeFieldsEvent;
     * use craft\gql\TypeManager;
     * use yii\base\Event;
     *
     * Event::on(TypeManager::class, TypeManager::EVENT_DEFINE_GQL_TYPE_FIELDS, function(DefineGqlTypeFieldsEvent $event) {
     *     // Remove all ids, to enforce use of uids
     *     unset ($event->fields['id']);
     *
     *     // Add author email to all entries
     *     if ($event->typeName == 'EntryInterface') {
     *         $event->fields['authorEmail'] = [
     *             'name' => 'authorEmail',
     *             'type' => Type::string(),
     *             'resolve' => function ($source, array $arguments, $context, ResolveInfo $resolveInfo) {
     *                 // Probably not the smartest idea, though, as this will perform a query for each entry
     *                 return $source->getAuthor()->email;
     *             }
     *         ];
     *     }
     * });
     * ```
     */
    public const EVENT_DEFINE_GQL_TYPE_FIELDS = 'defineGqlTypeFields';

    /**
     * Prepare field definitions for a GraphQL type by giving plugins a chance to modify them.
     *
     * @param array $fields
     * @param string $typeName
     * @return array
     * @deprecated in 4.0.0. Use [[craft\services\Gql::prepareFieldDefinitions()]] instead.
     */
    public static function prepareFieldDefinitions(array $fields, string $typeName): array
    {
        Craft::$app->getDeprecator()->log('TypeManager::prepareFieldDefinitions()', '`TypeManager::prepareFieldDefinitions()` has been deprecated. Use `craft\services\Gql::prepareFieldDefinition()` instead.');
        return Craft::$app->getGql()->prepareFieldDefinitions($fields, $typeName);
    }

    /**
     * Flush all prepared field definitions.
     *
     * @deprecated in 4.0.0. `craft\services\Gql::flushCaches()` should be used instead.
     */
    public static function flush(): void
    {
        Craft::$app->getDeprecator()->log('TypeManager::flush()', '`TypeManager::flush()` has been deprecated and has no effect.');
    }

    /**
     * Register field definitions for a given type and give plugins a chance to modify them.
     *
     * @param array $fields
     * @param string $typeName
     * @return array
     */
    public function registerFieldDefinitions(array $fields, string $typeName): array
    {
        if ($this->hasEventHandlers(self::EVENT_DEFINE_GQL_TYPE_FIELDS)) {
            $event = new DefineGqlTypeFieldsEvent([
                'fields' => $fields,
                'typeName' => $typeName,
            ]);

            $this->trigger(self::EVENT_DEFINE_GQL_TYPE_FIELDS, $event);
            $fields = $event->fields;
        }

        return $fields;
    }
}
