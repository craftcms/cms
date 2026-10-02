<?php

declare(strict_types=1);

namespace CraftCms\Cms\Route;

use CraftCms\Cms\Element\Contracts\ElementInterface;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Illuminate\Routing\Router;
use InvalidArgumentException;
use ReflectionMethod;
use Symfony\Component\HttpFoundation\Response;

/**
 * @since 6.0.0
 */
readonly class ElementRoute
{
    public string $destination;

    public function __construct(string $destination)
    {
        $this->destination = self::normalize($destination);

        if (! self::isValid($this->destination)) {
            throw new InvalidArgumentException("Invalid element route destination: {$destination}");
        }
    }

    public static function normalize(string $destination): string
    {
        $destination = ltrim(trim($destination), '\\');
        $destination = preg_replace('/::class(?=$|@|::)/i', '', $destination);

        return str_replace('::', '@', $destination);
    }

    public static function isValid(string $destination): bool
    {
        $destination = self::normalize($destination);

        if (! str_contains($destination, '\\') && ! str_contains($destination, '@')) {
            return (bool) preg_match('/^[a-zA-Z0-9_.-]+$/D', $destination);
        }

        return (bool) preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*(?:\\\\[a-zA-Z_][a-zA-Z0-9_]*)+(?:@[a-zA-Z_][a-zA-Z0-9_]*)?$/D', $destination);
    }

    private function isController(): bool
    {
        return str_contains($this->destination, '\\');
    }

    private function action(): string
    {
        return str_contains($this->destination, '@')
            ? $this->destination
            : "{$this->destination}@__invoke";
    }

    public function matches(Route $route): bool
    {
        if (! $this->isController()) {
            return $route->getName() === $this->destination;
        }

        $action = ltrim($route->getActionName(), '\\');

        if (! str_contains($action, '@')) {
            $action .= '@__invoke';
        }

        return strcasecmp($action, $this->action()) === 0;
    }

    public function handle(Request $request, ElementInterface $element): Response
    {
        $router = app(Router::class);

        if (! $this->isController()) {
            if (! $router->getRoutes()->getByName($this->destination)) {
                throw new InvalidArgumentException("Named element route {$this->destination} does not exist.");
            }

            throw new InvalidArgumentException("Named element route {$this->destination} does not match the entry URL. Its URI, domain, and HTTP methods must match the request.");
        }

        $action = $this->action();

        foreach ($router->getRoutes()->getRoutes() as $route) {
            if ($this->matches($route)) {
                throw new InvalidArgumentException("Registered element route action {$action} does not match the entry URL.");
            }
        }

        [$controller, $method] = explode('@', $action, 2);

        if (! class_exists($controller) || ! method_exists($controller, $method) || ! new ReflectionMethod($controller, $method)->isPublic()) {
            throw new InvalidArgumentException("Element route controller action {$action} does not exist or is not public.");
        }

        return new ControllerRoute([$controller, $method])->handle($request, $element);
    }
}
