<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\gql;

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
     * use craft\events\DefineGqlTypeFields;
     * use craft\gql\TypeManager;
     * use yii\base\Event;
     *
     * Event::on(TypeManager::class, TypeManager::EVENT_DEFINE_GQL_TYPE_FIELDS, function(DefineGqlTypeFields $e) {
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
    const EVENT_DEFINE_GQL_TYPE_FIELDS = 'defineGqlTypeFields';

    /**
     * @var self
     */
    private static $_instance;

    /**
     * @var array A list of definitions already prepared by type name.
     */
    private static $_definitions = [];

    /**
     * Prepare field definitions for a GraphQL type by giving plugins a chance to modify them.
     *
     * @param array $fields
     * @param string $typeName
     * @return mixed
     */
    public static function prepareFieldDefinitions(array $fields, string $typeName)
    {
        // TODO In Craft 4.0, kill all the static in this class to make it more injectable and testable.
        if (!isset(self::$_definitions[$typeName])) {
            $instance = self::$_instance ?? self::$_instance = new self();
            self::$_definitions[$typeName] = $instance->_triggerEvent($fields, $typeName);
        }

        return self::$_definitions[$typeName];
    }

    /**
     * Flush all prepared field definitions.
     */
    public static function flush()
    {
        // TODO looking at you, static method flush.
        self::$_definitions = [];
    }

    /**
     * Actually trigger the event on an instance of the class.
     *
     * @param array $fields
     * @param string $typeName
     * @return array
     */
    private function _triggerEvent(array $fields, string $typeName)
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
