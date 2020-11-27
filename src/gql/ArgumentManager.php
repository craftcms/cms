<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\gql;

use Craft;
use craft\base\Component;
use craft\events\DefineGqlTypeFieldsEvent;
use craft\events\RegisterGqlArgumentHandlersEvent;
use craft\gql\base\ArgumentHandlerInterface;
use craft\gql\handlers\RelatedAssets;
use craft\gql\handlers\RelatedCategories;
use craft\gql\handlers\RelatedEntries;
use craft\gql\handlers\RelatedTags;
use craft\gql\handlers\RelatedUsers;

/**
 * Class ArgumentManager
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.6.0
 */
class ArgumentManager extends Component
{
    /**
     * @event RegisterGqlArgumentHandlers The event that is triggered when GraphQL argument are being handled.
     *
     * Plugins can use this event to add, remove or modify arguments on a given GraphQL type.
     *
     * ---
     * ```php
     * use craft\events\RegisterGqlArgumentHandlers;
     * use craft\gql\ArgumentManager;
     * use my/namespace/gql/handlers/RedirectHandler
     * use yii\base\Event;
     *
     * Event::on(ArgumentManager::class, ArgumentManager::EVENT_DEFINE_GQL_ARGUMENT_HANDLERS, function(RegisterGqlArgumentHandlersEvent $event) {
     *      // Register a handler that works on the `redirect` argument.
     *      $event->handlers['redirect'] = RedirectHandler::class;
     *     };
     * });
     * ```
     */
    const EVENT_DEFINE_GQL_ARGUMENT_HANDLERS = 'defineGqlArgumentHandlers';

    private $_argumentHandlerClassers = [];

    private $_argumentHandlers = [];

    public function init()
    {
        $handlers = [
            'relatedEntries' => RelatedEntries::class,
            'relatedAssets' => RelatedAssets::class,
            'relatedCategories' => RelatedCategories::class,
            'relatedTags' => RelatedTags::class,
            'relatedUsers' => RelatedUsers::class,
        ];

        $event= new RegisterGqlArgumentHandlersEvent([
            'handlers' => $handlers
        ]);

        $this->trigger(self::EVENT_DEFINE_GQL_ARGUMENT_HANDLERS, $event);

        $this->_argumentHandlerClassers = $event->handlers;
    }

    /**
     * Prepare GraphQL arguments according to the registered argument handlers.
     *
     * @param $arguments
     * @return array|mixed
     * @throws \yii\base\InvalidConfigException
     */
    public function prepareArguments($arguments) {
        // TODO remove in Craft 4.1
        if (isset($arguments['relatedToAll'])) {
            Craft::$app->getDeprecator()->log('graphql.arguments.relatedToAll', 'The `relatedToAll` argument has been deprecated. Use the `relatedTo` argument with the `["and", ...ids]` syntax instead.');
            $ids = (array)$arguments['relatedToAll'];
            $ids = array_map(function($value) {
                return ['element' => $value];
            }, $ids);
            $arguments['relatedTo'] = array_merge(['and'], $ids);
            unset($arguments['relatedToAll']);
        }

        foreach ($this->_argumentHandlerClassers as $argumentName => $handlerClass) {
            if (!empty($arguments[$argumentName])) {
                if (empty($this->_argumentHandlers[$handlerClass])) {
                    $this->_argumentHandlers[$handlerClass] = is_object($handlerClass) ? $handlerClass : Craft::createObject($handlerClass);
                }

                $handler = $this->_argumentHandlers[$handlerClass];

                if ($handler instanceof ArgumentHandlerInterface) {
                    $arguments = $handler->handleArgumentCollection($arguments);
                }
            }
        }

        return $arguments;
    }
}
