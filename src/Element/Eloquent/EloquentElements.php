<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Eloquent;

use CraftCms\Cms\Cp\Data\NavItem;
use CraftCms\Cms\Element\ElementServiceProvider;
use CraftCms\Cms\Element\ElementTypes;
use CraftCms\Cms\Field\FieldTypes;
use CraftCms\Cms\Http\Controllers\Elements\EditElementController;
use CraftCms\Cms\Http\Controllers\Elements\ElementTypeIndexController;
use CraftCms\Cms\Http\Controllers\Elements\NewElementController;
use CraftCms\Cms\Http\Middleware\AuthorizeEloquentElementIndex;
use CraftCms\Cms\Route\Routes;
use CraftCms\Cms\Support\Str;
use CraftCms\Cms\Support\Url;
use CraftCms\Cms\Twig\Variables\CraftVariable;
use CraftCms\Cms\User\UserPermissions;
use Illuminate\Container\Attributes\Singleton;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Routing\Router;
use InvalidArgumentException;
use Symfony\Component\HttpKernel\Exception\HttpException;

use function CraftCms\Cms\currentUser;

/**
 * Registers {@see EloquentElement} types, wiring up everything they need in one call.
 *
 * Registering a type registers it as an element type, keeps it in step with
 * writes the app makes to its model ({@see ModelSync}), and gives it a control
 * panel index at {@see EloquentElement::cpIndexUri()}, `/new` and `/{id}` routes
 * beneath it for creating and editing elements, and a nav item from
 * {@see EloquentElement::cpNavItem()}, registers its Craft permissions
 * ({@see EloquentElement::permissionGroup()}), and lets templates query it
 * through `craft.customers()` — the plural of its handle, camel-cased. The index and nav item are shown
 * to users {@see EloquentElement::canViewIndex()} allows. It also registers the type's
 * {@see EloquentElement::relationFieldType()}, if it has one.
 *
 * ```php
 * public function boot(EloquentElements $eloquentElements): void
 * {
 *     $eloquentElements->register(Customer::class);
 * }
 * ```
 *
 * @since 6.0.0
 */
#[Singleton]
class EloquentElements
{
    /** @var array<class-string<EloquentElement<covariant Model>>, true> */
    private array $types = [];

    private bool $craftBooted = false;

    public function __construct(
        private readonly Application $app,
        private readonly ElementTypes $elementTypes,
        private readonly FieldTypes $fieldTypes,
        private readonly ModelSync $modelSync,
    ) {}

    /** @param class-string<EloquentElement<covariant Model>> ...$elementTypes */
    public function register(string ...$elementTypes): void
    {
        $this->elementTypes->register(...$elementTypes);

        foreach ($elementTypes as $elementType) {
            if (isset($this->types[$elementType])) {
                continue;
            }

            $this->modelSync->observe($elementType);
            $this->app->make(UserPermissions::class)->registerPermissionGroup(
                $elementType::permissionNamespace(),
                $elementType::permissionGroup(...),
            );
            $this->registerIndexRoute($elementType);
            $this->registerTemplateQuery($elementType);

            if (($fieldType = $elementType::relationFieldType()) !== null) {
                $this->fieldTypes->register($fieldType);
            }

            $this->types[$elementType] = true;
        }
    }

    /** @return list<class-string<EloquentElement<covariant Model>>> */
    public function types(): array
    {
        return array_keys($this->types);
    }

    /**
     * The nav items for the registered types the current user may view.
     *
     * @return list<NavItem>
     */
    public function navItems(): array
    {
        $items = [];
        $user = currentUser();

        foreach ($this->types() as $elementType) {
            $item = $elementType::cpNavItem();

            if ($item !== null && $user !== null && $elementType::canViewIndex($user)) {
                $items[] = $item;
            }
        }

        return $items;
    }

    /**
     * Lets templates query the type like Craft's own, through `craft.customers()`.
     *
     * @param  class-string<EloquentElement<covariant Model>>  $elementType
     */
    private function registerTemplateQuery(string $elementType): void
    {
        $name = Str::camel(Str::plural((string) $elementType::refHandle()));

        /** @param array<string, mixed> $criteria */
        CraftVariable::macro($name, fn (array $criteria = []) => EloquentElementQuery::configure($elementType::find(), $criteria));
    }

    /**
     * Registers the control panel routes of the types registered while the app booted, and of any registered later.
     *
     * Called once the app has booted ({@see ElementServiceProvider}), so Craft's own routes are in place to
     * check URIs against, and a type registered by any provider's `boot()` is included.
     *
     * @internal
     */
    public function registerRoutes(): void
    {
        $this->craftBooted = true;

        foreach ($this->types() as $elementType) {
            $this->registerIndexRoute($elementType);
        }
    }

    /** @param class-string<EloquentElement<covariant Model>> $elementType */
    private function registerIndexRoute(string $elementType): void
    {
        // Cached routes already hold the type's own, but those can still clash with another route
        if ($this->app->routesAreCached()) {
            $uri = $elementType::cpIndexUri();

            if ($uri !== null) {
                $this->ensureUriIsFree($elementType, $uri);
            }

            return;
        }

        // Types registered while the app boots wait for registerRoutes()
        if (! $this->craftBooted) {
            return;
        }

        $this->app->make(Router::class)
            ->middleware(['web', 'craft', 'craft.cp'])
            ->name('craft.cp.')
            ->prefix($this->app->make(Routes::class)->cpTriggerRoutePrefix())
            ->group(fn () => $this->indexRoute($elementType));
    }

    /** @param class-string<EloquentElement<covariant Model>> $elementType */
    private function indexRoute(string $elementType): void
    {
        $uri = $elementType::cpIndexUri();

        if ($uri === null) {
            return;
        }

        $this->ensureUriIsFree($elementType, $uri);

        $name = "elements.{$elementType::refHandle()}";

        $this->app->make(Router::class)
            ->middleware(['auth', 'can:accessCp'])
            ->group(function (Router $router) use ($elementType, $uri, $name): void {
                // Named as they're created, so the route collection can find a type registered after boot by name
                $router->name("$name.index")->get($uri, ElementTypeIndexController::class)
                    ->middleware(AuthorizeEloquentElementIndex::class)
                    ->defaults('elementType', $elementType)
                    ->defaults('newElementRoute', "craft.cp.$name.new");

                $router->name("$name.new")->get("$uri/new", NewElementController::class)
                    ->defaults('elementType', $elementType);

                $router->name("$name.edit")->get("$uri/{id}", EditElementController::class)
                    ->whereNumber('id')
                    ->defaults('elementType', $elementType);
            });
    }

    /**
     * Refuses a URI that one of the control panel's routes already answers, which
     * would otherwise shadow the type's index or creation page — or be shadowed by them.
     *
     * @param  class-string<EloquentElement<covariant Model>>  $elementType
     */
    private function ensureUriIsFree(string $elementType, string $uri): void
    {
        $routes = $this->app->make(Router::class)->getRoutes();

        foreach ([$uri, "$uri/new"] as $path) {
            try {
                $route = $routes->match(Request::create(Url::cpUrl($path)));
            } catch (HttpException) {
                continue;
            }

            if (! $route->isFallback && ($route->defaults['elementType'] ?? null) !== $elementType) {
                throw new InvalidArgumentException("{$elementType}’s control panel URI “{$uri}” is taken by the “{$route->uri()}” route. Override cpIndexUri() to give it another.");
            }
        }
    }
}
