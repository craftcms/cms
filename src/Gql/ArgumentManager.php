<?php

declare(strict_types=1);

namespace CraftCms\Cms\Gql;

use Closure;
use CraftCms\Cms\Component\Component;
use CraftCms\Cms\Gql\Contracts\ArgumentHandlerInterface;
use CraftCms\Cms\Gql\Exceptions\GqlException;
use CraftCms\Cms\Gql\Handlers\RelationArgumentHandler;
use CraftCms\Cms\Support\Str;
use InvalidArgumentException;

class ArgumentManager extends Component
{
    /**
     * @var array<string,class-string<ArgumentHandlerInterface>|Closure|ArgumentHandlerInterface>
     */
    private array $_argumentHandlers = [];

    private bool $_handlersCreated = false;

    public function __construct(array|object $config = [])
    {
        parent::__construct($config);

        $this->_argumentHandlers = app(GqlArguments::class)->handlers()->all();
    }

    protected function createHandlers(): void
    {
        if ($this->_handlersCreated) {
            return;
        }

        foreach ($this->_argumentHandlers as $argumentName => &$handler) {
            $handler = $this->resolveHandler($argumentName, $handler);
        }

        unset($handler);
        $this->_handlersCreated = true;
    }

    public function setHandler(string $argumentName, ArgumentHandlerInterface|string|Closure $handler): void
    {
        $this->_argumentHandlers[$argumentName] = $this->resolveHandler($argumentName, $handler);
    }

    /**
     * @param  array<string, mixed>  $arguments
     * @return array<string, mixed>
     *
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
            return app()->build($handler);
        }

        return $handler;
    }

    /**
     * @param  class-string<ArgumentHandlerInterface>|Closure|ArgumentHandlerInterface  $handler
     */
    private function resolveHandler(string $argumentName, ArgumentHandlerInterface|string|Closure $handler): ArgumentHandlerInterface
    {
        $handler = match (true) {
            $handler instanceof Closure => app()->call($handler, [self::class => $this]),
            is_string($handler) => $this->createHandler($handler),
            default => $handler,
        };

        if (! $handler instanceof ArgumentHandlerInterface) {
            throw new InvalidArgumentException(sprintf(
                'Argument handler [%s] must resolve to an instance of [%s].',
                $argumentName,
                ArgumentHandlerInterface::class,
            ));
        }

        $handler->setArgumentManager($this);

        return $handler;
    }
}
