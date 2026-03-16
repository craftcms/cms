<?php

declare(strict_types=1);

namespace CraftCms\Cms\Gql;

use CraftCms\Cms\Component\Component;
use CraftCms\Cms\Gql\Contracts\ArgumentHandlerInterface;
use CraftCms\Cms\Gql\Events\RegisterGqlArgumentHandlers;
use CraftCms\Cms\Gql\Exceptions\GqlException;
use CraftCms\Cms\Gql\Handlers\RelatedAssets;
use CraftCms\Cms\Gql\Handlers\RelatedEntries;
use CraftCms\Cms\Gql\Handlers\RelatedUsers;
use CraftCms\Cms\Gql\Handlers\RelationArgumentHandler;
use CraftCms\Cms\Gql\Handlers\Site;
use CraftCms\Cms\Gql\Handlers\SiteId;
use CraftCms\Cms\Support\Str;

class ArgumentManager extends Component
{
    /**
     * @var array<string,class-string<ArgumentHandlerInterface>|ArgumentHandlerInterface>
     */
    private array $_argumentHandlers = [];

    private bool $_handlersCreated = false;

    public function __construct(array|object $config = [])
    {
        parent::__construct($config);

        $this->_argumentHandlers = [
            'relatedToEntries' => RelatedEntries::class,
            'relatedToAssets' => RelatedAssets::class,
            'relatedToUsers' => RelatedUsers::class,
            'site' => Site::class,
            'siteId' => SiteId::class,
        ];

        event($event = new RegisterGqlArgumentHandlers(handlers: $this->_argumentHandlers));
        $this->_argumentHandlers = $event->handlers;
    }

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

    public function setHandler(string $argumentName, ArgumentHandlerInterface|string $handler): void
    {
        if (is_string($handler)) {
            $handler = $this->createHandler($handler);
        }

        $this->_argumentHandlers[$argumentName] = $handler;
    }

    /**
     * @throws GqlException
     */
    public function prepareArguments(array $arguments): array
    {
        $orderBy = $arguments['orderBy'] ?? null;
        if ($orderBy) {
            $parsedOrderBy = [];

            foreach (str($orderBy)->explode(',') as $chunk) {
                $chunk = trim($chunk);

                // Special case for rand()/random()
                if (in_array(strtolower($chunk), ['rand()', 'random()'], true)) {
                    $parsedOrderBy[] = [$chunk, 'asc'];

                    continue;
                }
                if (
                    Str::contains($chunk, ['(', ')']) ||
                    ! preg_match('/^(\w+(\.\w+)?)( (asc|desc))?$/i', $chunk, $matches)
                ) {
                    throw new GqlException('Illegal value for `orderBy` argument: `'.$orderBy.'`');
                }

                $parsedOrderBy[] = [$matches[1], strtolower($matches[4] ?? 'asc')];
            }

            $arguments['orderBy'] = $parsedOrderBy;
        }

        $this->createHandlers();

        foreach ($this->_argumentHandlers as $argumentName => $handler) {
            if (! empty($arguments[$argumentName]) && $handler instanceof ArgumentHandlerInterface) {
                $arguments = $handler->handleArgumentCollection($arguments);
            }
            // if it's one of the relatedToXYZ arguments,
            // if the value is empty/null unset that arg so that it doesn't go into the criteria
            if (
                array_key_exists($argumentName, $arguments) &&
                empty($arguments[$argumentName]) &&
                $handler instanceof RelationArgumentHandler
            ) {
                unset($arguments[$argumentName]);
            }
        }

        return $arguments;
    }

    protected function createHandler(string $handler): ArgumentHandlerInterface|string
    {
        if (is_a($handler, ArgumentHandlerInterface::class, true)) {
            /** @var ArgumentHandlerInterface $handler */
            $handler = new $handler;
            $handler->setArgumentManager($this);
        }

        return $handler;
    }
}
