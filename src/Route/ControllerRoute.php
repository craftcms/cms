<?php

declare(strict_types=1);

namespace CraftCms\Cms\Route;

use CraftCms\Cms\Element\Contracts\ElementInterface;
use Illuminate\Http\Request;
use Illuminate\Pipeline\Pipeline;
use Illuminate\Routing\Route;
use Illuminate\Routing\Router;
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
     * @param  array|string|callable|null  $action
     * @param  array<string, mixed>  $params
     */
    public function __construct(
        public mixed $action,
        public array $params = [],
    ) {}

    public function handle(Request $request, ElementInterface $element): Response
    {
        $router = app(Router::class);
        $route = $router->newRoute(
            methods: 'GET',
            uri: $request->path(),
            action: is_string($this->action)
                ? ['uses' => $this->action]
                : $this->action,
        );

        $route->bind($request);

        foreach ($this->routeParams($element) as $name => $value) {
            $route->setParameter($name, $value);
        }

        $request->setRouteResolver(fn () => $route);

        app()->instance(Route::class, $route);

        $response = new Pipeline(app())
            ->send($request)
            ->through($router->gatherRouteMiddleware($route))
            ->then(fn () => $route->run());

        return $router->prepareResponse($request, $response);
    }

    private function routeParams(ElementInterface $element): array
    {
        $params = ['element' => $element];

        if ($refHandle = $element::refHandle()) {
            $params[$refHandle] = $element;
        }

        return $params + $this->params;
    }
}
