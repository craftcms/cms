<?php


namespace craft\gql\base;

use craft\base\Component;
use craft\events\GqlSchemaDefFieldsEvent;
use GraphQL\Type\Definition\Type;

/**
 * Class SchemaDefFieldsEventHandler
 * @package craft\gql\base
 */
class SchemaDefFieldsEventProxy extends Component
{
    // Constants
    // =========================================================================

    /**
     * @event schemaDefFieldsEvent The event that is triggered when Gql Types have
     * completed defining their Craft-internally-defined fields.
     *
     * This event follows a usage pattern in Craft plugins etc. similar to others, but is
     * triggered through this proxy, SchemaDefFieldsEventProxy, so that its intended use
     * can be possible from the Gql Element interfaces, which are all defined by traits
     * and static functions, not methods of constructed objects.
     *
     * The job of the actual Event handler is simply to pass back a table, a nested array
     * which identifies any replacements or additions to properties for fields of Types.
     * A /config php file may in some cases be an attractive place to actually put this
     * config, as it can contain closures, as for the resolve(r) items when used.
     *
     * The `employ` method takes care of triggering the event, then appropriately merges
     * for targetted fields in the currently built Type, returning the upgraded field
     * definitions. It's used to complete each of the Element Interfaces for Gql inside
     * of Craft, just modifying the static lists in these before they're returned.
     * ---
     * ```php
     * use craft\gql\base\SchemaDefFieldsEventProxy;
     * use craft\events\GqlSchemaDefFieldsEvent;
     * use yii\base\Event;
     *
     * Event::on(SchemaDefFieldsEventProxy::class,
     *     SchemaDefFieldsEventProxy::EVENT_SCHEMA_DEF_FIELDS,
     *     function(GqlSchemaDefFieldsEvent $event) {
     *         $event->fieldsToChange = [
     *             'Asset' => [  // the Element Type Name (only)
     *                  'url' => [  // one of its fields
     *                      'args' => [
     *                           'transform' => Type::string()
     *                       ],
     *                       'resolve' => function ($source, $args, $context, $info) {
     *                           if (\array_key_exists('transform', $args)) {
     *                               return $source->getUrl($args['transform']);
     *
     *                               // other argument keys will get other resolutions, after
     *                               // which this back-out normally to parent for possible
     *                               // directive resolve, or failing that, appropriate default
     *                               // for the Element and Field Type we're modifying
     *
     *                           } else if (\is_array($resolve = $info->parentType->resolveFieldFn)) {
     *                               return $resolve ($source, $args, $context, $info);
     *                           } else {
     *                               return $source->getUrl();
     *                           }
     *                       },
     *                   ]
     *               ]
     *          ];
     *     }
     * );
     * ```
     */
    const EVENT_SCHEMA_DEF_FIELDS = 'schemaDefFieldsEvent';

    // Methods
    // =========================================================================

    /**
     * @param $type
     * @param $fields
     * @return GqlSchemaDefFieldsEvent
     */
    public function employ(string $typeInSchema, array $fields) : array
    {
        // Create and Fire a 'schemaDefFields' event

        $event = new GqlSchemaDefFieldsEvent([
            'typeInSchema' => $typeInSchema,
            'fields' => $fields,
            'fieldsToChange' => []
        ]);

        if (self::hasEventHandlers(self::EVENT_SCHEMA_DEF_FIELDS)) {
            self::trigger(self::EVENT_SCHEMA_DEF_FIELDS, $event);
        }

        // now change any fields that have modified definitions available
        // we take care of any original defs that will remain, making the app event handler easy

        $elementType = str_replace('Interface', '', $typeInSchema);

        // it seems simple, but with care here...array_merge is phpish-tricky
        // also, not impossible some validation could be worth the effort, as Exceptions
        // from mis-constructed $modDefs can be inscrutable, concern internals points

        // n.b. this appears to be sufficient to allow both directives and configured
        // field resolvers to operate for fields, with both kinds of queries on the
        // same server -- given there are not as-yet unseen cases.

        if (\array_key_exists($elementType, $event->fieldsToChange)) {
            foreach ($event->fieldsToChange[$elementType] as $modName => $modDefs) {

                // assure any prior arguments are retained, as for directives
                $modDefs['args'] = \array_merge($fields[$modName]['args'], $modDefs['args']);

                // then merge including our field args and resolver for them
                $fields[$modName] = \array_merge($fields[$modName], $modDefs);
            }
        }

        return $fields;
    }
}
