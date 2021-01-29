<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\gql;

use Craft;
use craft\base\Component;
use craft\events\RegisterGqlArgumentHandlersEvent;
use craft\gql\base\ArgumentHandlerInterface;
use craft\gql\handlers\RelatedAssets;
use craft\gql\handlers\RelatedCategories;
use craft\gql\handlers\RelatedEntries;
use craft\gql\handlers\RelatedTags;
use craft\gql\handlers\RelatedUsers;
use craft\gql\handlers\Site;

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

    private $_argumentHandlers = [];

    private $_handlersCreated = false;

    public function init()
    {
        $handlers = [
            'relatedToEntries' => RelatedEntries::class,
            'relatedToAssets' => RelatedAssets::class,
            'relatedToCategories' => RelatedCategories::class,
            'relatedToTags' => RelatedTags::class,
            'relatedToUsers' => RelatedUsers::class,
            'site' => Site::class
        ];

        $event = new RegisterGqlArgumentHandlersEvent([
            'handlers' => $handlers
        ]);

        $this->trigger(self::EVENT_DEFINE_GQL_ARGUMENT_HANDLERS, $event);

        $this->_argumentHandlers = $event->handlers;
    }

    /**
     * Create all the handlers, if class names were provided.
     */
    protected function createHandlers(): void
    {
        if ($this->_handlersCreated) {
            return;
        }

        foreach ($this->_argumentHandlers as &$handler) {
            // Instantiate in place, if a class name is added.
            if (is_string($handler)) {
                $handler = $this->createHandler($handler);
            }
        }

        unset($handler);
        $this->_handlersCreated = true;
    }

    /**
     * Set the argument handler for an argument name.
     *
     * @param string $argumentName
     * @param string|ArgumentHandlerInterface $handler
     */
    public function setHandler(string $argumentName, $handler): void
    {
        if (is_string($handler)) {
            $handler = $this->createHandler($handler);
        }

        $this->_argumentHandlers[$argumentName] = $handler;
    }

    /**
     * Prepare GraphQL arguments according to the registered argument handlers.
     *
     * @param $arguments
     * @return array
     * @throws \yii\base\InvalidConfigException
     */
    public function prepareArguments($arguments): array
    {
        $this->createHandlers();

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

        foreach ($this->_argumentHandlers as $argumentName => $handler) {
            if (!empty($arguments[$argumentName]) && $handler instanceof ArgumentHandlerInterface) {
                $arguments = $handler->handleArgumentCollection($arguments);
            }
        }

        return $arguments;
    }

    /**
     * @param string $handler
     * @return ArgumentHandlerInterface|string
     */
    protected function createHandler(string $handler)
    {
        if (is_a($handler, ArgumentHandlerInterface::class, true)) {
            /** @var ArgumentHandlerInterface $handler */
            $handler = new $handler();
            $handler->setArgumentManager($this);
        }
        return $handler;
    }
}
