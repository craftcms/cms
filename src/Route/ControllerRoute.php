<?php

declare(strict_types=1);

namespace CraftCms\Cms\Route;

use CraftCms\Cms\Element\Contracts\ElementInterface;
use Illuminate\Container\Container;
use Illuminate\Http\Request;
use Illuminate\Routing\Contracts\ControllerDispatcher;
use Illuminate\Routing\Router;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\Response;

class ControllerRoute
{
    /**
     * Create a new controller route.
     *
     * The action should match Laravel's route action value, such as an
     * invokable controller class or [Controller::class, 'method'].
     * Params are added as route parameters before handling.
     *
     * @param  class-string|array{class-string, string}  $action
     * @param  array<string, mixed>  $params
     */
    public function __construct(
        public array|string $action,
        public array $params = [],
    ) {}

    public function handle(Request $request, ElementInterface $element): Response
    {
        $route = $request->route();

        if (! $route) {
            throw new InvalidArgumentException('Controller site routes require a routed HTTP request.');
        }

        [$controller, $method] = match (true) {
            is_string($this->action) => [app(Container::class)->make($this->action), '__invoke'],
            is_array($this->action) && count($this->action) === 2 && is_string($this->action[0]) && is_string($this->action[1]) => [
                app(Container::class)->make($this->action[0]),
                $this->action[1],
            ],
            default => throw new InvalidArgumentException('Controller site routes must use an invokable controller or [controller, method].'),
        };

        $route->setParameter('element', $element);

        foreach ($this->params as $name => $value) {
            $route->setParameter($name, $value);
        }

        return Router::toResponse(
            $request,
            app(ControllerDispatcher::class)->dispatch($route, $controller, $method),
        );
    }
}
